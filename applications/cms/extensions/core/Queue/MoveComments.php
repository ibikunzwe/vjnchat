<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Pages
 * @since		20 Aug 2019
 */

namespace IPS\cms\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\cms\Databases;
use IPS\cms\Records;
use IPS\cms\Records\Comment;
use IPS\Content\Search\Index;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\forums\Topic;
use IPS\forums\Topic\Post;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use OutOfRangeException;
use Throwable;
use UnderflowException;
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
class MoveComments extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_SLOW;
	
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$data['done']	= 0;
		$data['lastId']	= 0;
		
		try
		{
			switch( $data['to'] )
			{
				case 'forums':
					if ( isset( $data['categoryId'] ) )
					{
						$where = array( Db::i()->in( 'comment_record_id', iterator_to_array( Db::i()->select( 'record_id', "cms_custom_database_{$data['databaseId']}", array( "category_id=?", $data['categoryId'] ) ) ) ) );
					}
					else
					{
						$where = array( "comment_database_id=?", $data['databaseId'] );
					}
					
					$data['count'] = Db::i()->select( 'COUNT(*)', 'cms_database_comments', $where )->first();
					break;
				
				case 'pages':
					if ( isset( $data['categoryId'] ) )
					{
						$where = array( "new_topic=? AND " . Db::i()->in( 'topic_id', iterator_to_array( Db::i()->select( 'record_topicid', "cms_custom_database_{$data['databaseId']}", array( "category_id=?", $data['categoryId'] ) ) ) ), 0 );
					}
					else
					{
						$where = array( "new_topic=? AND " . Db::i()->in( 'topic_id', iterator_to_array( Db::i()->select( 'record_topicid', "cms_custom_database_{$data['databaseId']}" ) ) ), 0 );
					}
					
					$data['count'] = Db::i()->select( 'COUNT(*)', 'forums_posts', $where )->first();
					break;
			}
			
			/* If there are no comments, then don't bother. */
			if ( !$data['count'] )
			{
				/* But wait... if we're going from Pages to Forums, we still need to create the topics, so kick off that task instead. */
				if ( $data['to'] === 'forums' )
				{
					Task::queue( 'cms', 'ResyncTopicContent', array( 'databaseId' => $data['databaseId'] ), 3, array( 'databaseId' ) );
				}
				
				return NULL;
			}
		}
		catch( Throwable $e )
		{
			/* Something went wrong - log it and return */
			Log::log( $e, 'cms_move_comments' );
			return NULL;
		}
		
		return $data;
	}

	/**
	 * Run Background Task
	 *
	 * @param	mixed						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws    Task\Queue\OutOfRangeException    Indicates offset doesn't exist and thus task is complete
	 */
	public function run( mixed &$data, int $offset ): int
	{
		/* Okay let's figure out what we're doing. Separate these out to different methods since they can be a little complex. */
		switch( $data['to'] )
		{
			case 'forums':
				$done = $this->_toForums( $data, $offset );
				break;
			
			case 'pages':
				$done = $this->_toPages( $data, $offset );
				break;
		}
		
		if ( $done )
		{
			return $done;
		}
		else
		{
			throw new Task\Queue\OutOfRangeException;
		}
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
		$database = Databases::load( $data['databaseId'] );
		return array( 'text' => Member::loggedIn()->language()->addToStack( 'moving_database_comments', FALSE, array( 'sprintf' => array( $database->_title ) ) ), 'complete' => $data['done'] ? ( round( 100 / $data['count'] * $data['done'], 2 ) ) : 0 );
	}
	
	/**
	 * Move Comments to the Forums
	 *
	 * @param	mixed	$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int		$offset	Offset
	 * @return	int		New offset
	 */
	protected function _toForums( mixed &$data, int $offset ): int
	{
		$done = 0;
		/* This is easy... kind of */
		$database = Databases::load( $data['databaseId'] );
		$commentClass = '\IPS\cms\Records\Comment' . $database->_id;
		if ( isset( $data['categoryId'] ) )
		{
			$where = array( "comment_database_id=? AND comment_id>? AND " . Db::i()->in( 'comment_record_id', iterator_to_array( Db::i()->select( 'primary_id_field', "cms_custom_database_{$database->_id}", array( "category_id=?", $data['categoryId'] ) ) ) ), $database->_id, $data['lastId'] );
		}
		else
		{
			$where = array( "comment_database_id=? AND comment_id>?", $database->_id, $data['lastId'] );
		}
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'cms_database_comments', $where, "comment_id ASC", $this->rebuild ), $commentClass ) AS $row )
		{
			/* Check the record exists */
			try
			{
				/* @var Comment $row */
				$row->item();

				/* Check the topic exists */
				try
				{
					Topic::load( $row->item()->record_topicid );
				}
				catch( OutOfRangeException $e )
				{
					/* If the topic does not exist, reset the value so it can be re-created */
					$row->item()->record_topicid = 0;
					$row->item()->save();
				}

				/* First, create the topic if it doesn't already exist */
				if ( !$row->item()->record_topicid )
				{
					$row->item()->syncTopic(); # Sync Topic will create it for us and link it, while also handling database and category level settings.
				}
			}
			// Exception may be that the item does not exist, or the category does not have syncing enabled
			catch( UnderflowException | OutOfRangeException $e )
			{
				$data['lastId'] = $row->id;
				$data['done']++;
				$done++;
				continue;
			}
			
			/* I'm not sure how to do this properly yet */
			$queued = $row->approved;
			if ( $row->approved == 0 )
			{
				$queued = 1;
			}
			else if ( $row->approved == 1 )
			{
				$queued = 0;
			}
			else if ( $row->approved == -1 )
			{
				$queued = -1;
			}
			
			$post					= new Post;
			$post->author_id			= $row->user;
			$post->append_edit		= $row->edit_show;
			$post->edit_time			= $row->edit_date;
			$post->author_name		= $row->author;
			$post->ip_address		= $row->ip_address;
			$post->post_date			= $row->date;
			$post->post				= $row->post;
			$post->queued			= $queued;
			$post->topic_id			= $row->item()->record_topicid;
			$post->edit_name			= $row->edit_member_name;
			$post->post_edit_reason		= $row->edit_reason ?: '';
			$post->save();

			/* Update post before register */
			Db::i()->update( 'core_post_before_registering', array( 'class' => "\IPS\forums\Topic\Post", 'id' => $post->id ), array( 'class=? and id=?', $commentClass, $row->id ) );

			Index::i()->index( $post );
			
			$data['lastId'] = $row->id;
			$data['done']++;
			$done++;
			
			$row->delete();
			
			/* Are we done? If so, sync the topic */
			if ( !(bool) Db::i()->select( 'COUNT(*)', 'cms_database_comments', array( "comment_database_id=? AND comment_record_id=?", $database->_id, $row->item()->primary_id_field ) )->first() )
			{
				$topic = Topic::load( $row->item()->record_topicid );
				$topic->resyncCommentCounts();
				$topic->save();
				
				$forum = $topic->container();
				$forum->resetCommentCounts();
				$forum->save();
				
				$record		= $row->item();
				$category	= $record->container();
				
				$record->resyncCommentCounts();
				$record->save();
				
				$category->resetCommentCounts();
				$category->save();
			}
		}
		
		return $done;
	}
	
	/**
	 * Move Comments to Pages
	 *
	 * @param	mixed	$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int		$offset	Offset
	 * @return	int		New offset
	 */
	protected function _toPages( mixed &$data, int $offset ): int
	{
		$done = 0;
		
		/* This ones a little more complicated */
		$database = Databases::load( $data['databaseId'] );
		$commentClass = '\IPS\cms\Records\Comment' . $database->_id;
		$recordClass = '\IPS\cms\Records' . $database->_id;
		if ( isset( $data['categoryId'] ) )
		{
			$subquery = iterator_to_array( Db::i()->select( 'record_topicid', "cms_custom_database_{$database->_id}", array( "category_id=?", $data['categoryId'] ) ) );
		}
		else
		{
			$subquery = iterator_to_array( Db::i()->select( 'record_topicid', "cms_custom_database_{$database->_id}" ) );
		}
		foreach( new ActiveRecordIterator( Db::i()->select( '*', 'forums_posts', array( "pid>? AND new_topic=? AND " . Db::i()->in( 'topic_id', $subquery ), $data['lastId'], 0 ), "pid ASC", $this->rebuild ), 'IPS\forums\Topic\Post' ) AS $row )
		{
			try
			{
				/* @var Records $recordClass */
				$record = $recordClass::load( $row->topic_id, 'record_topicid' );

				$approved = $row->queued;
				if ( $row->queued == 1 )
				{
					$approved = 0;
				}
				else if ( $row->queued == 0 )
				{
					$approved = 1;
				}

				$comment = new $commentClass;
				$comment->user = $row->author_id;
				$comment->database_id = $database->_id;
				$comment->record_id = $record->primary_id_field;
				$comment->date = $row->post_date;
				$comment->ip_address = $row->ip_address;
				$comment->post = $row->post;
				$comment->approved = $approved;
				$comment->author = $row->author_name;
				$comment->edit_date = $row->edit_time ?: 0;
				$comment->edit_reason = $row->edit_reason;
				$comment->edit_member_name = $row->edit_name;
				$comment->save();

				/* Update post before register */
				Db::i()->update( 'core_post_before_registering', array( 'class' => $commentClass, 'id' => $comment->id ), array( 'class=? and id=?', "\IPS\forums\Topic\Post", $row->id ) );

				Index::i()->index( $comment );

				$row->delete();

				$data['lastId'] = $row->pid;
				$data['done']++;
				$done++;

				/* If we're no longer keeping the topic, then delete it, but only if we're really done with it (ex, only the first post still exists). Otherwise, we still want the topic linked, we're just not using it for comments anymore. */
				if ( $data['deleteTopics'] and (int)Db::i()->select( 'COUNT(*)', 'forums_posts', array( "topic_id=?", $row->topic_id ) )->first() === 1 )
				{
					Topic::load( $row->topic_id )->delete();
					$record->record_topicid = 0;
				}

				$record->resyncCommentCounts();
				$record->save();

				$category = $record->container();
				$category->resetCommentCounts();
				$category->save();
			}
			catch ( OutOfRangeException ) {} // this may have already been handled
		}
		
		return $done;
	}
}