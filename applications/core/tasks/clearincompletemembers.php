<?php
/**
 * @brief		clearincompletemembers Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		08 May 2019
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Task;
use IPS\Task\Exception;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * clearincompletemembers Task
 */
class clearincompletemembers extends Task
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
		/* Delete cancelled registration members */
		foreach( Db::i()->select( 'member_id', 'core_validating', array( 'do_not_delete=0 and reg_cancelled > 0 and reg_cancelled < ?', DateTime::create()->sub( new DateInterval( 'PT1H' ) )->getTimestamp() ) ) as $id )
		{
			$member = Member::load( $id );

			if( $member->member_id )
			{
				$member->delete();
			}
			else
			{
				Db::i()->delete( 'core_validating', array( 'member_id=?', $id ) );
			}
		}
		
		/* Delete any incompleted accounts (for example, someone has clicked "Sign in with Twitter" and never provided a username or email */
		foreach ( new ActiveRecordIterator( Db::i()->select( '*', 'core_members', array( array( '(name=? OR email=?)', '', '' ), array( '! (members_bitoptions2 & 16384 ) AND joined<? AND (last_visit=0 OR last_visit IS NULL)', DateTime::create()->sub( new DateInterval( 'PT1H' ) )->getTimestamp() ) ) ), 'IPS\Member' ) as $incompleteMember )
		{
			$incompleteMember->delete();
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