<?php

/**
 * @brief        QueueAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/21/2023
 */

namespace IPS\Extensions;

use Exception;
use IPS\Db;
use IPS\Patterns\ActiveRecord;
use IPS\Task\Queue\OutOfRangeException as QueueOutOfRangeException;
use OutOfRangeException;
use const IPS\REBUILD_NORMAL;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class QueueAbstract
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public int $rebuild	= REBUILD_NORMAL;

	/**
	 *
	 *
	 * @param string $classname
	 * @return array
	 * @throws OutOfRangeException
	 */
	protected function getCountDataFromClass( string $classname ) : array
	{
		$data = [];

		/* Check table size before running a count query */
		if( Db::i()->recommendManualQuery( $classname::$databaseTable ) )
		{
			$data['count'] = $data['realCount'] = Db::i()->getTableData( $classname::$databaseTable )['rows'];
		}
		else
		{
			try
			{
				/* @var ActiveRecord $classname */
				$data['count']		= $classname::db()->select( 'MAX(' . $classname::$databasePrefix . $classname::$databaseColumnId . ')', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();
				$data['realCount']	= $classname::db()->select( 'COUNT(*)', $classname::$databaseTable, ( is_subclass_of( $classname, 'IPS\Content\Comment' ) ) ? $classname::commentWhere() : array() )->first();
			}
			catch( Exception $ex )
			{
				throw new OutOfRangeException;
			}
		}

		return $data;
	}

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	abstract public function preQueueData( array $data ): ?array;

	/**
	 * Run Background Task
	 *
	 * @param	array						$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int							$offset	Offset
	 * @return	int							New offset
	 * @throws	QueueOutOfRangeException	        Indicates offset doesn't exist and thus task is complete
	 */
	abstract public function run( array &$data, int $offset ): int;

	/**
	 * Get Progress
	 *
	 * @param	array					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	abstract public function getProgress( array $data, int $offset ): array;

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{

	}
}