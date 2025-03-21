<?php
/**
 * @brief		GraphQL: Change a notification setting
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		4 Aug 2019
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Application;
use IPS\core\api\GraphQL\Types\NotificationTypeType;
use IPS\Db;
use IPS\Member;
use IPS\Notification;
use function count;
use function defined;
use function in_array;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Change notification setting mutation for GraphQL API
 */
class ChangeNotificationSetting
{
	/*
	 * @brief 	Query description
	 */
	public static string $description = "Change a notification setting";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'id' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'extension' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'type' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'email' => TypeRegistry::boolean(),
			'push' => TypeRegistry::boolean(),
			'inline' => TypeRegistry::boolean(),
		];
	}

	/**
	 * Return the mutation return type
	 *
	 * @return NotificationTypeType
	 */
	public function type() : NotificationTypeType
	{
		return \IPS\core\Api\GraphQL\TypeRegistry::notificationType();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @return	array
	 */
	public function resolve( mixed $val, array $args ) : array
	{
		if( !Member::loggedIn()->member_id )
		{
			throw new SafeException( 'NOT_LOGGED_IN', 'GQL/0003/1', 403 );
		}

		$pieces = explode('_', $args['extension']);

		if( count( $pieces ) !== 2 )
		{
			throw new SafeException( 'INVALID_EXTENSION', 'GQL/0003/1', 403 );
		}

		$extensions = Application::load( $pieces[0] )->extensions('core', 'Notifications');

		if( !isset( $extensions[ $pieces[1] ] ) )
		{
			throw new SafeException( 'INVALID_EXTENSION', 'GQL/0003/1', 403 );
		}

		$extensionOptions = Notification::availableOptions( Member::loggedIn(), $extensions[ $pieces[1] ] );

		if( !isset( $extensionOptions[ $args['type'] ] ) || $extensionOptions[ $args['type'] ]['type'] !== 'standard' ) // Only standard types supported right now
		{
			throw new SafeException( 'INVALID_TYPE', 'GQL/0003/1', 403 );
		}

		$value = array();
		$extensionType = $extensionOptions[ $args['type'] ];
		$options = $extensionType['options'];

		foreach( $options as $type => $typeSettings )
		{
			// If the mutation is trying to change a setting...
			if( isset( $args[ $type ] ) )
			{
				if( $args[ $type ] === TRUE && $typeSettings['editable'] == TRUE && !in_array( $type, $extensionType['disabled'] ) )
				{
					$value[ $type ] = $type;
				}
			}
			// If we're taking the existing value
			else if( $typeSettings['value'] === TRUE )
			{
				$value[ $type ] = $type;
			}
		}

		// If push is true, inline must also be true
		if( isset( $value['push'] ) )
		{
			$value['inline'] = 'inline';
		}

		foreach ( $extensionType['notificationTypes'] as $notificationKey )
		{
			Db::i()->insert( 'core_notification_preferences', array(
				'member_id'			=> Member::loggedIn()->member_id,
				'notification_key'	=> $notificationKey,
				'preference'		=> implode( ',', $value )
			), TRUE );
		}

		// Get the data we need to return
		$methods = array('inline' => array(), 'email' => array(), 'push' => array());

		foreach( $methods as $method => $methodData )
		{
			if( !isset( $options[ $method ] ) )
			{
				continue;
			}

			$option = $options[ $method ];
			$methods[ $method ]['default'] = isset( $option['default'] ) && in_array( $method, $option['default'] );
			$methods[ $method ]['disabled'] = isset( $option['disabled'] ) && in_array( $method, $option['disabled'] );
			$methods[ $method ]['member'] = isset( $value[ $method ] );
		}

		return array(
			'id' => $args['id'],
			'extension' => $args['extension'],
			'type' => $type,
			'name' => $extensionType['title'],
			'description' => $extensionType['description'],
			'inline' => $methods['inline'],
			'email' => $methods['email'],
			'push' => $methods['push']
		);
	}
}

