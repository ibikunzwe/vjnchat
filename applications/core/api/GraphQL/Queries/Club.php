<?php
/**
 * @brief		GraphQL: Club query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Queries;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\core\api\GraphQL\Types\ClubType;
use IPS\Member\Club as ClubClass;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Club query for GraphQL API
 */
class Club
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a club";

	/*
	 * Query arguments
	 */
	public function args(): array {
		return array(
			'id' => TypeRegistry::nonNull( TypeRegistry::id() )
		);
	}

	/**
	 * Return the query return type
	 */
	public function type() : ClubType
	{
		return \IPS\core\api\GraphQL\TypeRegistry::club();
	}

	/**
	 * Resolves this query
	 *
	 * @param mixed $val Value passed into this resolver
	 * @param array $args Arguments
	 * @param array $context Context values
	 * @return	ClubClass
	 */
	public function resolve( mixed $val, array $args, array $context ) : ClubClass
	{
		return ClubClass::loadAndCheckPerms( $args['id'] );
	}
}
