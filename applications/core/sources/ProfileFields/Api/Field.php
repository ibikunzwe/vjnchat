<?php
/**
 * @brief		API output for custom fields
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		4 Mar 2016
 */

namespace IPS\core\ProfileFields\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * API output for custom fields groups
 */
class Field
{
	/**
	 * @brief	Name
	 */
	protected string $name = '';
	
	/**
	 * @brief	Value
	 */
	protected mixed $value = null;
	
	/**
	 * Constructor
	 *
	 * @param	string	$name	Group name
	 * @param	mixed	$value	Values
	 */
	public function __construct( string $name, mixed $value )
	{
		$this->name = $name;
		$this->value = $value;
	}
	
	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return	array
	 * @apiresponse	string		name	Field name
	 * @apiresponse	string		value	Value
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		return array( 'name' => $this->name, 'value' => $this->value );
	}
}