<?php
/**
 * @brief		Base API Graph Controller
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		1 Sept 2022
 */

namespace IPS\Api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL as _GraphQL;
use GraphQL\Type\Schema;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\IPS;
use IPS\Member;
use function defined;
use const IPS\DEBUG_GRAPHQL;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Base API Controller
 */
class GraphQL
{

	public function __construct()
	{
		/* This space intentionally left blank */
	}

	/**
	 * Execute
	 *
	 * @param	string				$query		The query to execute
	 * @param	array				$variables	Variables to include in query
	 * @param Member|null $member Member to check or NULL for currently logged in member.
	 * @return 	array 				GraphQL response
	 */
	public static function execute( string $query, array $variables = [], ?Member $member = NULL ) : array
	{
		$member = $member ?: Member::loggedIn();
		/* Register our GraphQL library */
		IPS::$PSR0Namespaces['GraphQL'] = \IPS\ROOT_PATH . "/system/3rd_party/graphql-php";

		/* Execute! */
		$result = _GraphQL::executeQuery(
			new Schema([
				'query' => TypeRegistry::query(),
				'mutation' => TypeRegistry::mutation()
			]),
			$query,
			NULL, // $rootValue
			[
				'member'	=> $member
			], // $context
			$variables
		);

		/* Convert result into JSON and send */
		return $result->toArray( ( \IPS\IN_DEV OR DEBUG_GRAPHQL ) ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE : false );
	}
}