<?php
/**
 * @brief		Support Videos in sitemaps
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		09 Feb 2018
 */

namespace IPS\gallery\extensions\core\Sitemap;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Content\Filter;
use IPS\Content\Item;
use IPS\core\extensions\core\Sitemap\Content;
use IPS\DateTime;
use IPS\Db;
use IPS\Extensions\SitemapAbstract;
use IPS\File;
use IPS\gallery\Image;
use IPS\Member;
use IPS\Settings;
use IPS\Sitemap;
use UnderflowException;
use function defined;
use function intval;
use function is_array;
use const IPS\SITEMAP_MAX_PER_FILE;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Support Videos in sitemaps
 */
class Videos extends SitemapAbstract
{
	/**
	 * @brief	Recommended Settings
	 */
	public array $recommendedSettings = array();

	/**
	 * Add settings for ACP configuration to the form
	 *
	 * @return	array
	 */
	public function settings(): array
	{
		return array();
	}

	/**
	 * Get the sitemap filename(s)
	 *
	 * @return	array
	 */
	public function getFilenames(): array
	{
		$settings	= Settings::i()->sitemap_content_settings ? json_decode( Settings::i()->sitemap_content_settings, TRUE ) : array();
		$class		= 'IPS\\gallery\\Image';
		$limit		= ( isset( $settings["sitemap_{$class::$title}_count"] ) ) ? $settings["sitemap_{$class::$title}_count"] : -1;

		if( $limit == 0 )
		{
			return array();
		}

		$count	= Image::getItemsWithPermission( array( 'image_media=?', 1 ), NULL, 10, 'read', Filter::FILTER_AUTOMATIC, 0, new Member, FALSE, FALSE, FALSE, TRUE );
		$files  = array();

		$count = ceil( $count / SITEMAP_MAX_PER_FILE );
		
		for( $i=1; $i <= $count; $i++ )
		{
			$files[] = 'sitemap_videositemap_' . $i;
		}

		return $files;
	}

	/**
	 * Generate the sitemap
	 *
	 * @param	string			$filename	The sitemap file to build (should be one returned from getFilenames())
	 * @param	Sitemap	$sitemap	Sitemap object reference
	 * @return	int|null
	 */
	public function generateSitemap( string $filename, Sitemap $sitemap ) : ?int
	{
		$entries	= array();
		$lastId		= 0;
		$settings	= Settings::i()->sitemap_content_settings ? json_decode( Settings::i()->sitemap_content_settings, TRUE ) : array();
		$class		= 'IPS\\gallery\\Image';

		$exploded	= explode( '_', $filename );
		$block		= (int) array_pop( $exploded );
		$totalLimit	= ( isset( $settings["sitemap_{$class::$title}_count"] ) AND $settings["sitemap_{$class::$title}_count"] ) ? $settings["sitemap_{$class::$title}_count"] : Content::RECOMMENDED_ITEM_LIMIT;
		$offset		= ( $block - 1 ) * SITEMAP_MAX_PER_FILE;
		$limit		= SITEMAP_MAX_PER_FILE;
		
		if ( ! $totalLimit )
		{
			return NULL;
		}
		
		if ( $totalLimit > -1 and ( $offset + $limit ) > $totalLimit )
		{
			$limit = $totalLimit - $offset;
		}

		/* Create limit clause */
		$limitClause	= array( $offset, $limit );

		$where		= $class::sitemapWhere();
		$where[]	= array( 'image_media=?', 1 );

		/* Try to fetch the highest ID built in the last sitemap, if it exists */
		try
		{
			$lastId = Db::i()->select( 'last_id', 'core_sitemap', array( array( 'sitemap=?', implode( '_', $exploded ) . '_' . ( $block - 1 ) ) ) )->first();

			if( $lastId > 0 )
			{
				$where[]		= array( $class::$databaseTable . '.' . $class::$databasePrefix . $class::$databaseColumnId . ' > ?', $lastId );
				$limitClause	= $limit;
			}
		}
		catch( UnderflowException ){}

		$idColumn = $class::$databaseColumnId;
		foreach ( $class::getItemsWithPermission( $where, $class::$databasePrefix . $class::$databaseColumnId .' ASC', $limitClause, 'read', Filter::FILTER_PUBLIC_ONLY, Item::SELECT_IDS_FIRST, new Member, TRUE ) as $item )
		{
			if( !$item->canView( new Member ) )
			{
				continue;
			}

			$data = array( 'url' => $item->url() );
			$lastMod = NULL;
			
			if ( isset( $item::$databaseColumnMap['last_comment'] ) )
			{
				$lastCommentField = $item::$databaseColumnMap['last_comment'];
				if ( is_array( $lastCommentField ) )
				{
					foreach ( $lastCommentField as $column )
					{
						$lastMod = DateTime::ts( $item->$column );
					}
				}
				else
				{
					$lastMod = DateTime::ts( $item->$lastCommentField );
				}
			}
			
			if ( $lastMod )
			{
				$data['lastmod'] = $lastMod;
			}

			/* Video sitemap data */
			$data['video:video'] = array( 
				'video:content_loc'	=> (string) File::get( 'gallery_Images', $item->original_file_name )->url->setScheme( ( mb_substr( Settings::i()->base_url, 0, 5 ) === 'https' ) ? 'https' : 'http' ),
				'video:description'	=> $item->mapped('content'),
				'video:title'		=> $item->mapped('title'),
				'video:rating'		=> $item->mapped('rating_average'),
				'video:view_count'	=> $item->mapped('views'),
				'video:publication_date'	=> DateTime::ts( $item->date )->rfc3339(),
				'video:uploader'	=> array( 0 => $item->author()->name, 'info' => (string) $item->author()->url() ),
			);

			if( $item->masked_file_name )
			{
				$data['video:video']['video:thumbnail_loc'] = (string) File::get( 'gallery_Images', $item->masked_file_name )->url->setScheme( ( mb_substr( Settings::i()->base_url, 0, 5 ) === 'https' ) ? 'https' : 'http' );
			}
		
			$priority = ( $item->sitemapPriority() ?: ( intval($settings["sitemap_{$class::$title}_priority"] ?? Content::RECOMMENDED_ITEM_PRIORITY) ) );
			if ( $priority !== -1 )
			{
				$data['priority'] = $priority;
			}

			$entries[] = $data;

			$lastId = $item->$idColumn;
		}

		$sitemap->buildSitemapFile( $filename, $entries, $lastId, array( 'video' => 'https://www.google.com/schemas/sitemap-video/1.1' ) );
		return $lastId;
	}

	/**
	 * Save settings for ACP configuration
	 *
	 * @param array $values Values
	 * @return    void
	 */
	public function saveSettings( array $values ): void
	{

	}
}