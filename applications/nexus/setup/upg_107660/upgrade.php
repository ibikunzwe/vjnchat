<?php
/**
 * @brief		4.7.9 Beta 1 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		11 Jan 2023
 */

namespace IPS\nexus\setup\upg_107660;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\AdminNotification;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.9 Beta 1 Upgrade Code
 */
class Upgrade
{
	/**
	 * Step 1 - Remove redundant settings
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		Db::i()->delete( 'core_sys_conf_settings', "conf_key IN( 'monitoring_alert', 'monitoring_allowed_fails' , 'monitoring_backup' , 'monitoring_from' , 'monitoring_panic' , 'monitoring_script' , 'network_status' , 'nexus_domain_prices' , 'nexus_domain_tax' , 'nexus_enom_pw' , 'nexus_enom_un' , 'nexus_hosting_allow_change_domain' , 'nexus_hosting_allow_own_domain' , 'nexus_hosting_bandwidth' , 'nexus_hosting_error_emails' , 'nexus_hosting_nameservers' , 'nexus_hosting_own_domain_sub' , 'nexus_hosting_own_domains' , 'nexus_hosting_subdomains' , 'nexus_hosting_terminate' )" );

		if( Settings::i()->maxmind_key and !Settings::i()->maxmind_id )
		{
			AdminNotification::send( 'nexus', 'Maxmind', NULL, TRUE, array() );
		}
    
		return TRUE;
	}

	/**
	 * Step 2 - Remove hosting purchases
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step2() : bool|array
	{
		/* Remove hosting purchases */
		Db::i()->delete( 'nexus_purchases', array( Db::i()->in( 'ps_item_id', Db::i()->select( 'p_id', 'nexus_packages', array( "p_type='hosting'" ) ) ) ) );
	
		return TRUE;
	}

	/**
	 * Step 3 - Remove hosting packages
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step3() : bool|array
	{
		/* Remove products */
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'nexus_packages', array( "p_type=?", 'hosting' ) ), 'IPS\nexus\Package' ) AS $pkg )
		{
			$pkg->delete();
		}

		return TRUE;
	}

	/**
	 * Step 4 - Remove ACP notifications
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step4() : bool|array
	{
		/* Remove products */
		Db::i()->delete( 'core_acp_notifications', array( "app=? AND ext=?", 'nexus', 'HostingError' ) );

		return TRUE;
	}

	/**
	 * Step 5 - Remove Hosting module
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step5() : bool|array
	{
		Db::i()->delete( 'core_modules', array( 'sys_module_application=? and sys_module_key=? and sys_module_area=?', 'nexus', 'hosting', 'admin' ) );

		return TRUE;
	}
}