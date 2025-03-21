<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		20 Dec 2019
 */

namespace IPS\nexus\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\Member;
use IPS\nexus\Package;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use function count;
use function defined;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class MassChangePurchases extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['count'] = Db::i()->select( 'COUNT(*)', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'package', $data['id'] ) )->first();
		
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( array &$data, int $offset ): int
	{
		try
		{
			$package = Package::load( $data['id'] );
		}
		catch( OutOfRangeException )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$admin = Member::load( $data['admin'] );
		if ( $data['cancel_type'] == 'change' )
		{
			$newPackage = Package::load( $data['mass_change_purchases_to'] );
		}
		
		$query = Db::i()->select( '*', 'nexus_purchases', array( 'ps_app=? AND ps_type=? AND ps_item_id=?', 'nexus', 'package', $data['id'] ), 'ps_id ASC', ( $data['cancel_type'] == 'change' ) ? NULL : array( $offset, REBUILD_SLOW ) );
		if ( !count( $query ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		foreach ( new ActiveRecordIterator( $query, 'IPS\nexus\Purchase' ) as $purchase )
		{
			if ( $data['cancel_type'] == 'change' )
			{
				$package->upgradeDowngrade( $purchase, $newPackage, isset( $data[ 'renew_option_' . $newPackage->_id ] ) ? $data[ 'renew_option_' . $newPackage->_id ] : NULL, (bool) $data['mass_change_purchases_override'] );
				$purchase->member->log( 'purchase', array( 'type' => 'change', 'id' => $purchase->id, 'old' => $purchase->name, 'name' => $newPackage->titleForLog(), 'system' => FALSE ), $admin );
			}
			else
			{
				/* If grouped, ungroup */
				$grouped = $purchase->grouped_renewals;
				if ( $grouped )
				{
					$purchase->ungroupFromParent();
					$purchase->save();
				}
				
				/* Update purchase and log */
				if ( $data['cancel_type'] == 'expire' )
				{
					$purchase->renewals = NULL;
					$purchase->member->log( 'purchase', array( 'type' => 'info', 'id' => $purchase->id, 'name' => $purchase->name, 'info' => 'remove_renewals' ), $admin );
				}
				else
				{
					$purchase->cancelled = TRUE;
					$purchase->member->log( 'purchase', array( 'type' => 'cancel', 'id' => $purchase->id, 'name' => $purchase->name ), $admin );
				}
				$purchase->can_reactivate = $data['ps_can_reactivate'];
				$purchase->save();
				
				/* If grouped, regroup */
				if ( $grouped )
				{
					$purchase->groupWithParent();
					$purchase->save();
				}
			}
		}
				
		return $offset + REBUILD_SLOW;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{		
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'mass_change_purchases_in_progress_text', FALSE, array( 'sprintf' => Package::load( $data['id'] )->_title ) ), 'complete' => floor( 100 / $data['count'] * $offset ) );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{

	}
}