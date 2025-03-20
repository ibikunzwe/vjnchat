<?php
/**
 * @brief		GraphQL: Tag Type
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		7 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\core\api\GraphQL\Types;
use GraphQL\Type\Definition\ObjectType;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Tag;
use IPS\Http\Url;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * TagType for GraphQL API
 */
class TagType extends ObjectType
{
	/**
	 * Get object type
	 *
	 */
	public function __construct()
	{
		$config = [
			'name' => 'core_Tag',
			'description' => 'A community tag',
			'fields' => function () {
				return [
					'name' => [
                        'type' => TypeRegistry::string(),
                        'description' => "Tag name",
                        'resolve' => function ($tag) {
                            return $tag;
                        }
                    ],
					'url' => [
                        'type' => TypeRegistry::string(),
                        'description' => "Tag URL",
                        'resolve' => function ($tag) {
							return Tag::buildTagUrl( $tag );
                        }
                    ]
				];
			}
		];

		parent::__construct($config);
	}
}
