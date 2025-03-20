<?php
/**
 * @brief		prunesearchindex Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 Sep 2017
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\Settings;
use IPS\Task;
use IPS\Task\Exception;
use UnexpectedValueException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * prunesearchindex Task
 */
class prunesearchindex extends Task
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
		/* Prune search index */
		if ( Settings::i()->search_method == 'mysql' and Settings::i()->search_index_timeframe )
		{
			try
			{
				Index::i()->prune( DateTime::create()->sub( new DateInterval( 'P' . Settings::i()->search_index_timeframe . 'D' ) ) );
			}
			catch ( UnexpectedValueException $e ) { }
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