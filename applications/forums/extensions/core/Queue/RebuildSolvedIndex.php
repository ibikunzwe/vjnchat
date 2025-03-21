<?php
/**
 * @brief		Background Task: Rebuild Solved Index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		20 February 2020
 */

namespace IPS\forums\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\forums\Topic\Post;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use OutOfRangeException;
use UnderflowException;
use function defined;
use const IPS\REBUILD_QUICK;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild Solved Index
 */
class RebuildSolvedIndex extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		try
		{
			$data['count'] = Db::i()->select( 'MAX(tid)', 'forums_topics', array( 'topic_answered_pid > 0' ) )->first();
			$data['realCount'] = Db::i()->select( 'COUNT(*)', 'forums_topics', array( 'topic_answered_pid > 0' ) )->first();
		}
		catch( Exception $e )
		{
			throw new OutOfRangeException;
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
	 * @throws	\IPS\Task\Queue\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		if ( !class_exists( 'IPS\forums\Topic' ) OR !Application::appisEnabled( 'forums' ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$last = NULL;
		
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'forums_topics', array( "tid>? and topic_answered_pid > 0", $offset ), "tid ASC", array( 0, REBUILD_QUICK ) ), 'IPS\forums\Topic' ) AS $topic )
		{
			/* I told him we already got one! */
			try 
			{
				Db::i()->select( '*', 'core_solved_index', array( 'comment_class=? and item_id=? and comment_id=? AND type=?', 'IPS\\forums\\Topic\\Post', $topic->tid, $topic->topic_answered_pid, 'solved' ) )->first();
			}
			catch( UnderflowException $e )
			{
				/* No? Best slap it in then */
				try 
				{
					$comment = Post::load( $topic->topic_answered_pid );
					
					Db::i()->insert( 'core_solved_index', array(
						'member_id' => $comment->author()->member_id,
						'app'	=> 'forums',
						'comment_class' => 'IPS\\forums\\Topic\\Post',
						'comment_id' => $comment->pid,
						'item_id'	 => $topic->tid,
						'solved_date' => $comment->post_date, // We don't have the real solve date so this will have to do
						'type' => 'solved'
					) );
				}
				catch( Exception $e ) { }
			}
			
			$data['completed']++;
			$last = $topic->tid;
		}

		if( $last === NULL )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $last;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( mixed $data, int $offset ): array
	{
		return array( 'text' =>  Member::loggedIn()->language()->addToStack('queue_rebuilding_solved_posts'), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['completed'], 2 ) ) : 100 );
	}	

}