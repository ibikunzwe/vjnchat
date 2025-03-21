<?php
/**
 * @brief		Overview statistics extension: Blogs
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Blogs
 * @since		28 Jan 2020
 */

namespace IPS\blog\extensions\core\OverviewStatistics;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use IPS\Extensions\OverviewStatisticsAbstract;
use IPS\Member;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Overview statistics extension: Blogs
 */
class Blogs extends OverviewStatisticsAbstract
{
	/**
	 * @brief	Which statistics page (activity or user)
	 */
	public string $page	= 'activity';

	/**
	 * Return the sub-block keys
	 *
	 * @note This is designed to allow one class to support multiple blocks, for instance using the ContentRouter to generate blocks.
	 * @return array
	 */
	public function getBlocks(): array
	{
		return array( 'blogs' );
	}

	/**
	 * Return block details (title and description)
	 *
	 * @param string|null $subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	array
	 */
	public function getBlockDetails( string $subBlock = NULL ): array
	{
		/* Description can be null and will not be shown if so */
		return array( 'app' => 'blog', 'title' => 'stats_overview_blogs', 'description' => Member::loggedIn()->language()->addToStack( 'stats_overview_blogs_desc' ), 'refresh' => 120 );
	}

	/** 
	 * Return the block HTML to show
	 *
	 * @param array|string|null $dateRange	NULL for all time, or an array with 'start' and 'end' \IPS\DateTime objects to restrict to
	 * @param string|null $subBlock	The subblock we are loading as returned by getBlocks()
	 * @return	string
	 */
	public function getBlock( array|string|null $dateRange = NULL, string $subBlock = NULL ): string
	{
		$total = Db::i()->select( 'COUNT(*)', 'blog_blogs' )->first();

		return Theme::i()->getTemplate( 'stats' )->overviewComparisonCount( $total, NULL, NULL );
	}
}