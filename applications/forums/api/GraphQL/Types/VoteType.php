<?php
/**
 * @brief		GraphQL: Vote Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		25 Jan 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Types;
use GraphQL\Type\Definition\EnumType;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * VoteType for GraphQL API
 */
class VoteType extends EnumType
{
	/**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'forums_VoteType',
			'values' => ['UP', 'DOWN']
		];

		parent::__construct( $config );
	}
}