<?php
/**
 * @brief		Upgrader: License
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Sep 2014
 */
 
namespace IPS\core\modules\setup\upgrade;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Data\Store;
use IPS\Db;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Text;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Member;
use IPS\Output;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader: License
 */
class license extends Controller
{

	/**
	 * Show Form
	 *
	 * @return	void
	 */
	public function manage() : void
	{
		/* Check license key */
		if( Db::i()->checkForTable( 'core_store' ) )
		{
			$licenseData = IPS::licenseKey( TRUE );
		}
		else
		{
			$licenseData	= NULL;

			try
			{
				$license	= Db::i()->select( '*', 'cache_store', array( 'cs_key=?', 'licenseData' ) )->first();
				$licenseData	= unserialize( $license['cs_value'] );
			}
			catch( Exception $e ){}
		}

		if( isset( $licenseData['key'] ) AND !isset( $licenseData['expires'] ) )
		{
			$licenseData	= $this->getLicenseData();
		}

		if( !$licenseData )
		{
			$active	= NULL;
		}
		else
		{
			$active = ( isset( $licenseData['expires'] ) and $licenseData['expires'] AND strtotime( $licenseData['expires'] ) > time() ) ? TRUE : ( ( isset( $licenseData['active'] ) and $licenseData['active'] ) ? TRUE : NULL );
		}

		if ( !$active )
		{
			$response	= NULL;
			$form		= new Form( 'licensekey', 'continue' );
			$form->add( new Text( 'ipb_reg_number', NULL, TRUE, array(), function( $val ){
				IPS::checkLicenseKey( $val, Settings::i()->base_url );
			} ) );

			if( $values = $form->values() )
			{
				$values['ipb_reg_number'] = trim( $values['ipb_reg_number'] );
				
				if ( mb_substr( $values['ipb_reg_number'], -12 ) === '-TESTINSTALL' )
				{
					$values['ipb_reg_number'] = mb_substr( $values['ipb_reg_number'], 0, -12 );
				}
	
				/* Save */
				$form->saveAsSettings( $values );

				/* Refresh the locally stored license info */
				if( Db::i()->checkForTable( 'core_store' ) )
				{
					unset( Store::i()->license_data );
					$licenseData = IPS::licenseKey();
				}
				else
				{
					/* Call the main server */
					$licenseData	= $this->getLicenseData();
				}

				/* Reset some vars now */				
				$active = ( isset( $licenseData['expires'] ) and $licenseData['expires'] AND strtotime( $licenseData['expires'] ) > time() ) ? TRUE : ( ( isset( $licenseData['active'] ) and $licenseData['active'] ) ? TRUE : FALSE );

				if( $active )
				{
					$form	= NULL;
				}
			}
		}

		if( $active )
		{
			/* Clear any caches or else we might not see new versions on the next screen */
			if ( isset( Store::i()->applications ) )
			{
				unset( Store::i()->applications );
			}

			Output::i()->redirect( Url::internal( "controller=applications" )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		}
		
		Output::i()->title		= Member::loggedIn()->language()->addToStack('license');
		Output::i()->output	= Theme::i()->getTemplate( 'global' )->license( $form, $active );
	}

	/**
	 * Retrieve license data from license server
	 *
	 * @return array|null
	 */
	protected function getLicenseData() : ?array
	{
		/* Call the main server */
		try
		{
			$response = Url::ips( 'license/' . Settings::i()->ipb_reg_number )->request()->get();
			if ( $response->httpResponseCode == 404 )
			{
				$licenseData	= NULL;
			}
			else
			{
				$licenseData	= $response->decodeJson();
			}
		}
		catch ( Exception $e )
		{
			$licenseData	= NULL;
		}

		return $licenseData;
	}
}