<?php

/**
 * @brief		Converter PhpMyForum Forums Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		6 December 2016
 * @note		Only redirect scripts are supported right now
 */

namespace IPS\convert\Software\Forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\convert\Software;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\Http\Url;
use IPS\Request;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PhpMyForum Forums Converter
 */
class Phpmyforum extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "PhpMyForum";
	}

	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "phpmyforum";
	}

	/**
	 * Content we can convert from this software.
	 *
	 * @return    array|NULL
	 */
	public static function canConvert(): ?array
	{
		return NULL;
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		$url = Request::i()->url();

		if( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'board.php' ) !== FALSE )
		{
			try
			{
				$data = (string) $this->app->getLink( Request::i()->id, array( 'forums', 'forums_forums' ) );
				$item = Forum::load( $data );

				if( $item->can( 'view' ) )
				{
					return $item->url();
				}
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}
		elseif( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'topic.php' ) !== FALSE )
		{
			try
			{
				try
				{
					$data = (string) $this->app->getLink( Request::i()->id, array( 'topics', 'forums_topics' ) );
				}
				catch( OutOfRangeException $e )
				{
					$data = (string) $this->app->getLink( Request::i()->id, array( 'topics', 'forums_topics' ), FALSE, TRUE );
				}
				$item = Topic::load( $data );

				if( $item->canView() )
				{
					return $item->url();
				}
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}

		return NULL;
	}
}