<?php
/**
 * @brief		Stripe Webhook Handler
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Nexus
 * @since		20 Jul 2017
 */

use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Http\Url;
use IPS\Math\Number;
use IPS\Member;
use IPS\nexus\Customer;
use IPS\nexus\Fraud\MaxMind\Request;
use IPS\nexus\Gateway;
use IPS\nexus\Gateway\Stripe;
use IPS\nexus\Invoice;
use IPS\nexus\Money;
use IPS\nexus\Transaction;
use IPS\Output;
use IPS\Session\Front;
use IPS\Settings;

define('REPORT_EXCEPTIONS', TRUE);
require_once '../../../../init.php';
Front::i();

Output::setCacheTime( false );

/**
 * Stripe Webhook Handler
 */
class stripeWebhookHandler
{
	/**
	 * @brief	Raw Webhook Data (as a string)
	 */
	private mixed $body;
	
	/**
	 * @brief	Parsed Webhook Data (as an array)
	 */
	private array $data;
	
	/**
	 * @brief	Transaction
	 */
	private Transaction $transaction;
	
	/**
	 * Constructor
	 *
	 * @param	string	$body	The raw body posted to this script
	 * @return	void
	 */
	public function __construct( string $body )
	{
		$this->body = $body;
		$this->data = json_decode( $body, TRUE );
	}
	
	/**
	 * A source has become chargeable (for example, 3DSecure was completed) so we're ready to authorize the payment
	 *
	 * @return	string
	 */
	private function sourceChargeable() : string
	{
		/* Don't need to do anything for cards, since this is only for asynchronous payments */
		if ( isset( $this->data['data']['object']['type'] ) and in_array( $this->data['data']['object']['type'], array( 'card' ) ) )
		{
			return 'NO_PROCESSING_REQUIRED';
		}
		
		/* Check the status */
		if ( !in_array( $this->transaction->status, array( Transaction::STATUS_PENDING, Transaction::STATUS_WAITING ) ) )
		{
			if ( in_array( $this->transaction->status, array( Transaction::STATUS_GATEWAY_PENDING, Transaction::STATUS_PAID ) ) )
			{
				return 'ALREADY_PROCESSED';
			}
			else
			{
				throw new Exception('BAD_STATUS');
			}
		}
		
		/* Validate the source with Stripe */
		$source = $this->transaction->method->api( 'sources/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['id'] ), null, 'get' );
		if ( $source['client_secret'] != $this->data['data']['object']['client_secret'] )
		{
			throw new Exception('BAD_SECRET');
		}
		
		/* Check we're not just going to refuse this */
		$maxMind = NULL;
		if ( Settings::i()->maxmind_key and ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways == '*' or in_array( $this->transaction->method->id, explode( ',', Settings::i()->maxmind_gateways ) ) ) )
		{
			$maxMind = new Request( FALSE );
			$maxMind->setIpAddress( $this->transaction->ip );
			$maxMind->setTransaction( $this->transaction );
		}
		$fraudResult = $this->transaction->runFraudCheck( $maxMind );
		if ( $fraudResult === Transaction::STATUS_REFUSED )
		{
			$this->transaction->executeFraudAction( $fraudResult, FALSE );
			$this->transaction->sendNotification();
		}
		
		/* Authorize */	
		else
		{	
			$this->transaction->auth = $this->transaction->method->auth( $this->transaction, array( $this->transaction->method->id . '_card' => $source['id'] ) );
			$this->transaction->status = Transaction::STATUS_GATEWAY_PENDING;
			$this->transaction->save();
		}
		
		/* Return */
		return 'OK';
	}
	
	/**
	 * A charge was successful on a RECURRING payment, so we can approve the transaction
	 *
	 * @return	string
	 */
	private function chargeSucceeded() : string
	{
		/* Check the status */
		if ( !in_array( $this->transaction->status, [ Transaction::STATUS_GATEWAY_PENDING, IPS\nexus\Transaction::STATUS_PENDING ] ) )
		{
			if ( $this->transaction->status === Transaction::STATUS_PAID )
			{
				return 'ALREADY_PROCESSED';
			}
			else
			{
				throw new Exception('BAD_STATUS');
			}
		}
		
		/* Validate the charge with Stripe */
		try
		{
			$charge = $this->transaction->method->api( 'charges/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['id'] ), null, 'get' );
			if ( !in_array( $charge['status'], array( 'succeeded', 'paid' ) ) )
			{
				throw new Exception;
			}
		}
		catch (Exception )
		{
			throw new Exception('INVALID_CHARGE');
		}
		
		/* Create a MaxMind request */
		$maxMind = NULL;
		if ( Settings::i()->maxmind_key and ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways == '*' or in_array( $this->transaction->method->id, explode( ',', Settings::i()->maxmind_gateways ) ) ) )
		{
			$maxMind = new Request( FALSE );
			$maxMind->setIpAddress( $this->transaction->ip );
			$maxMind->setTransaction( $this->transaction );
		}
		
		/* Check fraud rules */
		$fraudResult = $this->transaction->runFraudCheck( $maxMind );
		if ( $fraudResult )
		{
			$this->transaction->executeFraudAction( $fraudResult, TRUE );
		}
		
		/* If we're not being fraud blocked, we can approve */
		if ( $fraudResult === Transaction::STATUS_PAID )
		{
			$this->transaction->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> Transaction::STATUS_PAID,
				'id'			=> $this->transaction->id,
				'invoice_id'	=> $this->transaction->invoice->id,
				'invoice_title'	=> $this->transaction->invoice->title,
			) );
			$this->transaction->approve();
		}
		
		/* Either way, let the user know we got their payment */
		$this->transaction->sendNotification();
		
		/* Return */
		return 'OK';
	}
	
	/**
	 * A charge was successful on a NEW payment, so we can create, capture, and approve the transaction
	 *
	 * @return	string
	 */
	private function chargeSucceededNew() : string
	{
		/* Check the status */
		if ( !in_array( $this->transaction->status, [ Transaction::STATUS_GATEWAY_PENDING, IPS\nexus\Transaction::STATUS_PENDING ] ) )
		{
			if ( $this->transaction->status === Transaction::STATUS_PAID )
			{
				return 'ALREADY_PROCESSED';
			}
			else
			{
				throw new Exception('BAD_STATUS');
			}
		}
		
		/* If the invoice this is for is already paid, this may be a duplicate charge, so don't capture */
		if ( $this->transaction->invoice->status === Invoice::STATUS_PAID )
		{
			throw new Exception('INVOICE_ALREADY_PAID');
		}
		
		/* Validate the charge with Stripe */
		try
		{
			$charge = $this->transaction->method->api( 'charges/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['id'] ), null, 'get' );
			if ( !in_array( $charge['status'], array( 'succeeded', 'paid' ) ) )
			{
				throw new Exception;
			}
		}
		catch (Exception )
		{
			throw new Exception('INVALID_CHARGE');
		}
		
		/* If this was done by an admin, we just capture and approve straight away */
		if ( isset( $this->data['data']['object']['metadata']['Admin'] ) and $this->data['data']['object']['metadata']['Admin'] )
		{
			$this->transaction->auth = NULL;
			$this->transaction->capture();
			$this->transaction->member->log( 'transaction', array(
				'type'			=> 'paid',
				'status'		=> Transaction::STATUS_PAID,
				'id'			=> $this->transaction->id,
				'invoice_id'	=> $this->transaction->invoice->id,
				'invoice_title'	=> $this->transaction->invoice->title,
			), Member::load( $this->data['data']['object']['metadata']['Admin'] ) );
			$this->transaction->approve();
		}
		
		/* Otherwise, check fraud rules and then capture */
		else
		{		
			/* Create a MaxMind request */
			$maxMind = NULL;
			if ( Settings::i()->maxmind_key and ( !Settings::i()->maxmind_gateways or Settings::i()->maxmind_gateways == '*' or in_array( $this->transaction->method->id, explode( ',', Settings::i()->maxmind_gateways ) ) ) )
			{
				$maxMind = new Request( FALSE );
				$maxMind->setIpAddress( $this->transaction->ip );
				$maxMind->setTransaction( $this->transaction );
			}
			
			/* Set the authorize date (it's already actually been authorized) */			
			$this->transaction->auth = \IPS\DateTime::ts( $this->data['data']['object']['created'] )->add( new DateInterval( 'P7D' ) );
						
			/* Check Fraud Rules and capture */
			$this->transaction->checkFraudRulesAndCapture( $maxMind );
		}
		
		/* Send email receipt */
		$this->transaction->sendNotification();
		
		/* Return */
		return 'OK';
	}
	
	/**
	 * A charge failed so we need to mark the transaction as failed locally
	 *
	 * @return	string
	 */
	private function chargeFailed() : string
	{
		/* Check the status */
		if ( !in_array( $this->transaction->status, [ Transaction::STATUS_GATEWAY_PENDING, IPS\nexus\Transaction::STATUS_PENDING ] ) )
		{
			if ( $this->transaction->status === Transaction::STATUS_REFUSED )
			{
				return 'ALREADY_PROCESSED';
			}
			else
			{
				throw new Exception('BAD_STATUS');
			}
		}
		
		/* Validate the charge with Stripe */
		try
		{
			$charge = $this->transaction->method->api( 'charges/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['id'] ), null, 'get' );
			if ( $charge['status'] !== 'failed' )
			{
				throw new Exception;
			}
		}
		catch (Exception )
		{
			throw new Exception('INVALID_CHARGE');
		}
		
		/* Mark it failed */
		$this->transaction->status = Transaction::STATUS_REFUSED;
		$extra = $this->transaction->extra;
		$extra['history'][] = array( 's' => Transaction::STATUS_REFUSED, 'noteRaw' => $this->data['data']['object']['failure_message'] );
		$this->transaction->extra = $extra;
		$this->transaction->save();
		$this->transaction->member->log( 'transaction', array(
			'type'			=> 'paid',
			'status'		=> Transaction::STATUS_REFUSED,
			'id'			=> $this->transaction->id,
			'invoice_id'	=> $this->transaction->invoice->id,
			'invoice_title'	=> $this->transaction->title,
		), FALSE );
		
		/* Send notification */
		$this->transaction->sendNotification();
		
		/* Return */
		return 'OK';
	}

	/**
	 * A chargeback/dispute has been made against a transaction so we need to mark it as such locally
	 *
	 * @return	string
	 */
	private function disputeCreated() : string
	{
		/* Validate the dispute with Stripe */
		try
		{
			$dispute = $this->transaction->method->api( 'disputes/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['id'] ), null, 'get' );
			if ( !in_array( $dispute['status'], array( 'needs_response', 'warning_needs_response' ) ) )
			{
				throw new Exception;
			}
						
			if ( substr( $this->transaction->gw_id, 0, 3 ) === 'pi_' )
			{
				$paymentIntent = $this->transaction->method->api( 'charges?payment_intent=' . $this->transaction->gw_id, null, 'get' );
				
				$ok = FALSE;
				foreach ( $paymentIntent['data'] as $charge )
				{
					if ( $dispute['charge'] === $charge['id'] )
					{
						$ok = TRUE;
						break;
					}
				}
				
				if ( !$ok )
				{
					throw new Exception;
				}
			}
			elseif ( $dispute['charge'] !== $this->transaction->gw_id )
			{
				throw new Exception;
			}
		}
		catch (Exception )
		{
			throw new Exception('INVALID_DISPUTE');
		}
		
		/* Mark the transaction as disputed */
		$this->transaction->status = Transaction::STATUS_DISPUTED;
		$extra = $this->transaction->extra;
		$extra['history'][] = array( 's' => Transaction::STATUS_DISPUTED, 'on' => $this->data['data']['object']['created'], 'ref' => $this->data['data']['object']['id'] );
		$this->transaction->extra = $extra;
		$this->transaction->save();
		
		/* Log */ 
		if ( $this->transaction->member )
		{
			$this->transaction->member->log( 'transaction', array(
				'type'		=> 'status',
				'status'	=> Transaction::STATUS_DISPUTED,
				'id'		=> $this->transaction->id
			) );
		}
		
		/* Mark the invoice as not paid (revoking benefits) */
		$this->transaction->invoice->markUnpaid( Invoice::STATUS_CANCELED );
		
		/* Send admin notification */
		AdminNotification::send( 'nexus', 'Transaction', Transaction::STATUS_DISPUTED, TRUE, $this->transaction );
		
		/* Return */
		return 'OK';
	}
	
	/**
	 * A chargeback/dispute has been resolved (which may be won or lost)
	 *
	 * @return	string
	 */
	private function disputeClosed() : string
	{
		/* Validate the dispute with Stripe */
		try
		{
			$dispute = $this->transaction->method->api( 'disputes/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['id'] ) );						
			if ( substr( $this->transaction->gw_id, 0, 3 ) === 'pi_' )
			{
				$paymentIntent = $this->transaction->method->api( 'charges?payment_intent=' . $this->transaction->gw_id, null, 'get' );
				
				$ok = FALSE;
				foreach ( $paymentIntent['data'] as $charge )
				{
					if ( $dispute['charge'] === $charge['id'] )
					{
						$ok = TRUE;
						break;
					}
				}
				
				if ( !$ok )
				{
					throw new Exception;
				}
			}
			elseif ( $dispute['charge'] !== $this->transaction->gw_id )
			{
				throw new Exception;
			}
		}
		catch (Exception )
		{
			throw new Exception('INVALID_DISPUTE');
		}
		
		/* Did we win or lose? */
		if ( in_array( $dispute['status'], array( 'won', 'warning_closed' ) ) )
		{
			// Do nothing
		}
		elseif ( in_array( $dispute['status'], array( 'lost' ) ) )
		{
			$this->transaction->status = Transaction::STATUS_REFUNDED;
			$this->transaction->save();
		}
		
		/* Return */
		return 'OK';
	}
	
	/**
	 * Run
	 *
	 * @param	string	$signature	The signature provided by this request
	 * @return	string
	 */
	public function run( string $signature ) : string
	{
		/* Ignore anything we don't care about */
		if ( !isset( $this->data['type'] ) or !in_array( $this->data['type'], array( 'source.chargeable', 'charge.succeeded', 'charge.failed', 'charge.dispute.created', 'charge.dispute.closed' ) ) )
		{
			return 'UNNEEDED_TYPE';
		}
		
		/* Try to find the transaction this is about */
		$new = FALSE;
		try
		{
			if ( isset( $this->data['data']['object']['metadata']['Transaction ID'] ) )
			{
				$this->transaction = Transaction::constructFromData( Db::i()->select( '*', 'nexus_transactions', array( 't_id=?', $this->data['data']['object']['metadata']['Transaction ID'] ), flags: Db::SELECT_FROM_WRITE_SERVER )->first() );
			}
			elseif ( isset( $this->data['data']['object']['redirect']['return_url'] ) )
			{
				$url = new Url( $this->data['data']['object']['redirect']['return_url'] );
				$this->transaction = Transaction::constructFromData( Db::i()->select( '*', 'nexus_transactions', array( 't_id=?', $url->queryString['nexusTransactionId'] ), flags: Db::SELECT_FROM_WRITE_SERVER )->first() );
			}
			elseif ( isset( $this->data['data']['object']['charge'] ) )
			{
				$paymentIntentId = NULL;
				foreach ( Gateway::roots() as $method )
				{
					if ( $method instanceof Stripe )
					{
						try
						{
							$charge = $method->api( 'charges/' . preg_replace( '/[^A-Z0-9_]/i', '', $this->data['data']['object']['charge'] ), null, 'get' );
							if ( isset( $charge['payment_intent'] ) )
							{
								$paymentIntentId = $charge['payment_intent'];
							}
							break;
						}
						catch (Exception )
						{
							// Do nothing - try the next one
						}
					}
				}
				
				if ( $paymentIntentId )
				{
					$this->transaction = Transaction::constructFromData( Db::i()->select( '*', 'nexus_transactions', array( '( t_gw_id=? OR t_gw_id=? )', $paymentIntentId, $this->data['data']['object']['charge'] ), flags: Db::SELECT_FROM_WRITE_SERVER )->first() );
				}
				else
				{
					$this->transaction = Transaction::constructFromData( Db::i()->select( '*', 'nexus_transactions', array( 't_gw_id=?', $this->data['data']['object']['charge'] ), flags: Db::SELECT_FROM_WRITE_SERVER )->first() );
				}
			}
			elseif ( isset( $this->data['data']['object']['id'] ) and mb_substr( $this->data['data']['object']['id'], 0, 4 ) !== 'src_' )
			{
				if ( isset( $this->data['data']['object']['payment_intent'] ) )
				{
					$where = array( array( '( t_gw_id=? OR t_gw_id=? )', $this->data['data']['object']['id'], $this->data['data']['object']['payment_intent'] ) );
				}
				else
				{
					$where = array( array( 't_gw_id=?', $this->data['data']['object']['id'] ) );
				}
				
				if ( isset( $this->data['data']['object']['metadata']['Invoice ID'] ) )
				{
					$where[] = array( 't_invoice=?', $this->data['data']['object']['metadata']['Invoice ID'] );
				}
				
				try
				{
					$this->transaction = Transaction::constructFromData( Db::i()->select( '*', 'nexus_transactions', $where, flags: Db::SELECT_FROM_WRITE_SERVER )->first() );
				}
				catch (UnderflowException $e )
				{
					/* If we can't find a transaction, but there is an "IP Address" in the metadata, this is from the actual checkout process: create a transaction */
					if ( isset( $this->data['data']['object']['metadata']['Invoice ID'] ) and isset( $this->data['data']['object']['metadata']['IP Address'] ) and isset( $this->data['data']['object']['metadata']['Payment Method ID'] ) )
					{
						$invoice = Invoice::load( intval( $this->data['data']['object']['metadata']['Invoice ID'] ) );
						
						$this->transaction = new Transaction;
						$this->transaction->member = isset( $this->data['data']['object']['metadata']['Customer ID'] ) ? Customer::load( intval( $this->data['data']['object']['metadata']['Customer ID'] ) ) : $invoice->member;
						$this->transaction->invoice = $invoice;
						$this->transaction->amount = new Money( new Number( mb_substr( $this->data['data']['object']['amount'], 0, -2 ) . '.' . mb_substr( $this->data['data']['object']['amount'], -2 ) ), mb_strtoupper( $this->data['data']['object']['currency'] ) );
						$this->transaction->ip = $this->data['data']['object']['metadata']['IP Address'];
						$this->transaction->gw_id = $this->data['data']['object']['payment_intent'] ?? $this->data['data']['object']['id'];
						$this->transaction->status = Transaction::STATUS_PENDING;
						$this->transaction->method = Gateway::load( intval( $this->data['data']['object']['metadata']['Payment Method ID'] ) );
						$this->transaction->save();
						
						$new = TRUE;
					}
					else
					{					
						throw $e;
					}
				}
			}
			else
			{
				return 'NO_TRANSACTION_INFORMATION';
			}
		}
		catch (Exception )
		{
			if ( $this->data['type'] === 'charge.failed' )
			{
				return 'COULD_NOT_FIND_TRANSACTION'; // Sometimes if a transaction fails we don't create a transaction record
			}
			
			throw new Exception('COULD_NOT_FIND_TRANSACTION');
		}
		
		/* Validate the signature */
		if ( !( $this->transaction->method instanceof Stripe ) )
		{
			throw new Exception('INVALID_GATEWAY');
		}
		$settings = json_decode( $this->transaction->method->settings, TRUE );
		if ( isset( $settings['webhook_secret'] ) and $settings['webhook_secret'] )  // In case they upgraded and haven't provided one
		{
			$sig = array();
			foreach ( explode( ',', $signature ) as $row )
			{
				if ( strpos( $row, '=' ) !== FALSE )
				{
					list( $k, $v ) = explode( '=', trim( $row ) );
					$sig[ trim( $k ) ][] = trim( $v );
				}
			}
						
			if ( isset( $sig['t'] ) and isset( $sig['t'][0] ) )
			{
				$signedPayload = $sig['t'][0] . '.' . $this->body;
				$signature = hash_hmac( 'sha256', $signedPayload, $settings['webhook_secret'] );
				
				if ( !in_array( $signature, $sig['v1'] ) )
				{
					throw new Exception('INVALID_SIGNING_SECRET');
				}
			}
			else
			{
				throw new Exception('INVALID_SIGNING_SECRET');
			}
		}
		
		/* Do it */
		if ( isset( $this->data['type'] ) )
		{
			switch ( $this->data['type'] )
			{
				case 'source.chargeable':
					return $this->sourceChargeable();
				case 'charge.succeeded':
					return $new ? $this->chargeSucceededNew() : $this->chargeSucceeded();
				case 'charge.failed':
					return $this->chargeFailed();
				case 'charge.dispute.created':
					return $this->disputeCreated();
				case 'charge.dispute.closed':
					return $this->disputeClosed();
				default:
					return 'UNNEEDED_TYPE';
			}
		}

		return '';
	}
}

$class = new stripeWebhookHandler( trim( @file_get_contents('php://input') ) );
try
{
	sleep( 5 ); // Stripe can send web hooks very quickly, we need to delay to ensure we've processed the transaction first.
	$response = $class->run($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
	Output::i()->sendOutput( $response, 200, 'text/plain' );
}
catch (Exception $e)
{
	Output::i()->sendOutput( $e->getMessage(), 500, 'text/plain' );
}