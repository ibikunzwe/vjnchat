<?php
/**
 * @brief		Background Task: Rebuild reputation index
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		13 Jun 2014
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\IPS;
use IPS\Member;
use OutOfRangeException;
use function count;
use function defined;
use const IPS\REBUILD_NORMAL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild Reputation Index
 */
class RebuildReputationIndex extends QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_NORMAL;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];

		/* Make sure there's even content to parse */
		if( !IPS::classUsesTrait( $classname, 'IPS\Content\Reactable' ) )
		{
			$data['count'] = 0;
		}
		else
		{
			try
			{
				$data['count']		= Db::i()->select( 'MAX( id )', 'core_reputation_index', array( 'app=? and type=?', $classname::$application, $classname::reactionType() ) )->first();
				$data['realCount']	= Db::i()->select( 'COUNT(*)', 'core_reputation_index', array( 'app=? and type=?', $classname::$application, $classname::reactionType() ) )->first();
			}
			catch( Exception $ex )
			{
				throw new OutOfRangeException;
			}
		}

		if( $data['count'] == 0 )
		{
			return null;
		}

		$data['indexed']	= 0;

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
		$classname = $data['class'];
        $exploded = explode( '\\', $classname );
        if ( !class_exists( $classname ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		/* Make sure there's even content to parse */
		if( !IPS::classUsesTrait( $classname, 'IPS\Content\Reactable' ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}

		$last     = NULL;
		foreach( Db::i()->select( '*', 'core_reputation_index', array( 'app=? and type=? and id > ?', $classname::$application, $classname::reactionType(), $offset ), 'id asc', array( 0, $this->rebuild ) ) as $row )
		{
			$data['indexed']++;
			
			$update = array();
			try
			{
				$post = $classname::load( $row['type_id'] );
				if ( $post->mapped('author') and $post->mapped('author') != $row['member_received'] and $post->mapped('author') != -1 )
				{
					$update['member_received'] = $post->mapped('author');
				}
				
				if ( ! $row['item_id'] )
				{
					$row['item_id'] = (int) $post->mapped('item');
					$update['item_id'] = $row['item_id'];
				}
			}
			catch( OutOfRangeException $ex )
			{
				$last = $row['id'];
				continue;
			}
			
			$update['class_type_id_hash'] = md5( $classname . ':' . $row['type_id'] );
			
			if ( ! $row['rep_class'] )
			{
				$update['rep_class'] = $classname;
			}
				
			if ( count( $update ) )
			{
				Db::i()->update( 'core_reputation_index', $update, array( 'id=?', $row['id'] ) );
			}
				
			$last = $row['id'];
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
		$class = $data['class'];
        $exploded = explode( '\\', $class );
        if ( !class_exists( $class ) or !Application::appIsEnabled( $exploded[1] ) )
		{
			throw new OutOfRangeException;
		}
		
		return array( 'text' => Member::loggedIn()->language()->addToStack('rebuilding_reputation', FALSE, array( 'sprintf' => array( Member::loggedIn()->language()->addToStack( $class::$title, FALSE, array( 'strtolower' => TRUE ) ) ) ) ), 'complete' => $data['realCount'] ? ( round( 100 / $data['realCount'] * $data['indexed'], 2 ) ) : 100 );
	}
}