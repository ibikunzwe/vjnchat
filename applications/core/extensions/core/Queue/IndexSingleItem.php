<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		21 Aug 2018
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\Content\Search\Index;
use IPS\Content\Search\SearchContent;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\IPS;
use IPS\Log;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use OutOfRangeException;
use Throwable;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class IndexSingleItem extends QueueAbstract
{
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		$classname = $data['class'];
		
		Log::debug( "Getting preQueueData for {$classname}", 'index_single_item' );
		
		if ( in_array( 'IPS\Content\Comment', class_parents( $classname ) ) )
		{
			/* @var array $databaseColumnMap */
			$where = array( array( $classname::$databasePrefix . $classname::$databaseColumnMap['item'] . '=?', $data['id'] ) );

			if( Settings::i()->search_method == 'mysql' and Settings::i()->search_index_timeframe )
			{
				if( isset( $classname::$databaseColumnMap['date'] ) )
				{
					$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnMap['date'] . '> ?', DateTime::ts( time() - ( 86400 * Settings::i()->search_index_timeframe ) )->getTimestamp() );
				}
			}
		}
		else
		{
			/* Just in case an item slips in here */
			$where = array( array( $classname::$databasePrefix . $classname::$databaseColumnId . '=?', $data['id'] ) );
		}
		
		$data['count']		= Db::i()->select( 'COUNT(*)', $classname::$databaseTable, $where )->first();
		$data['indexed']	= 0;
		$data['lastId']     = 0;
		$data['where']      = $where;

		if( $data['count'] == 0 )
		{
			return null;
		}

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
		$idColumn = $classname::$databaseColumnId;
		if( !SearchContent::isSearchable( $classname ) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$where = $data['where']; // We do not want to modify the stored where clause

		/* Refine results for ones we haven't done */
		$where[] = array( $classname::$databasePrefix . $classname::$databaseColumnId . '>?', $data['lastId'] );
		
		$done = 0;
		foreach( new ActiveRecordIterator( Db::i()->select( '*', $classname::$databaseTable, $where, $classname::$databasePrefix . $classname::$databaseColumnId . ' ASC', 50 ), $classname ) AS $object )
		{
			/* If this comment is queued for deleting or pbr, skip it */
			if ( !in_array( $object->hidden(), [ -2, -3 ] ) )
			{
				try
				{
					$index = TRUE;
					if ( IPS::classUsesTrait( $object, 'IPS\Content\FuturePublishing' ) AND !$object->isFutureDate() )
					{
						$index = FALSE;
					}
					if ( $index )
					{
						Index::i()->index( $object );
					}
				}
				catch( Exception | Throwable $e )
				{
					Log::log( $e, 'index_single_item' );
				}
			}

			$data['lastId'] = $object->$idColumn;
			$done++;
		}
		
		if ( !$done )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $offset + $done;
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
		
		if ( in_array( 'IPS\Content\Review', class_parents( $class ) ) )
		{
			$lang = Member::loggedIn()->language()->addToStack('reindexing_single_item_reviews', FALSE, array( 'sprintf' => array( $data['url'], $data['title'] ) ) );
		}
		elseif ( in_array( 'IPS\Content\Comment', class_parents( $class ) ) )
		{
			$lang = Member::loggedIn()->language()->addToStack('reindexing_single_item_comments', FALSE, array( 'sprintf' => array( $data['url'], $data['title'] ) ) );
		}
		else
		{
			$lang = Member::loggedIn()->language()->addToStack('reindexing_single_item', FALSE, array( 'sprintf' => array( $data['url'], $data['title'] ) ) );
		}
		
		return array( 'text' => $lang, 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}
}