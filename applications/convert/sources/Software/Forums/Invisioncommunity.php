<?php

/**
 * @brief		Converter Invision Community Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		26 July 2017
 */

namespace IPS\convert\Software\Forums;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Content;
use IPS\convert\App;
use IPS\convert\Library;
use IPS\convert\Software;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Request;
use IPS\Task;
use OutOfRangeException;
use UnderflowException;
use function defined;
use function stristr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Invision Forums Converter
 */
class Invisioncommunity extends Software
{
	/**
	 * @brief 	Whether the versions of IPS4 match
	 */
	public static bool $versionMatch = FALSE;

	/**
	 * @brief 	Whether the database has been required
	 */
	public static bool $dbNeeded = FALSE;

	/**
	 * Constructor
	 *
	 * @param	App	$app	The application to reference for database and other information.
	 * @param	bool				$needDB	Establish a DB connection
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public function __construct( App $app, bool $needDB=TRUE )
	{
		/* Set filename obscuring flag */
		Library::$obscureFilenames = FALSE;

		parent::__construct( $app, $needDB );

		if( $needDB )
		{
			static::$dbNeeded = TRUE;

			try
			{
				$version = $this->db->select( 'app_version', 'core_applications', array( 'app_directory=?', 'core' ) )->first();

				/* We're matching against the human version since the long version can change with patches */
				if ( $version == Application::load( 'core' )->version )
				{
					static::$versionMatch = TRUE;
				}
			}
			catch( DbException $e ) {}

			/* Get parent sauce */
			$this->parent = $this->app->_parent->getSource();
		}
	}

	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return 'Invision Community (' . Application::load( 'core' )->version . ')';
	}

	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "invisioncommunity";
	}

	/**
	 * Content we can convert from this software.
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		if( !static::$versionMatch AND static::$dbNeeded )
		{
			throw new \IPS\convert\Exception( 'convert_invision_mismatch' );
		}

		return array(
			'convertForumsForums'			=> array(
				'table'						=> 'forums_forums',
				'where'						=> NULL,
			),
			'convertForumsTopics'				=> array(
				'table'						=> 'forums_topics',
				'where'						=> NULL,
			),
			'convertForumsPosts'			=> array(
				'table'						=> 'forums_posts',
				'where'						=> NULL,
			),
			'convertForumsTopicsMultimods'	=> array(
				'table'						=> 'forums_topic_mmod',
				'where'						=> NULL,
			),
			'convertForumsRssImports'		=> array(
				'table'						=> 'core_rss_import',
				'where'						=> array( 'rss_import_class=?', 'IPS\\forums\\Topic' ),
			),
			'convertForumsRssImported'		=> array(
				'table'						=> 'core_rss_imported',
				'where'						=> NULL,
			),
			'convertForumsQuestionRatings'	=> array(
				'table'						=> 'forums_question_ratings',
				'where'						=> NULL,
			),
			'convertForumsAnswerRatings'	=> array(
				'table'						=> 'forums_answer_ratings',
				'where'						=> NULL,
			),
			'convertAttachments'			=> array(
				'table'						=> 'core_attachments',
				'where'						=> NULL
			),
		);
	}

	/**
	 * Count Source Rows for a specific step
	 *
	 * @param string $table		The table containing the rows to count.
	 * @param string|array|NULL $where		WHERE clause to only count specific rows, or NULL to count all.
	 * @param bool $recache	Skip cache and pull directly (updating cache)
	 * @return    integer
	 * @throws	\IPS\convert\Exception
	 */
	public function countRows( string $table, string|array|null $where=NULL, bool $recache=FALSE ): int
	{
		switch( $table )
		{
			case 'core_rss_imported':
				try
				{
					return $this->db->select( 'COUNT(*)', 'core_rss_imported', array( 'rss_imported_import_id IN(' . $this->db->select( 'rss_import_id', 'core_rss_import', array( "rss_import_class='IPS\\forums\\Topic'" ) ) . ')' ) )->first();
				}
				catch( Exception $e )
				{
					throw new \IPS\convert\Exception( sprintf( Member::loggedIn()->language()->get( 'could_not_count_rows' ), $table ) );
				}

			default:
				return parent::countRows( $table, $where, $recache );
		}
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
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array(
			'convertAttachments'
		);
	}
	
	/**
	 * Get More Information
	 *
	 * @param string $method	Method name
	 * @return    array|null
	 */
	public function getMoreInfo( string $method ): ?array
	{
		$return = array();

		switch( $method )
		{
			case 'convertAttachments':
				Member::loggedIn()->language()->words["upload_path"] = Member::loggedIn()->language()->addToStack( 'convert_invision_upload_input' );
				Member::loggedIn()->language()->words["upload_path_desc"] = Member::loggedIn()->language()->addToStack( 'convert_invision_upload_input_desc' );
				$return[ $method ] = array(
					'upload_path'				=> array(
						'field_class'		=> 'IPS\\Helpers\\Form\\Text',
						'field_default'		=> $this->parent->app->_session['more_info']['convertEmoticons']['upload_path'] ?? NULL,
						'field_required'	=> TRUE,
						'field_extra'		=> array(),
						'field_hint'		=> Member::loggedIn()->language()->addToStack('convert_invision_upload_path'),
						'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
					)
				);
				break;
		}

		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}

	/**
	 * Possible Parent Conversions
	 *
	 * @return    array|null
	 */
	public static function parents(): ?array
	{
		return array( 'core' => array( 'invisioncommunity' ) );
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
		Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\forums\Topic' ), 3, array( 'class' ) );
		Task::queue( 'convert', 'RebuildFirstPostIds', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		Task::queue( 'convert', 'DeleteEmptyTopics', array( 'app' => $this->app->app_id ), 5, array( 'app' ) );
		Task::queue( 'convert', 'InvisionCommunityRebuildContent', array( 'app' => $this->app->app_id, 'link' => 'forums_posts', 'class' => 'IPS\forums\Topic\Post' ), 2, array( 'app', 'link', 'class' ) );

		/* Caches */
		Task::queue( 'convert', 'RebuildTagCache', array( 'app' => $this->app->app_id, 'link' => 'forums_topics', 'class' => 'IPS\forums\Topic' ), 3, array( 'app', 'link', 'class' ) );

		/* Rebuild Leaderboard */
		Task::queue( 'core', 'RebuildReputationLeaderboard', array(), 4 );
		Db::i()->delete('core_reputation_leaderboard_history');

		return array( "f_forum_last_post_data", "f_rebuild_posts", "f_recounting_forums", "f_recounting_topics", "f_topic_tags_recount" );
	}

	/**
	 * Convert question ratings
	 *
	 * @return	void
	 */
	public function convertForumsAnswerRatings() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'forums_answer_ratings', 'id'  ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'forums_answer_ratings', 'forums' );

			$libraryClass->convertForumsAnswerRating( $row );
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert forums
	 *
	 * @return	void
	 */
	public function convertForumsForums() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'forums_forums', 'id'  ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'forums_forums', 'forums' );

			/* Add name after clearing other columns */
			$row['name'] = $this->parent->getWord( 'forums_forum_' . $row['id'] );
			$row['description'] = $this->parent->getWord( 'forums_forum_' . $row['id'] . '_desc' );

			/* Rename bit options so we can pass them through the conversion method */
			$row['ips_forums_bitoptions'] = $row['forums_bitoptions'];
			unset( $row['forums_bitoptions'] );

			$libraryClass->convertForumsForum( $row );

			/* Convert Follows */
			foreach( $this->db->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', 'forums', 'forum', $row['id'] ) ) as $follow )
			{
				/* Remove non-standard columns */
				$this->parent->unsetNonStandardColumns( $follow, 'core_follow', 'core' );

				/* Change follow data */
				$follow['follow_rel_id_type'] = 'forums_forums';

				$libraryClass->convertFollow( $follow );
			}

			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert topics
	 *
	 * @return	void
	 */
	public function convertForumsTopics() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'tid' );

		foreach( $this->fetch( 'forums_topics', 'tid' ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'forums_topics', 'forums' );

			$libraryClass->convertForumsTopic( $row );

			/* Convert Follows */
			foreach( $this->db->select( '*', 'core_follow', array( 'follow_app=? AND follow_area=? AND follow_rel_id=?', 'forums', 'topic', $row['tid'] ) ) as $follow )
			{
				/* Remove non-standard columns */
				$this->parent->unsetNonStandardColumns( $follow, 'core_follow', 'core' );

				/* Change follow data */
				$follow['follow_rel_id_type'] = 'forums_topics';

				$libraryClass->convertFollow( $follow );
			}

			/* Convert Ratings */
			foreach( $this->db->select( '*', 'core_ratings', array( 'class=? AND item_id=?', 'IPS\\forums\\Topic', $row['tid'] ) ) as $rating )
			{
				/* Remove non-standard columns */
				$this->parent->unsetNonStandardColumns( $rating, 'core_ratings', 'core' );

				/* Change rating data */
				$rating['item_link'] = 'forums_topics';

				$libraryClass->convertRating( $rating );
			}

			/* Convert Tags */
			foreach( $this->db->select( '*', 'core_tags', array( 'tag_meta_app=? AND tag_meta_area=? AND tag_meta_id=?', 'forums', 'forums', $row['tid'] ) ) as $tag )
			{
				/* Remove non-standard columns */
				$this->parent->unsetNonStandardColumns( $tag, 'core_tags', 'core' );

				$libraryClass->convertTag( $tag );
			}

			$libraryClass->setLastKeyValue( $row['tid'] );
		}
	}

	/**
	 * Convert posts
	 *
	 * @return	void
	 */
	public function convertForumsPosts() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'pid' );

		foreach( $this->fetch( 'forums_posts', 'pid' ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'forums_posts', 'forums' );

			/* Rename bit options so we can pass them through the conversion method */
			$row['ips_post_bwoptions'] = $row['post_bwoptions'];
			unset( $row['post_bwoptions'] );

			$libraryClass->convertForumsPost( $row );

			/* Convert Edit History */
			foreach( $this->db->select( '*', 'core_edit_history', array( 'class=? AND comment_id=?', 'IPS\\forums\\Topic\\Post', $row['pid'] ) ) as $history )
			{
				/* Remove non-standard columns */
				$this->parent->unsetNonStandardColumns( $history, 'core_edit_history', 'core' );

				$libraryClass->convertEditHistory( $history );
			}

			/* Reputation */
			foreach( $this->db->select( '*', 'core_reputation_index', array( "app=? AND type=? AND type_id=?", 'forums', 'pid', $row['pid'] ) ) AS $rep )
			{
				/* Remove non-standard columns */
				$this->parent->unsetNonStandardColumns( $rep, 'core_reputation_index', 'core' );

				$libraryClass->convertReputation( $rep );
			}

			$libraryClass->setLastKeyValue( $row['pid'] );
		}
	}

	/**
	 * Convert question ratings
	 *
	 * @return	void
	 */
	public function convertForumsQuestionRatings() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'forums_question_ratings', 'id'  ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'forums_question_ratings', 'forums' );

			$libraryClass->convertForumsQuestionRating( $row );
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert topics mmod
	 *
	 * @return	void
	 */
	public function convertForumsTopicsMultimods() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'mm_id' );

		foreach( $this->fetch( 'forums_topic_mmod', 'mm_id'  ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'forums_topic_mmod', 'forums' );

			/* Add name after clearing other columns */
			$row['mm_name'] = $this->parent->getWord( 'forums_mmod_' . $row['mm_id'] );

			$libraryClass->convertForumsTopicMultimod( $row );
			$libraryClass->setLastKeyValue( $row['mm_id'] );
		}
	}

	/**
	 * Convert rss imports
	 *
	 * @return	void
	 */
	public function convertForumsRssImports() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'rss_import_id' );

		foreach( $this->fetch( 'core_rss_import', 'rss_import_id', array( 'rss_import_class=?', 'IPS\\forums\\Topic' ) ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'core_rss_import', 'core' );

			/* JSON Decode settings (we expect an array) */
			$row['rss_import_settings'] = json_decode( $row['rss_import_settings'], TRUE );

			$libraryClass->convertForumsRssImport( $row );
			$libraryClass->setLastKeyValue( $row['rss_import_id'] );
		}
	}

	/**
	 * Convert rss imported
	 *
	 * @return	void
	 */
	public function convertForumsRssImported() : void
	{
		$libraryClass = $this->getLibrary();

		foreach( $this->fetch( 'core_rss_imported', 'rss_imported_content_id', array( 'rss_imported_id IN(' . Db::i()->select( 'rss_import_id', 'core_rss_import', array( "rss_import_class='IPS\\forums\\Topic'" ) ) . ')' ) ) AS $row )
		{
			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'core_rss_imported', 'core' );

			$libraryClass->convertForumsRssImported( $row );
		}
	}

	/**
	 * Convert attachments
	 *
	 * @return	void
	 */
	public function convertAttachments() : void
	{
		$libraryClass = $this->getLibrary();
		$libraryClass::setKey( 'attach_id' );

		foreach( $this->fetch( 'core_attachments', 'attach_id' ) AS $row )
		{
			try
			{
				$attachmentMap = $this->db->select( '*', 'core_attachments_map', array( 'attachment_id=? AND location_key=?', $row['attach_id'], 'forums_Forums' ) )->first();
			}
			catch( UnderflowException $e )
			{
				$libraryClass->setLastKeyValue( $row['attach_id'] );
				continue;
			}

			/* Remove non-standard columns */
			$this->parent->unsetNonStandardColumns( $row, 'core_attachments' );
			$this->parent->unsetNonStandardColumns( $attachmentMap, 'core_attachments_map' );

			/* Remap rows */
			$name = explode( '/', $row['attach_location'] );
			$row['attach_container'] = isset( $name[1] ) ? $name[0] : NULL;
			$thumbName = explode( '/', $row['attach_thumb_location'] );
			$row['attach_thumb_container'] = isset( $thumbName[1] ) ? $thumbName[0] : NULL;

			$filePath = $this->app->_session['more_info']['convertAttachments']['upload_path'] . '/' . $row['attach_location'];
			$thumbnailPath = $this->app->_session['more_info']['convertAttachments']['upload_path'] . '/' . $row['attach_thumb_location'];

			unset( $row['attach_file'] );

			$libraryClass->convertAttachment( $row, $attachmentMap, $filePath, NULL, $thumbnailPath );		
			$libraryClass->setLastKeyValue( $row['attach_id'] );
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

		if( !stristr( $url->data[ Url::COMPONENT_PATH ], 'ic-merge-' . $this->app->_parent->app_id ) )
		{
			return NULL;
		}

		/* account for non-mod_rewrite links */
		$searchOn = stristr( $url->data[ Url::COMPONENT_PATH ], 'index.php' ) ? $url->data[ Url::COMPONENT_QUERY ] : $url->data[ Url::COMPONENT_PATH ];

		if( preg_match( '#/(forum|topic)/([0-9]+)-(.+?)#i', $searchOn, $matches ) )
		{
			$oldId	= (int) $matches[2];

			switch( $matches[1] )
			{
				case 'forum':
					$class	= '\IPS\forums\Forum';
					$types	= array( 'forums', 'forums_forums' );
				break;

				case 'topic':
					$class	= '\IPS\forums\Topic';
					$types	= array( 'topics', 'forums_topics' );

					if( Request::i()->do == 'findComment' AND Request::i()->comment )
					{
						$class	= '\IPS\forums\Topic\Post';
						$types	= array( 'posts', 'forums_posts' );
						$oldId	= Request::i()->comment;
					}
				break;
			}
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