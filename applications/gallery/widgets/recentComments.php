<?php
/**
 * @brief		Recent comments widget
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		25 Mar 2014
 */

namespace IPS\gallery\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\WidgetComment;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Recent comments widget
 */
class recentComments extends WidgetComment
{
	/**
	 * @brief	Widget Key
	 */
	public string $key = 'recentComments';
	
	/**
	 * @brief	App
	 */
	public string $app = 'gallery';

	/**
	 * @brief Class
	 */
	protected static string $class = 'IPS\gallery\Image\Comment';

	/**
	 * @brief	Moderator permission to generate caches on [optional]
	 */
	protected array $moderatorPermissions	= array( 'can_view_hidden_content', 'can_view_hidden_gallery_image_comment' );
}