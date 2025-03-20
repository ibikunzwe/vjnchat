<?php
/**
 * @brief		GraphQL: Calendar mutations
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		22 Oct 2022
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\calendar\api\GraphQL;
use IPS\calendar\api\GraphQL\Mutations\CreateEvent;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * Blog mutationss GraphQL API
 */
abstract class Mutation
{
    /**
     * Get the supported query types in this app
     *
     * @return	array
     */
    public static function mutations(): array
    {
        return [
            'createEvent' => new CreateEvent(),
        ];
    }
}
