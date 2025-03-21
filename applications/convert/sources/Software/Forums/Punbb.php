<?php

/**
 * @brief		Converter Punbb Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert\Software\Forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Content;
use IPS\convert\Software;
use IPS\convert\Software\Core\Punbb as PunbbCore;
use IPS\Http\Url;
use IPS\Node\Model;
use IPS\Request;
use IPS\Task;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PunBB Forums Converter
 */
class Punbb extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "PunBB (1.x)";
	}
	
	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "punbb";
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertForumsForums'	=> array(
				'table'		=> 'forums',
				'where'		=> NULL,
			),
			'convertForumsTopics'	=> array(
				'table'		=> 'topics',
				'where'		=> NULL
			),
			'convertForumsPosts'	=> array(
				'table'		=> 'posts',
				'where'		=> NULL
			),
		);
	}

	/**
	 * Requires Parent
	 *
	 * @return    boolean
	 */
	public static function requiresParent(): bool
	{
		return TRUE;
	}
	
	/**
	 * Possible Parent Conversions
	 *
	 * @return    array|null
	 */
	public static function parents(): ?array
	{
		return array( 'core' => array( 'punbb' ) );
	}

	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		/* Content Rebuilds */
		Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\forums\Forum', 'count' => 0 ), 4, array( 'class' ) );
		Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'forums_posts', 'class' => 'IPS\forums\Topic\Post' ), 2, array( 'app', 'link', 'class' ) );
		Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\forums\Topic' ), 3, array( 'class' ) );
		Task::queue( 'convert', 'RebuildFirstPostIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		Task::queue( 'convert', 'DeleteEmptyTopics', array( 'app' => $this->app->app_id ), 5, array( 'app' ) );
		
		return array( "f_forum_last_post_data", "f_rebuild_posts", "f_recounting_forums", "f_recounting_topics" );
	}

	/**
	 * Pre-process content for the Invision Community text parser
	 *
	 * @param	string $post The post
	 * @param	string|null $className Content Classname passed by post-conversion rebuild
	 * @param	int|null $contentId Content ID passed by post-conversion rebuild
	 * @return	string			The converted post
	 */
	public static function fixPostData( string $post, ?string $className=null, ?int $contentId=null ): string
	{
		return PunbbCore::fixPostData( $post, $className, $contentId );
	}
	
	/**
	 * Convert forums
	 *
	 * @return	void
	 */
	public function convertForumsForums(): void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'forums', 'id' ) AS $row )
		{
			/* PunBB has separate concepts of categories versus forums - normally, we would do these in separate processes but that isn't really all that necessary */
			try
			{
				$catId = $this->app->getLink( '1000' . $row['cat_id'], 'forums_forums' );
			}
			catch( OutOfRangeException $e )
			{
				try
				{
					$category = $this->db->select( '*', 'categories', array( "id=?", $row['cat_id'] ) )->first();
					
					$libraryClass->convertForumsForum( array(
						'id'			=> '1000' . $category['id'],
						'name'			=> $category['cat_name'],
						'parent_id'		=> -1,
						'position'		=> $category['disp_position']
					) );
				}
				catch( UnderflowException $e ) {}
			}
			
			/* They don't store the last poster ID. Makes me sad. */
			$last_poster_id = 0;
			try
			{
				/* Better hope they haven't changed their name */
				$last_poster_id = $this->db->select( 'id', 'users', array( "username=?", $row['last_poster'] ) )->first();
			}
			catch( UnderflowException $e ) {}
			
			$info = array(
				'id'				=> $row['id'],
				'name'				=> $row['forum_name'],
				'description'		=> $row['forum_desc'],
				'topics'			=> $row['num_topics'],
				'posts'				=> $row['num_posts'],
				'last_post'			=> $row['last_post'],
				'last_poster_id'	=> $last_poster_id,
				'last_poster_name'	=> $row['last_poster'],
				'parent_id'			=> ( $row['parent_forum_id'] == 0 ) ? '1000' . $row['cat_id'] : $row['parent_forum_id'],
				'position'			=> $row['disp_position'],
				'redirect_url'		=> $row['redirect_url'],
			);
			
			$libraryClass->convertForumsForum( $info );
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	/**
	 * Convert topics
	 *
	 * @return	void
	 */
	public function convertForumsTopics(): void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'topics', 'id' ) AS $row )
		{
			/* sigh */
			$last_poster_id = 0;
			try
			{
				$last_poster_id = $this->db->select( 'id', 'users', array( "username=?", $row['last_poster'] ) )->first();
			}
			catch( UnderflowException $e ) {}
			
			$starter_id = 0;
			try
			{
				$starter_id = $this->db->select( 'id', 'users', array( "username=?", $row['poster'] ) )->first();
			}
			catch( UnderflowException $e ) {}
			
			$moved_to = NULL;
			if ( $row['moved_to'] )
			{
				try
				{
					$moved_to_forum = $this->db->select( 'forum_id', 'topics', array( "id=?", $row['moved_to'] ) )->first();
					$moved_to = [ $row['moved_to'], $moved_to_forum ];
				}
				catch( UnderflowException $e ) {}
			}
			
			$info = array(
				'tid'				=> $row['id'],
				'title'				=> $row['subject'],
				'forum_id'			=> $row['forum_id'],
				'state'				=> ( $row['closed'] ) ? 'closed' : 'open',
				'posts'				=> $row['num_replies'],
				'starter_id'		=> $starter_id,
				'start_date'		=> $row['posted'],
				'last_poster_id'	=> $last_poster_id,
				'last_post'			=> $row['last_post'],
				'starter_name'		=> $row['poster'],
				'last_poster_name'	=> $row['last_poster'],
				'pinned'			=> ( $row['sticky'] OR $row['announcement'] ) ? 1 : 0,
				'moved_to'			=> $moved_to,
				'moved_on'			=> ( !is_null( $moved_to ) ) ? $row['posted'] : 0,
			);
			
			$libraryClass->convertForumsTopic( $info );
			
			foreach( $this->db->select( '*', 'subscriptions', array( "topic_id=?", $row['id'] ) ) AS $follow )
			{
				$libraryClass->convertFollow( array(
					'follow_app'			=> 'forums',
					'follow_area'			=> 'topic',
					'follow_rel_id'			=> $row['id'],
					'follow_rel_id_type'	=> 'forums_topics',
					'follow_member_id'		=> $follow['user_id'],
					'follow_is_anon'		=> 0,
					'follow_added'			=> time(),
					'follow_notify_do'		=> 1,
					'follow_notify_meta'	=> '',
					'follow_notify_freq'	=> 'immediate',
					'follow_notify_sent'	=> 0,
					'follow_visible'		=> 1,
				) );
			}
			
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}
	
	/**
	 * Convert posts
	 *
	 * @return	void
	 */
	public function convertForumsPosts(): void
	{
		$libraryClass = $this->getLibrary();
		
		$libraryClass::setKey( 'id' );
		
		foreach( $this->fetch( 'posts', 'id' ) AS $row )
		{
			$info = array(
				'pid'			=> $row['id'],
				'topic_id'		=> $row['topic_id'],
				'post'			=> $row['message'],
				'append_edit'	=> ( $row['edited'] ) ? 1 : 0,
				'edit_time'		=> $row['edited'],
				'author_id'		=> $row['poster_id'], # I half expected them to not store this
				'author_name'	=> $row['poster'],
				'ip_address'	=> $row['poster_ip'],
				'post_date'		=> $row['posted'],
				'edit_name'		=> $row['edited_by'],
			);
			
			$libraryClass->convertForumsPost( $info );
			
			$libraryClass->setLastKeyvalue( $row['id'] );
		}
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		$url = Request::i()->url();

		if( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'viewtopic.php' ) !== FALSE )
		{
			if( isset( Request::i()->pid ) )
			{
				$class	= '\IPS\forums\Topic\Post';
				$types	= array( 'posts', 'forums_posts' );
				$oldId	= Request::i()->pid;
			}
			else
			{
				$class	= '\IPS\forums\Topic';
				$types	= array( 'topics', 'forums_topics' );
				$oldId	= Request::i()->id;
			}
		}
		elseif( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'viewforum.php' ) !== FALSE )
		{
			$class	= '\IPS\forums\Forum';
			$types	= array( 'forums', 'forums_forums' );
			$oldId	= Request::i()->id;
		}

		if( isset( $class ) )
		{
			try
			{
				try
				{
					$data = (string) $this->app->getLink( $oldId, $types );
				}
				catch( OutOfRangeException $e )
				{
					$data = (string) $this->app->getLink( $oldId, $types, FALSE, TRUE );
				}
				$item = $class::load( $data );

				if( $item instanceof Content )
				{
					if( $item->canView() )
					{
						return $item->url();
					}
				}
				elseif( $item instanceof Model )
				{
					if( $item->can( 'view' ) )
					{
						return $item->url();
					}
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