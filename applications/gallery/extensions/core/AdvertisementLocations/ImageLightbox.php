<?php
/**
 * @brief		Advertisement locations extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Gallery
 * @since		16 Jan 2018
 */

namespace IPS\gallery\extensions\core\AdvertisementLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\AdvertisementLocationsAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Advertisement locations extension
 */
class ImageLightbox extends AdvertisementLocationsAbstract
{
	/** 
	 * Get the locations and the additional settings
	 *
	 * @param	array	$settings	Current setting values
	 * @return	array	Array with two elements: 'locations' which should have keys as the location keys and values as the fields to toggle, and 'settings' which are additional fields to add to the form
	 */
	public function getSettings( array $settings ): array
	{
		return array( 'locations' => array( 'ad_image_lightbox' => array() ), 'settings' => array() );
	}

	/** 
	 * Return an array of setting values to store
	 *
	 * @param	array	$values	Values from the form submission
	 * @return	array 	Array of setting key => value to store
	 */
	public function parseSettings( array $values ): array
	{
		return array();
	}
}