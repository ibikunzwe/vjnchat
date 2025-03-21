<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	convert
 * @since		26 Feb 2016
 */

namespace IPS\convert\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\forums\Topic\ArchivedPost;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task\Queue\OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class RebuildFirstPostIds extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data	Data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		try
		{
			$data['count'] = Db::i()->select( 'count(tid)', 'forums_topics' )->first();
		}
		catch( Exception $e )
		{
			throw new \OutOfRangeException;
		}

		if( $data['count'] == 0 )
		{
			return NULL;
		}

		$data['completed'] = 0;

		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		if ( !class_exists( 'IPS\forums\Topic' ) OR !Application::appisEnabled( 'forums' ) )
		{
			throw new OutOfRangeException;
		}

		$last = NULL;

		$topicIdsToReset			= array();
		$archivedTopicIdsToReset	= array();
		$firstPostIds				= array();
		$firstPostArchivedIds		= array();

		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'forums_topics', array( "tid>?", $offset ), "tid ASC", array( 0, REBUILD_SLOW ) ), 'IPS\forums\Topic' ) AS $topic )
		{
			if ( !$topic->isArchived() )
			{
				try
				{
					/* Set first post */
					$topic->topic_firstpost = Db::i()->select( 'pid', 'forums_posts', array( 'topic_id=?', $topic->tid ), 'post_date ASC', 1 )->first();
					$topic->save();

					/* Reset new_topic value for topic */
					$topicIdsToReset[]	= $topic->tid;
					$firstPostIds[]		= $topic->topic_firstpost;
				}
				/* Underflow exception may occur if the topic doesn't have any posts for an unknown reason */
				catch( UnderflowException $e ) {}
			}
			else
			{
				try
				{
					/* Set first post */
					$topic->topic_firstpost = ArchivedPost::db()->select( 'archive_id', 'forums_archive_posts', array( "archive_topic_id=?", $topic->tid ), "archive_content_date ASC", 1 )->first();
					$topic->save();

					/* Reset new_topic value for topic */
					$archivedTopicIdsToReset[]	= $topic->tid;
					$firstPostArchivedIds[]		= $topic->topic_firstpost;
				}
				/* Underflow exception may occur if the topic doesn't have any posts for an unknown reason */
				catch( UnderflowException $e ) {}
			}

			$last = $topic->tid;
			$data['completed']++;
		}

		/* Reset flags as needed */
		if( count( $topicIdsToReset ) )
		{
			Db::i()->update( 'forums_posts', array( 'new_topic' => 0 ), array( 'topic_id IN(' . implode( ',', $topicIdsToReset ) . ')' ) );
		}

		if( count( $firstPostIds ) )
		{
			Db::i()->update( 'forums_posts', array( 'new_topic' => 1 ), array( 'pid IN(' . implode( ',', $firstPostIds ) . ')' ) );
		}

		if( count( $archivedTopicIdsToReset ) )
		{
			ArchivedPost::db()->update( 'forums_archive_posts', array( 'archive_is_first' => 0 ), array( 'archive_topic_id IN(' . implode( ',', $archivedTopicIdsToReset ) . ')' ) );
		}

		if( count( $firstPostArchivedIds ) )
		{
			ArchivedPost::db()->update( 'forums_archive_posts', array( 'archive_is_first' => 1 ), array( 'archive_id IN(' . implode( ',', $firstPostArchivedIds ) . ')' ) );
		}

		if( $last === NULL )
		{
			throw new OutOfRangeException;
		}

		return $last;
	}

	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array	Text explaining task and percentage complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' =>  Member::loggedIn()->language()->addToStack('queue_rebuilding_new_topic_flag'), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $data['completed'], 2 ) ) : 100 );
	}
}