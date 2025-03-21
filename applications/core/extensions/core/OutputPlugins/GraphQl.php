<?php
/**
 * @brief		Template Plugin
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		01 Sep 2022
 */

namespace IPS\core\extensions\core\OutputPlugins;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\GraphQL as GraphQLApi;
use IPS\Extensions\OutputPluginsAbstract;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Template Plugin
 */
class GraphQl extends OutputPluginsAbstract
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
	 * @return	string|array		Code to eval
	 */
	public static function runPlugin( string $data, array $options ): string|array
	{
		if( !isset( $options['variables']))
		{
			$options['variables'] = [];
		}

		$response = GraphQLApi::execute($data, $options['variables']);

		if( isset( $response['data']))
		{
			return "'"  . json_encode($response['data'], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS ) . "'";
		}

		return '';
	}
}