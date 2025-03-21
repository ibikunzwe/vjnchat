<?php
/**
 * @brief		deletecontent Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		14 Feb 2017
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * deletecontent Task
 */
class deletecontent extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	Exception
	 */
	public function execute() : mixed
	{
		$this->runUntilTimeout( function()
		{
			$timeago	= DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->dellog_retention_period . 'D' ) );
			$count		= 0;

			foreach( new ActiveRecordIterator( Db::i()->select( '*', 'core_deletion_log', array( "dellog_deleted_date<?", $timeago->getTimestamp() ), 'dellog_deleted_date ASC', array( 0, 20 ) ), 'IPS\core\DeletionLog' ) AS $log )
			{
				try
				{
					$class		= $log->content_class;
					if ( class_exists ( $class ) )
					{
						$content = $class::load( $log->content_id );

						/* Make sure that the content is flagged for deletion */
						if( $content->hidden() !== -2 )
						{
							throw new OutOfRangeException;
						}

						$content->delete();
					}
				}
				/* If the content is gone already, don't let an uncaught exception bubble up...just remove the deletion log orphaned entry */
				catch( OutOfRangeException $e ){}
				
				$log->delete();
				$count++;
			}
			
			return (bool) $count;
		} );
		
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