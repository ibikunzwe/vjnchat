<?php
/**
 * @brief		Search Result
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Sep 2015
*/

namespace IPS\Content\Search;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Member;
use IPS\Settings;
use IPS\Text\Parser;
use function defined;
use function preg_replace;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Search Result
 */
abstract class Result
{
	/**
	 * Pre-Display logic for search index content
	 *
	 * @param	string	$content	The search result content
	 * @return	string
	 */
	public static function preDisplay( string $content ): string
	{
        /* We need to strip the image labels as returned by the AI image scanner */
        return preg_replace('#<i\s+?l="([^<]+?)</i>#', '', $content);
	}
	
	/**
	 * @brief	Created Date
	 */
	public DateTime $createdDate;
	
	/**
	 * @brief	Last Updated Date
	 */
	public DateTime $lastUpdatedDate;
	
	/**
	 * Separator for activity streams - "past hour", "today", etc.
	 *
	 * @param	bool	$createdDate	If TRUE, uses $createdDate, otherwise uses $lastUpdatedDate
	 * @return	string
	 */
	public function streamSeparator( bool $createdDate=TRUE ): string
	{
		$date = $createdDate ? $this->createdDate : $this->lastUpdatedDate;
		
		$now = DateTime::ts( time() );
		$yesterday = clone $now;
		$yesterday = $yesterday->sub( new DateInterval('P1D') );
		$diff = $date->diff( $now );

		if ( $date->format('Y-m-d') == $yesterday->format('Y-m-d') )
		{
			return 'yesterday';
		}
		elseif ( $diff->h < 1 && !$diff->d && !$diff->m )
		{
			return 'past_hour';
		}
		elseif ( $date->format('Y-m-d') == $now->format('Y-m-d') )
		{
			return 'today';
		}
		elseif ( !$diff->y and !$diff->m and $diff->d < 7 )
		{
			return 'last_week';
		}
		else
		{
			return 'earlier';
		}
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse			string				title					Title of search result
	 * @apiresponse			string				content					Content of search result
	 * @apiresponse			string				class					Content class of search result
	 * @apiresponse			int					objectId				Content ID of search result
	 * @apiresponse			string				itemClass				Content item class of search result (if search result is of a content item, this will match class)
	 * @apiresponse			int					itemId					Content item ID of search result (if search result is of a content item, this will match objectId)
	 * @apiresponse			datetime			started					Datetime search result was submitted
	 * @apiresponse			datetime			updated					Datetime search result was last updated
	 * @apiresponse			string				itemUrl					URL to content item
	 * @apiresponse			string				objectUrl				URL to search result item (if search result is of a content item, this will match itemUrl)
	 * @apiresponse			int					reputation				Number of reputation points for search result
	 * @apiresponse			int|null			comments				Number of comments or replies for search result, or NULL if commenting is not supported
	 * @apiresponse			int|null			reviews					Number of reviews for search result, or NULL if reviewing is not supported
	 * @apiresponse			string				container				Title of container of search result
	 * @apiresponse			string				containerUrl			URL to container of search result
	 * @apiresponse			string				author					Author name of search result
	 * @apiresponse			string|null			authorUrl				URL to author's profile, or NULL if search result was submitted by a guest
	 * @apiresponse			string				authorPhoto				URL to author's profile photo
	 * @apiresponse			string				authorPhotoThumbnail	URL to author's profile photo thumbnail
	 * @apiresponse			array				tags					Array of tags associated with the search result
	 */
	public function apiOutput( Member|null $authorizedMember = NULL ): array
	{
		/* @note This is only here to populate the AdminCP reference tab - the array of search result entries is built manually */
		return [];
	}
}