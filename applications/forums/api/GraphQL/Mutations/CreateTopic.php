<?php
/**
 * @brief		GraphQL: Create topic mutation
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @since		10 May 2017
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\forums\api\GraphQL\Mutations;
use IPS\Api\GraphQL\SafeException;
use IPS\Api\GraphQL\TypeRegistry;
use IPS\Content\Api\GraphQL\ItemMutator;
use IPS\Content\Item;
use IPS\forums\api\GraphQL\Types\TopicType;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\Member;
use OutOfRangeException;
use function defined;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Create topic mutation for GraphQL API
 */
class CreateTopic extends ItemMutator
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\forums\Topic';

	/*
	 * @brief 	Query description
	 */
	public static string $description = "Create a new topic";

	/*
	 * Mutation arguments
	 */
	public function args(): array
	{
		return [
			'forumID' => TypeRegistry::nonNull( TypeRegistry::id() ),
			'title' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'content' => TypeRegistry::nonNull( TypeRegistry::string() ),
			'tags' => TypeRegistry::listOf( TypeRegistry::string() ),
			'state' => TypeRegistry::itemState(),
			'postKey' => TypeRegistry::string()
		];
	}

	/**
	 * Return the mutation return type
	 *
	 * @return TopicType
	 */
	public function type()  : TopicType
	{
		return \IPS\forums\api\GraphQL\TypeRegistry::topic();
	}

	/**
	 * Resolves this query
	 *
	 * @param 	mixed $val 	Value passed into this resolver
	 * @param 	array $args 	Arguments
	 * @param 	array $context 	Context values
	 * @param	mixed $info
	 * @return	Topic
	 */
	public function resolve( mixed $val, array $args, array $context, mixed $info ) : Item
	{
		/* Get forum */
		try
		{
			$forum = Forum::loadAndCheckPerms( $args['forumID'] );
		}
		catch ( OutOfRangeException $e )
		{
			throw new SafeException( 'NO_FORUM', '1F294/2_graphl', 400 );
		}
		
		/* Check permission */
		if ( !$forum->can( 'add', Member::loggedIn() ) )
		{
			throw new SafeException( 'NO_PERMISSION', '2F294/9_graphl', 403 );
		}
		
		/* Check we have a title and a post */
		if ( !$args['title'] )
		{
			throw new SafeException( 'NO_TITLE', '1F294/5_graphl', 400 );
		}
		if ( !$args['content'] )
		{
			throw new SafeException( 'NO_POST', '1F294/4_graphl', 400 );
		}
		
		
		return $this->_create( $args, $forum, $args['postKey'] ?? NULL );
	}
}
