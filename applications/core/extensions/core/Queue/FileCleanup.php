<?php
/**
 * @brief		Background Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		05 Feb 2021
 */

namespace IPS\core\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Db;
use IPS\Extensions\QueueAbstract;
use IPS\File;
use IPS\Log;
use IPS\Member;
use OutOfRangeException;
use function defined;
use function is_array;
use function is_numeric;
use const IPS\REBUILD_SLOW;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task
 */
class FileCleanup extends QueueAbstract
{
	/**
	 * @brief Number of thumbnails to build per cycle
	 */
	public int $perCycle	= REBUILD_SLOW;

	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array|null
	 */
	public function preQueueData( array $data ): ?array
	{
		/* Make sure we have the minimal amount of data we need */
		if( !$this->canBeRun( $data ) )
		{
			throw new OutOfRangeException;
		}

		$data['maxId']	= NULL;

		if( isset( $data['primaryId'] ) )
		{
			$data['maxId']			= Db::i()->select( 'MAX(' . $data['primaryId'] . ')', $data['table'] )->first();
		}
		
		$data['count']			= Db::i()->select( 'count(*)', $data['table'] )->first();
		$data['deleted']		= 0;

		/* Convert the storage extension to get the configuration ID */
		if( !is_numeric( $data['storageExtension'] ) )
		{
			$data['storageExtension'] = (int) File::getClass( $data['storageExtension'] )->configurationId;
		}

		/* Normalize columns */
		$data['column']	= !is_array( $data['column'] ) ? array( $data['column'] ) : $data['column'];

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
		if( !$this->canBeRun( $data) )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		$last		= ( isset( $data['primaryId'] ) ) ? NULL : $offset;
		$deleted	= 0;

		Log::debug( "Deleting files from an offset of " . $offset, 'deleteFilesTask' );

		$where = $data['where'] ?? array();
		if( isset( $data['primaryId'] ) )
		{
			$where[]	= array( $data['primaryId'] . '> ?', $offset );
			$limit		= array( 0, $this->perCycle );
			$order		= $data['primaryId'] . ' ASC';
		}
		else
		{
			$limit		= array( $offset, $this->perCycle );
			$order		= NULL;
		}

		foreach( Db::i()->select( '*', $data['table'], $where, $order, $limit ) as $row )
		{
			/* Set the last ID we deleted now */
			$last	= ( isset( $data['primaryId'] ) ) ? $row[ $data['primaryId'] ] : ( $last + 1 );

			/* Increment the counter for the progress bar */
			$deleted++;

			/* Handle each column */
			foreach( $data['column'] as $column )
			{
				if( isset( $row[ $column ] ) AND $row[ $column ] )
				{
					$values = ( isset( $data['multipleFiles'] ) AND $data['multipleFiles'] ) ? explode( ',', $row[ $column ] ) : array( $row[ $column ] );

					foreach( $values as $fileLocation )
					{
						try
						{
							File::get( $data['storageExtension'], $fileLocation )->delete();
						}
						catch( Exception $e )
						{
							Log::log( $e, 'fileCleanupDeleteFailed' );
							continue;
						}
					}
				}
			}
		}

		$data['deleted'] = $data['deleted'] + $deleted;

		if( $deleted === 0 )
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
		return array( 'text' => Member::loggedIn()->language()->addToStack('deleting_files_generic'), 'complete' => $data['count'] ? ( round( ( 100 / $data['count'] ) * $data['deleted'], 2 ) ) : 100 );
	}

	/**
	 * Perform post-completion processing
	 *
	 * @param	array	$data		Data returned from preQueueData
	 * @param	bool	$processed	Was anything processed or not? If preQueueData returns NULL, this will be FALSE.
	 * @return	void
	 */
	public function postComplete( array $data, bool $processed = TRUE ) : void
	{
		if( !isset( $data['data'] ) )
		{
			return;
		}

		$data = json_decode( $data['data'], TRUE );

		if( isset( $data['dropTable'] ) )
		{
			Db::i()->dropTable( $data['dropTable'], TRUE );
		}
		elseif( isset( $data['dropColumn'] ) AND isset( $data['dropColumnTable'] ) )
		{
			Db::i()->dropColumn( $data['dropColumnTable'], $data['dropColumn'] );
		}
		elseif( isset( $data['deleteRows'] ) AND $data['deleteRows'] )
		{
			Db::i()->delete( $data['table'], $data['where'] );
		}
	}

	/**
	 * Determine if the task can be run
	 * 
	 * @param array $data
	 * @return bool
	 */
	protected function canBeRun( array $data ): bool
	{
		return !( !isset( $data['table'] ) OR empty( $data['column'] ) OR empty( $data['storageExtension'] ) OR !Db::i()->checkForTable( $data['table'] ) );
	}
}