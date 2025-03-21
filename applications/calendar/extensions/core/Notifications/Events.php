<?php
/**
 * @brief		Notification Options
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 May 2016
 */

namespace IPS\calendar\extensions\core\Notifications;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\calendar\Event;
use IPS\Extensions\NotificationsAbstract;
use IPS\Lang;
use IPS\Member;
use IPS\Notification\Inline;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Notification Options
 */
class Events extends NotificationsAbstract
{
	/**
	 * Get fields for configuration
	 *
	 * @param	Member|null	$member		The member (to take out any notification types a given member will never see) or NULL if this is for the ACP
	 * @return	array
	 */
	public static function configurationOptions( Member $member = NULL ): array
	{
		return array(
			'event_reminder'	=> array(
				'type'				=> 'standard',
				'notificationTypes'	=> array( 'event_reminder' ),
				'title'				=> 'notifications__calendar_Events',
				'showTitle'			=> FALSE,
				'description'		=> 'notifications__calendar_Events_desc',
				'default'			=> array( 'inline', 'push', 'email' ),
				'disabled'			=> array()
			)
		);
	}
	
	/**
	 * Parse notification: new_content
	 *
	 * @param	Inline	$notification	The notification
	 * @param	bool						$htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	 	return array(
	 		'title'		=> "Mark has replied to A Topic",	// The notification title
	 		'url'		=> new \IPS\Http\Url( ... ),		// The URL the notification should link to
	 		'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	 														// explains what the notification is about - just include any appropriate content.
	 														// For example, if the notification is about a post, set this as the body of the post.
	 		'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	 	);
	 * @endcode
	 */
	public function parse_new_content( Inline $notification, bool $htmlEscape=TRUE ): array
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}
		
		$name = ( $item->isAnonymous() ) ? Member::loggedIn()->language()->addToStack( 'post_anonymously_placename' ) : $item->author()->name;
		
		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__new_content', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array(
					$name,
					mb_strtolower( $item->indefiniteArticle() ),
					$item->container()->getTitleForLanguage( Member::loggedIn()->language(), $htmlEscape ? array( 'escape' => TRUE ) : array() ),
					$item->mapped('title')
				)
			) ),
			'url'		=> $notification->item->url(),
			'content'	=> $notification->item->content(),
			'author'	=> $notification->item->author(),
			'unread'	=> (bool) ( $item->unread() )
		);
	}

	/**
	 * Parse notification: Event reminder
	 *
	 * @param Inline $notification	The notification
	 * @param bool $htmlEscape		TRUE to escape HTML in title
	 * @return	array
	 * @code
	* return array(
	* 'title'		=> "Mark has replied to A Topic",	// The notification title
	* 'url'		=> new \IPS\Http\Url( ... ),		// The URL the notification should link to
	* 'content'	=> "Lorem ipsum dolar sit",			// [Optional] Any appropriate content. Do not format this like an email where the text
	* // explains what the notification is about - just include any appropriate content.
	* // For example, if the notification is about a post, set this as the body of the post.
	* 'author'	=>  \IPS\Member::load( 1 ),			// [Optional] The user whose photo should be displayed for this notification
	* );
	 * @endcode
	 */
	public function parse_event_reminder( Inline $notification, bool $htmlEscape=TRUE ): array
	{
		$item = $notification->item;
		if ( !$item )
		{
			throw new OutOfRangeException;
		}

		return array(
			'title'		=> Member::loggedIn()->language()->addToStack( 'notification__event_reminder', FALSE, array(
				( $htmlEscape ? 'sprintf' : 'htmlsprintf' ) => array( Member::loggedIn()->language()->addToStack( "days_to_go", FALSE, array( 'pluralize' => array( $notification->extra['daysToGo'] ) ) ), $item->mapped('title') )
			) ),
			'url'		=> $notification->item->url(),
			'content'	=> $notification->item->content(),
			'author'	=> $notification->item->author(),
			'unread'	=> (bool) ( $item->unread() )
		);
	}
	
	/**
	 * Parse notification for mobile: event_reminder
	 *
	 * @param	Lang	$language	The language that the notification should be in
	 * @param	Event	$event		The event the notification refers to
	 * @param 	int		$daysToGo
	 * @return	array
	 */
	public static function parse_mobile_event_reminder( Lang $language, Event $event, int $daysToGo ): array
	{
		return array(
			'title'			=> $language->addToStack( 'notification__event_reminder_title', FALSE, array(
				'pluralize' => array(1)
			) ),
			'body'		=> $language->addToStack( 'notification__event_reminder', FALSE, array( 'htmlsprintf' => array( 
				$language->addToStack( "days_to_go", FALSE, array( 'pluralize' => array(
					$daysToGo
				) ) ),
				$event->mapped('title')
			) ) ),
			'data'		=> array(
				'url'		=> (string) $event->url(),
				'author'	=> $event->author(),
			),
			'channelId'	=> 'events',
		);
	}
}