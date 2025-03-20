<?php
/**
 * @brief		Content Router extension: Calendar
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		7 Jan 2014
 */

namespace IPS\calendar\extensions\core\ContentRouter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\Extensions\ContentRouterAbstract;
use IPS\Member;
use IPS\Member\Group;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Content Router extension: Calendar
 */
class Calendar extends ContentRouterAbstract
{
	/**
	 * Constructor
	 *
	 * @param	Member|Group|NULL	$member		If checking access, the member/group to check for, or NULL to not check access
	 * @return	void
	 */
	public function __construct( Group|Member $member = NULL )
	{
		if ( $member === NULL or $member->canAccessModule( Module::get( 'calendar', 'calendar', 'front' ) ) )
		{
			$this->classes[] = 'IPS\calendar\Event';
		}
	}
}