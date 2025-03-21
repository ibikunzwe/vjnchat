<?php
/**
 * @brief		GraphQL: Coverphoto field defintiion
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\Api\GraphQL\Fields;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * ForumType for GraphQL API
 */
abstract class CoverPhotoField
{
	/**
	 * Get root type
	 *
	 * @param string $name
	 * @return    array
	 */
	public static function getDefinition( string $name ): array
	{	 
		return [
			'type' => new ObjectType([
				'name' => $name,
				'description' => 'Returns a cover photo',
				'fields' => [
					'image' => TypeRegistry::string(),
					'offset' => TypeRegistry::int()
				],
				'resolveField' => function( $value, $args, $context, $info ) {
					switch ($info->fieldName)
					{
						case 'image':
							return ( $value->file ) ? (string) $value->file->url : null;
						case 'offset':
							return ( $value->file ) ? $value->offset : null;
					}
					return null;
				}
			]),
			'description' => 'Returns a cover photo',
			'resolve' => function ($thing) {
				return $thing->coverPhoto();
			}
		];
	}
}
