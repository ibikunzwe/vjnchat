<?php
/**
 * @brief		GraphQL: Forum query
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Queries;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\forums\api\GraphQL\Types\ForumType;
use IPS\forums\Forum as ForumClass;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Forum query for GraphQL API
 */
class Forum
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Returns a forum";

	/*
	 * Query arguments
	 */
	public function args(): array
	{
		return array(
			'id' => TypeRegistry::nonNull( TypeRegistry::id() ),
			'password' => TypeRegistry::string()
		);
	}

	/**
	 * Return the query return type
	 *
	 * @return ForumType
	 */
	public function type() : ForumType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::forum();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @param 	array $context 	Context values
	 * @param	mixed $info
	 * @return	ForumClass
	 */
	public function resolve( mixed $val, array $args, array $context, mixed $info ) : ForumClass
	{
		$forum = ForumClass::load( $args['id'] );

		if( !$forum->can( 'view', $context['member'] ) )
		{
			throw new OutOfRangeException;
		}
		return $forum;
	}
}
