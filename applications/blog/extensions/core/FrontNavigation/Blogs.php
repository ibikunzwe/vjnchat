<?php
/**
 * @brief		Front Navigation Extension: Blogs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blog
 * @since		03 Mar 2014
 */

namespace IPS\blog\extensions\core\FrontNavigation;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Application\Module;
use IPS\core\FrontNavigation;
use IPS\core\FrontNavigation\FrontNavigationAbstract;
use IPS\Dispatcher;
use IPS\Http\Url;
use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Front Navigation Extension: Blogs
 */
class Blogs extends FrontNavigationAbstract
{
	/**
	 * @var string Default icon
	 */
	public string $defaultIcon = '\f044';

	/**
	 * Get Type Title which will display in the AdminCP Menu Manager
	 *
	 * @return	string
	 */
	public static function typeTitle(): string
	{
		return Member::loggedIn()->language()->addToStack('frontnavigation_blogs');
	}
	
	/**
	 * Can the currently logged in user access the content this item links to?
	 *
	 * @return    bool
	 */
	public function canAccessContent(): bool
	{
		return Member::loggedIn()->canAccessModule( Module::get( 'blog', 'blogs' ) );
	}
	
	/**
	 * Get Title
	 *
	 * @return    string
	 */
	public function title(): string
	{
		return Member::loggedIn()->language()->addToStack('frontnavigation_blogs');
	}
	
	/**
	 * Get Link
	 *
	 * @return    string|Url|null
	 */
	public function link(): Url|string|null
	{
		return Url::internal( "app=blog&module=blogs&controller=browse", 'front', 'blogs' );
	}
	
	/**
	 * Is Active?
	 *
	 * @return    bool
	 */
	public function active(): bool
	{
		return !FrontNavigation::$clubTabActive and !FrontNavigation::nodeExtensionIsActive( 'blog' ) and Dispatcher::i()->application->directory === 'blog';
	}
}