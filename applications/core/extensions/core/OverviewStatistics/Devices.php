<?php
/**
 * @brief		Overview statistics extension: Devices
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jan 2020
 */

namespace IPS\core\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Member;
use IPS\Settings;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Devices
 */
class Devices extends OverviewStatisticsAbstract
{
	/**
	 * @brief	Which statistics page (activity or user)
	 */
	public string $page	= 'user';

	/**
	 * Return the sub-block keys
	 *
	 * @note This is designed to allow one class to support multiple blocks, for instance using the ContentRouter to generate blocks.
	 * @return array
	 */
	public function getBlocks(): array
	{
		return array( 'devices' );
	}

	/**
	 * Return block details (title and description)
	 *
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockDetails( string $subBlock = NULL ): array
	{
		$pruneNotice = Settings::i()->stats_device_usage_prune ? Member::loggedIn()->language()->addToStack( 'stats_overview_prune', TRUE, array( 'pluralize' => array( Settings::i()->stats_device_usage_prune ) ) ) : NULL;

		/* Description can be null and will not be shown if so */
		return array( 'app' => 'core', 'title' => 'stats_overview_devices', 'description' => $pruneNotice, 'refresh' => 10 );
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param	array|string|null    $dateRange	String for a fixed time period in days, NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param	string|NULL	$subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	public function getBlock( array|string $dateRange = NULL, string $subBlock = NULL ): string
	{
		/* Init Chart */
		$pieBarData = array();
		
		/* Add Rows */
		$where		= NULL;

		if( $dateRange !== NULL )
		{
			if( is_array( $dateRange ) )
			{
				$where = array(
					array( 'time > ?', $dateRange['start']->getTimestamp() ),
					array( 'time < ?', $dateRange['end']->getTimestamp() ),
				);
			}
			else
			{
				$currentDate = new DateTime;

				switch( $dateRange )
				{
					case '7':
						$where = array( array( 'time > ? ', $currentDate->sub( new DateInterval( 'P7D' ) )->getTimestamp() ) );
					break;

					case '30':
						$where = array( array( 'time > ? ', $currentDate->sub( new DateInterval( 'P1M' ) )->getTimestamp() ) );
					break;

					case '90':
						$where = array( array( 'time > ? ', $currentDate->sub( new DateInterval( 'P3M' ) )->getTimestamp() ) );
					break;

					case '180':
						$where = array( array( 'time > ? ', $currentDate->sub( new DateInterval( 'P6M' ) )->getTimestamp() ) );
					break;

					case '365':
						$where = array( array( 'time > ? ', $currentDate->sub( new DateInterval( 'P1Y' ) )->getTimestamp() ) );
					break;
				}
			}
		}
		$where[] = ['type=?', 'devices'];
		$result = Db::i()->select( 'SUM(value_1) as mobiles, SUM(value_2) AS tablets, SUM(value_3) as consoles, SUM(value_4) as desktops', 'core_statistics', $where )->first();
		$total = $result['mobiles'] + $result['tablets'] + $result['consoles'] + $result['desktops'];

		foreach( array('mobiles', 'tablets', 'consoles', 'desktops' ) as $device )
		{
			if( $result[ $device ] > 0 )
			{
				$pieBarData[] = array(
					'name' =>  Member::loggedIn()->language()->addToStack('stats_devices_' . $device),
					'value' => $result[ $device ],
					'percentage' => round( ( $result[ $device ] / $total ) * 100, 2 )
				);
			}
		}

		return Theme::i()->getTemplate( 'global', 'core', 'global'  )->applePieChart( $pieBarData );
	}
}