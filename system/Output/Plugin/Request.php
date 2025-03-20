<?php
/**
 * @brief		Template Plugin - Request
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Output\Plugin;

/* To prevent PHP errors (extending class does not exist) revealing path */

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin - Request
 */
class Request
{
	/**
	 * @brief	Can be used when compiling CSS
	 */
	public static bool $canBeUsedInCss = FALSE;
	
	/**
	 * Run the plug-in
	 *
	 * @param	string 		$data	  The initial data from the tag
	 * @param	array		$options    Array of options
	 * @return	string		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): string
	{
		if( isset( $options['raw'] ) AND $options['raw'] )
		{
			return "isset( \IPS\Widget\Request::i()->$data ) ? \IPS\Widget\Request::i()->$data : NULL";
		}
		else
		{
			return "isset( \IPS\Widget\Request::i()->$data ) ? htmlspecialchars( \IPS\Widget\Request::i()->$data, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE ): NULL";
		}
	}
}