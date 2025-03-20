<?php
/**
 * @brief		publish Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 Oct 2014
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content;
use IPS\Db;
use IPS\IPS;
use IPS\Task;
use IPS\Task\Exception;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0') . ' 403 Forbidden' );
	exit;
}

/**
 * publish Task
 */
class publish extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	string|null	Message to log or NULL
	 * @throws	Exception
	 */
	public function execute() : mixed
	{
		$types = array();

		foreach ( Content::routedClasses( FALSE, FALSE, TRUE ) as $class )
		{
			if( IPS::classUsesTrait( $class, 'IPS\Content\FuturePublishing' ) )
			{
				$types[] = $class;
			}
		}

		if( count( $types ) )
		{
			foreach( $types as $class )
			{
				/* @var $databaseColumnMap array */
				foreach( Db::i()->select( '*', $class::$databaseTable, array( $class::$databasePrefix . $class::$databaseColumnMap['is_future_entry'] . '=1 and ' . $class::$databasePrefix . $class::$databaseColumnMap['date'] . ' <= ' . time() ) ) as $row )
				{
					$obj = $class::constructFromData( $row );
					$obj->publish();
				}
			}
		}

		return NULL;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}