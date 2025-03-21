<?php
/**
 * @brief		Achievement Action Extension: User posts new content item
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @since		24 Feb 2021
 */

namespace IPS\core\extensions\core\AchievementAction;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Achievements\Actions\ContentAchievementActionAbstract;
use IPS\core\Achievements\Rule;
use IPS\Db;
use IPS\Member;
use IPS\Theme;
use OutOfRangeException;
use function class_exists;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Achievement Action Extension: User posts new content item
 */
class NewContentItem extends ContentAchievementActionAbstract
{
	protected static bool $includeComments = FALSE;
	protected static bool $includeReviews = FALSE;
	
	/**
	 * Work out if the filters applies for a given action
	 *
	 * Important note for milestones: consider the context. This method is called by \IPS\Member::achievementAction(). If your code 
	 * calls that BEFORE making its change in the database (or there is read/write separation), you will need to add
	 * 1 to the value being considered for milestones
	 *
	 * @param	Member	$subject	The subject member
	 * @param	array		$filters	The value returned by formatFilterValues()
	 * @param	mixed		$extra		Any additional information about what is happening (e.g. if a post is being made: the post object)
	 * @return	bool
	 */
	public function filtersMatch( Member $subject, array $filters, mixed $extra = NULL ): bool
	{
		if ( !parent::filtersMatch( $subject, $filters, $extra ) )
		{
			return FALSE;
		}
		
		if ( isset( $filters['milestone'] ) )
		{
			$count = 0;
			$classes = [];
			if ( isset( $filters['type'] ) )
			{
				if ( isset( $filters[ 'nodes_' . str_replace( '\\', '-', $filters['type'] ) ] ) )
				{
					$class = $filters['type'];
					$where = [];

					/* @var array $databaseColumnMap */
					$where[] = [ $class::$databasePrefix . $class::$databaseColumnMap['author'] . '=?', $subject->member_id ];
					$where[] = [ Db::i()->in( $class::$databasePrefix . $class::$databaseColumnMap['container'], $filters[ 'nodes_' . str_replace( '\\', '-', $filters['type'] ) ] ) ];
					
					$count += Db::i()->select( 'COUNT(*)', $class::$databaseTable, $where )->first();
				}
				else
				{
					$classes[] = $filters['type'];
				}

				foreach ( $classes as $class )
				{
					$count += $class::memberPostCount( $subject, TRUE, FALSE );
				}
			}
			else
			{
				/* It's too expensive to get a live count from every single item/comment class available to the suite
				   so lets just use the post count here. It won't be quite as accurate but we're not a bank so whatever */
				$count = $subject->member_posts;
			}
			
			if ( $count < $filters['milestone'] )
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Return a description for this action to show in the log
	 *
	 * @param	string	$identifier	The identifier as returned by identifier()
	 * @param	array	$actor		If the member was the "subject", "other", or both
	 * @return	string
	 */
	public function logRow( string $identifier, array $actor ): string
	{
		$exploded = explode( ':', $identifier );
		
		$sprintf = [];
		try
		{
			if( !class_exists( $exploded[0] ) )
			{
				throw new OutOfRangeException;
			}

			$item = $exploded[0]::load( $exploded[1] );
			$sprintf = [ 'htmlsprintf' => [
				Theme::i()->getTemplate( 'global', 'core', 'global' )->basicUrl( $item->url(), TRUE, $item->mapped('title') ?: $item->indefiniteArticle(), FALSE )
			] ];
		}
		catch ( OutOfRangeException $e )
		{
			$sprintf = [ 'sprintf' => [ Member::loggedIn()->language()->addToStack('modcp_deleted') ] ];
		}
		
		return Member::loggedIn()->language()->addToStack( 'AchievementAction__NewContentItem_log', FALSE, $sprintf );
	}
	
	/**
	 * Get "description" for rule
	 *
	 * @param	Rule	$rule	The rule
	 * @return	string|NULL
	 */
	public function ruleDescription( Rule $rule ): ?string
	{
		$type = $rule->filters['type'] ?? NULL;
		
		$conditions = [];
		if ( isset( $rule->filters['milestone'] ) )
		{
			$conditions[] = Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone', FALSE, [
				'htmlsprintf' => [
					Theme::i()->getTemplate( 'achievements' )->ruleDescriptionBadge( 'milestone', Member::loggedIn()->language()->addToStack( 'achievements_title_filter_milestone_nth', FALSE, [ 'pluralize' => [ $rule->filters['milestone'] ] ] ) )
				],
				'sprintf'		=> [ $type ? Member::loggedIn()->language()->addToStack( $type::$title ) : Member::loggedIn()->language()->addToStack('AchievementAction__NewContentItem_title_generic') ]
			] );
		}
		if ( $nodeCondition = $this->_nodeFilterDescription( $rule ) )
		{
			$conditions[] = $nodeCondition;
		}
		
		return Theme::i()->getTemplate( 'achievements' )->ruleDescription(
			$type ? Member::loggedIn()->language()->addToStack( 'AchievementAction__NewContentItem_title_t', FALSE, [ 'sprintf' => [ Member::loggedIn()->language()->addToStack( $type::$title ) ] ] ) : Member::loggedIn()->language()->addToStack( 'AchievementAction__NewContentItem_title' ),
			$conditions
		);
	}
}