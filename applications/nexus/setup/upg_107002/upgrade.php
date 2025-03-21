<?php
/**
 * @brief		4.7.0 Beta 2 Upgrade Code
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Commerce
 * @since		11 May 2022
 */

namespace IPS\nexus\setup\upg_107002;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * 4.7.0 Beta 2 Upgrade Code
 */
class Upgrade
{
	/**
	 * Generate missing purchases for subscriptions
	 *
	 * @return	bool|array 	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1() : bool|array
	{
		Task::queue( 'nexus', 'FixMissingSubscriptionPurchases', [] );

		return TRUE;
	}
}