<?php
/**
 * @brief		Template Plugin - Hex to RGB. Takes a CSS hex code and returns an RGB string
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		05 Jan 2016
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Settings;
use IPS\Theme;
use function defined;
use function str_replace;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - hextorgb
 */
class Hextorgb
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = TRUE;

	/**
	 * Run the plug-in. Take #ffffff and returns 255,255,255, for example
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): string
	{
		if( $data[0] !== '$' ){
			$data = "'" . $data . "'";
		}

		$opacity = 1;
		if( !empty( $options['opacity'] ) )
		{
			$opacity = $options['opacity'][0] !== '$' ? "'" . $options['opacity'] . "'" : $options['opacity'];
		}

		return "\IPS\Output\Plugin\Hextorgb::convertToRGB({$data}, {$opacity})";
	}

	/**
	 * Convert Hex value to RGB
	 *
	 * @param string $data		Hex color code
	 * @param string $opacity	Opacity (0-1) or Setting/Theme Setting name
	 * @return	string
	 */
	public static function convertToRGB( string $data, string $opacity ): string
	{
		$output = array();

		/* If a theme setting key has been passed in, then use that as the value */
		if ( isset( Theme::i()->settings[ $data ] ) )
		{
			$data = Theme::i()->settings[ $data ];
		}

		/* If a regular setting key has been passed in, then use that as the value */
		if ( isset( Settings::i()->$data ) )
		{
			$data = Settings::i()->$data;
		}

		/* If a theme setting key has been passed in, then use that as the opacity */
		if ( isset( Theme::i()->settings[ $opacity ] ) )
		{
			$opacity = Theme::i()->settings[ $opacity ];
		}

		/* If a regular setting key has been passed in, then use that as the opacity */
		if ( isset( Settings::i()->$opacity ) )
		{
			$opacity = Settings::i()->$opacity;
		}

		/* Basic validation */
		if( !preg_match( "/^#?[0-9a-fA-F]+$/", $data ) OR ( strlen( str_replace( '#', '', $data ) ) !== 3 AND strlen( str_replace( '#', '', $data ) ) !== 6 ) )
		{
			return "htmlspecialchars( '" . $data . "', ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE )";
		}

		$data = str_replace( '#', '', $data );

		if ( strlen( $data ) == 3 )
		{
			$output[] = hexdec( substr( $data, 0, 1 ) . substr( $data, 0, 1 ) ); // R
			$output[] = hexdec( substr( $data, 1, 1 ) . substr( $data, 1, 1 ) ); // G
			$output[] = hexdec( substr( $data, 2, 1 ) . substr( $data, 2, 1 ) ); // B
		}
		else
		{
			$output[] = hexdec( substr( $data, 0, 2 ) ); // R
			$output[] = hexdec( substr( $data, 2, 2 ) ); // G
			$output[] = hexdec( substr( $data, 4, 2 ) ); // B
		}

		if( isset( $opacity ) && $opacity !== '1' )
		{
			return "rgba(" . implode( ',', $output ) . "," . str_replace( ',', '.', $opacity ) . ")";
		}
		else
		{
			return "rgb(" . implode( ',', $output ) . ")";	
		}		
	}
}