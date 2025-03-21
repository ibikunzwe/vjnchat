<?php

/**
 * @brief		Converter Photopost Gallery Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		6 December 2016
 * @note		Only redirect scripts are supported right now
 */

namespace IPS\convert\Software\Gallery;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use Exception;
use IPS\convert\Software;
use IPS\Db;
use IPS\gallery\Category;
use IPS\gallery\Image;
use IPS\Http\Url;
use IPS\Member;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Request;
use IPS\Task;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Photopost Gallery Converter
 */
class Photopost extends Software
{
	/**
	 * Software Name
	 *
	 * @return    string
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		return "Photopost (8.x)";
	}

	/**
	 * Software Key
	 *
	 * @return    string
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		return "photopost";
	}

	/**
	 * Requires Parent
	 *
	 * @return    boolean
	 */
	public static function requiresParent(): bool
	{
		return TRUE;
	}

	/**
	 * Possible Parent Conversions
	 *
	 * @return    array|null
	 */
	public static function parents(): ?array
	{
		return array( 'core' => array( 'photopost' ) );
	}

	/**
	 * Content we can convert from this software.
	 *
	 * @return    array|null
	 */
	public static function canConvert(): ?array
	{
		return array(
			'convertGalleryCategories'	=> array(
				'table'						=> 'categories',
				'where'						=> array( 'cattype=?', 'c' )
			),
			'convertGalleryAlbums'		=> array(
				'table'						=> 'categories',
				'where'						=> array( 'cattype=?', 'a' )
			),
			'convertGalleryImages'		=> array(
				'table'						=> 'photos',
				'where'						=> NULL,
			),
			'convertGalleryComments'	=> array(
				'table'						=> 'comments',
				'where'						=> NULL,
			)
		);
	}

	/**
	 * List of conversion methods that require additional information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array(
			'convertGalleryAlbums',
			'convertGalleryImages'
		);
	}

	/**
	 * Get More Information
	 *
	 * @param string $method	Conversion method
	 * @return    array|null
	 */
	public function getMoreInfo( string $method ): ?array
	{
		$return = array();
		switch( $method )
		{
			case 'convertGalleryAlbums':
				$options = array();
				foreach( new ActiveRecordIterator( Db::i()->select( '*', 'gallery_categories' ), 'IPS\gallery\Category' ) AS $category )
				{
					$options[$category->_id] = $category->_title;
				}

				$return['convertGalleryAlbums']['members_gallery_category'] = array(
					'field_class'		=> 'IPS\\Helpers\\Form\\Select',
					'field_default'		=> NULL,
					'field_required'	=> FALSE,
					'field_extra'		=> array(
						'options'			=> $options
					),
					'field_hint'		=> NULL,
				);
				break;
			case 'convertGalleryImages':
				$return['convertGalleryImages']['file_location'] = array(
					'field_class'		=> 'IPS\\Helpers\\Form\\Text',
					'field_default'		=> NULL,
					'field_required'	=> TRUE,
					'field_extra'		=> array(),
					'field_hint'		=> Member::loggedIn()->language()->addToStack('convert_photopost_image_path'),
					'field_validation'	=> function( $value ) { if ( !@is_dir( $value ) ) { throw new DomainException( 'path_invalid' ); } },
				);

				break;
		}

		return ( isset( $return[ $method ] ) ) ? $return[ $method ] : array();
	}

	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		/* Content Rebuilds */
		Task::queue( 'convert', 'RebuildGalleryImages', array( 'app' => $this->app->app_id ), 2, array( 'app' ) );
		Task::queue( 'convert', 'RebuildContent', array( 'app' => $this->app->app_id, 'link' => 'gallery_comments', 'class' => 'IPS\gallery\Image\Comment' ), 2, array( 'app', 'link', 'class' ) );
		Task::queue( 'core', 'RebuildItemCounts', array( 'class' => 'IPS\gallery\Image' ), 3, array( 'class' ) );
		Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\gallery\Album', 'count' => 0 ), 4, array( 'class' ) );
		Task::queue( 'core', 'RebuildContainerCounts', array( 'class' => 'IPS\gallery\Category', 'count' => 0 ), 5, array( 'class' ) );

		return array( "f_gallery_images_rebuild", "f_gallery_cat_recount", "f_gallery_album_recount", "f_gallery_image_recount" );
	}

	/**
	 * Convert gallery categories
	 *
	 * @return	void
	 */
	public function convertGalleryCategories() : void
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'categories', 'id', array( 'cattype=?', 'c' ) ) AS $row )
		{
			$libraryClass->convertGalleryCategory( array(
				'category_id'			=> $row['id'],
				'category_name'			=> html_entity_decode( $row['catname'] ),
				'category_desc'			=> $row['description'],
				'category_parent_id'	=> $row['parent'],
				'category_count_imgs'	=> $row['photos'],
				'category_position'		=> $row['catorder']
			) );

			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert gallery albums
	 *
	 * @return	void
	 */
	public function convertGalleryAlbums() : void
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'categories', 'id', array( 'cattype=?', 'a' ) ) AS $row )
		{
			$info = array(
				'album_id'					=> $row['id'],
				'album_owner_id'			=> $row['parent'],
				'album_description'			=> $row['description'],
				'album_name'				=> $row['catname'],
				'album_type'				=> $row['private'] == 'yes' ? 2 : 1,
				'album_count_imgs'			=> $row['photos'],
				'album_count_comments'		=> $row['posts']
			);

			$category = $this->app->_session['more_info']['convertGalleryAlbums']['members_gallery_category'];
			if ( $category == 0 )
			{
				$category = NULL;
			}

			$libraryClass->convertGalleryAlbum( $info, NULL, $category );
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert gallery images
	 *
	 * @return	void
	 */
	public function convertGalleryImages() : void
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'photos' ) AS $row )
		{
			$info = array(
				'image_id'				=> $row['id'],
				'image_album_id'		=> $row['cat'],
				'image_category_id'		=> $row['cat'],
				'image_member_id'		=> $row['userid'],
				'image_caption'			=> $row['title'],
				'image_description'		=> $row['description'],
				'image_views'			=> $row['views'],
				'image_comments'		=> $row['numcom'],
				'image_ratings_total'	=> $row['rating'] * $row['votes'],
				'image_ratings_count'	=> $row['votes'],
				'image_rating'			=> $row['rating'],
				'image_date'			=> $row['date'],
				'image_last_comment'	=> $row['lastpost'],
				'image_ipaddress'		=> $row['ipaddress'],
				'image_approved'		=> $row['approved'],
				'image_file_name'		=> $row['bigimage']
			);

			$directory = $row['storecat'] ?: $row['cat'];
			$path = rtrim( $this->app->_session['more_info']['convertGalleryImages']['file_location'], '/' ) . '/' . $directory . '/' . $row['bigimage'];

			$libraryClass->convertGalleryImage( $info, $path );
			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Convert gallery comments
	 *
	 * @return	void
	 */
	public function convertGalleryComments() : void
	{
		$libraryClass = $this->getLibrary();

		$libraryClass::setKey( 'id' );

		foreach( $this->fetch( 'comments' ) AS $row )
		{
			$libraryClass->convertGalleryComment( array(
				'comment_id'			=> $row['id'],
				'comment_text'			=> $row['comment'],
				'comment_img_id'		=> $row['photo'],
				'comment_author_id'		=> $row['userid'],
				'comment_author_name'	=> $row['username'],
				'comment_ip_address'	=> $row['ipaddress'],
				'comment_post_date'		=> $row['date'],
				'comment_approved'		=> $row['approved'],
			) );

			$libraryClass->setLastKeyValue( $row['id'] );
		}
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		$url = Request::i()->url();

		if( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'showgallery.php' ) !== FALSE )
		{
			if( !$id = Request::i()->cat )
			{
				preg_match( '#cat/([0-9]+)#', $url->data[ Url::COMPONENT_PATH ], $matches );
				$id = $matches[1] ?? NULL;
			}

			try
			{
				$data = (string) $this->app->getLink( $id, 'gallery_categories' );
				$item = Category::load( $data );

				if( $item->can('view') )
				{
					return $item->url();
				}
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}
		elseif( mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'showphoto.php' ) !== FALSE OR
			mb_strpos( $url->data[ Url::COMPONENT_PATH ], 'showfull.php' ) !== FALSE)
		{
			if( !$id = Request::i()->photo )
			{
				preg_match( '#photo/([0-9]+)#', $url->data[ Url::COMPONENT_PATH ], $matches );
				$id = $matches[1] ?? NULL;
			}

			try
			{
				$data = (string) $this->app->getLink( $id, 'gallery_images' );
				$item = Image::load( $data );

				if( $item->canView() )
				{
					return $item->url();
				}
			}
			catch( Exception $e )
			{
				return NULL;
			}
		}

		return NULL;
	}
}