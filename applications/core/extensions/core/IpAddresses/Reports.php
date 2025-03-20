<?php
/**
 * @brief		IP Address Lookup: Reports
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		12 Oct 2016
 */

namespace IPS\core\extensions\core\IpAddresses;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Reports\Report;
use IPS\DateTime;
use IPS\Db;
use IPS\Db\Select;
use IPS\Extensions\IpAddressesAbstract;
use IPS\Helpers\Table\Db as TableDb;
use IPS\Http\Url;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * IP Address Lookup: Reports
 */
class Reports extends IpAddressesAbstract
{
	/**
	 * Removes the logged IP address
	 *
	 * @param int $time
	 * @return void
	 */
	public function pruneIpAddresses( int $time ) : void
	{
		Db::i()->update('core_rc_reports', [ 'ip_address' => '' ] , [ 'date_reported <?', $time ] );
	}

	/** 
	 * Find Records by IP
	 *
	 * @param	string			$ip			The IP Address
	 * @param	Url|null	$baseUrl	URL table will be displayed on or NULL to return a count
	 * @return	string|int|null
	 */
	public function findByIp( string $ip, ?Url $baseUrl = NULL ): string|int|null
	{
		/* Return count */
		if ( $baseUrl === NULL )
		{
			return Db::i()->select( 'COUNT(*)', 'core_rc_reports', array( "ip_address LIKE ?", $ip ) )->first();
		}
		
		/* Init Table */
		$table = new TableDb( 'core_rc_reports', $baseUrl, array( "ip_address LIKE ?", $ip ) );
				
		/* Columns we need */
		$table->include = array( 'report_by', 'report', 'date_reported', 'ip_address' );
		$table->mainColumn = 'date_reported';
		$table->langPrefix = 'report_';

		$table->tableTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'table' );
		$table->rowsTemplate  = array( Theme::i()->getTemplate( 'tables', 'core', 'admin' ), 'rows' );
				
		/* Default sort options */
		$table->sortBy = $table->sortBy ?: 'date_reported';
		$table->sortDirection = $table->sortDirection ?: 'desc';
		
		/* Custom parsers */
		$table->parsers = array(
			'report_by'			=> function( $val, $row )
			{
				$member = Member::load( $row['report_by'] );
				return Theme::i()->getTemplate( 'global', 'core' )->userPhoto( $member, 'tiny' ) . ' ' . $member->link();
			},
			'date_reported'		=> function( $val, $row )
			{
				return DateTime::ts( $val );
			},
			'report'		=> function( $val, $row )
			{
				try
				{
					$report = Report::load( $row['rid'] );
					return Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $report->url(), TRUE, $report->mapped('title') );
				}
				catch( OutOfRangeException $e )
				{
					return "";
				}
			},
		);
		
		/* Return */
		return (string) $table;
	}
	
	/**
	 * Find IPs by Member
	 *
	 * @code
	 	return array(
	 		'::1' => array(
	 			'ip'		=> '::1'// string (IP Address)
		 		'count'		=> ...	// int (number of times this member has used this IP)
		 		'first'		=> ... 	// int (timestamp of first use)
		 		'last'		=> ... 	// int (timestamp of most recent use)
		 	),
		 	...
	 	);
	 * @endcode
	 * @param	Member	$member	The member
	 * @return	array|Select
	 */
	public function findByMember( Member $member ) : array|Select
	{
		return Db::i()->select( "ip_address AS ip, count(*) AS count, MIN(date_reported) AS first, MAX(date_reported) AS last", 'core_rc_reports', array( 'report_by=?', $member->member_id ), NULL, NULL, 'ip_address' )->setKeyField( 'ip' );
	}	
}