<?php
/**
 * @brief		Member filter extension: Reputation
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		22 Mar 2017
 */

namespace IPS\core\extensions\core\MemberFilter;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Extensions\MemberFilterAbstract;
use IPS\Helpers\Form\Custom;
use IPS\Member;
use IPS\Theme;
use LogicException;
use function defined;
use function in_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member filter: Reputation
 */
class Reputation extends MemberFilterAbstract
{
	/**
	 * Determine if the filter is available in a given area
	 *
	 * @param	string	$area	Area to check (bulkmail, group_promotions, automatic_moderation, passwordreset)
	 * @return	bool
	 */
	public function availableIn( string $area ): bool
	{
		return in_array( $area, array( 'bulkmail', 'group_promotions', 'automatic_moderation' ) );
	}

	/**
	 * Get Setting Field
	 *
	 * @param array $criteria	Value returned from the save() method
	 * @return	array 	Array of form elements
	 */
	public function getSettingField( array $criteria ): array
	{
		return array(
			new Custom( 'mf_reputation', array( 0 => $criteria['reputation_operator'] ?? NULL, 1 => $criteria['reputation_score'] ?? NULL ), FALSE, array(
				'getHtml'	=> function( $element )
				{
					return Theme::i()->getTemplate( 'forms', 'core' )->select( "{$element->name}[0]", $element->value[0], $element->required, array(
						'any'	=> Member::loggedIn()->language()->addToStack('any'),
						'gt'	=> Member::loggedIn()->language()->addToStack('gt'),
						'lt'	=> Member::loggedIn()->language()->addToStack('lt'),
						'eq'	=> Member::loggedIn()->language()->addToStack('exactly'),
					),
						FALSE,
						NULL,
						FALSE,
						array(
							'any'	=> array(),
							'gt'	=> array( 'elNumber_' . $element->name . '-qty' ),
							'lt'	=> array( 'elNumber_' . $element->name . '-qty' ),
							'eq'	=> array( 'elNumber_' . $element->name . '-qty' ),
						) )
					. ' '
					. Theme::i()->getTemplate( 'forms', 'core', 'global' )->number( "{$element->name}[1]", $element->value[1], $element->required, NULL, FALSE, NULL, NULL, NULL, 0, NULL, FALSE, NULL, array(), array(), array(), $element->name . '-qty' );
				}
			) )
		);
	}
	
	/**
	 * Save the filter data
	 *
	 * @param	array	$post	Form values
	 * @return	array			False, or an array of data to use later when filtering the members
	 * @throws LogicException
	 */
	public function save( array $post ): array
	{
		return array( 'reputation_operator' => $post['mf_reputation'][0], 'reputation_score' => $post['mf_reputation'][1] );
	}
	
	/**
	 * Get where clause to add to the member retrieval database query
	 *
	 * @param array $data	The array returned from the save() method
	 * @return	array|NULL			Where clause - must be a single array( "clause" )
	 */
	public function getQueryWhereClause( array $data ): ?array
	{
		if ( $data['reputation_operator'] and $data['reputation_score'] )
		{
			switch ( $data['reputation_operator'] )
			{
				case 'gt':
					return array( "pp_reputation_points > " . (int) $data['reputation_score'] );

				case 'lt':
					return array( "pp_reputation_points < " . (int) $data['reputation_score'] );

				case 'eq':
					return array( "pp_reputation_points= " . (int) $data['reputation_score'] );

			}
		}

		return NULL;
	}

	/**
	 * Determine if a member matches specified filters
	 *
	 * @note	This is only necessary if availableIn() includes group_promotions
	 * @param	Member	$member		Member object to check
	 * @param	array 		$filters	Previously defined filters
	 * @param	object|NULL	$object		Calling class
	 * @return	bool
	 */
	public function matches( Member $member, array $filters, ?object $object=NULL ) : bool
	{
		/* If we aren't filtering by this, then any member matches */
		if( !isset( $filters['reputation_operator'] ) OR !$filters['reputation_operator'] OR !isset( $filters['reputation_score'] ) OR !$filters['reputation_score'] )
		{
			return TRUE;
		}

		switch ( $filters['reputation_operator'] )
		{
			case 'gt':
				return ( $member->pp_reputation_points > (int) $filters['reputation_score'] );

			case 'lt':
				return ( $member->pp_reputation_points < (int) $filters['reputation_score'] );

			case 'eq':
				return ( $member->pp_reputation_points == (int) $filters['reputation_score'] );

		}

		/* If we are still here, then there wasn't an appropriate operator (maybe they selected 'any') so return true */
		return TRUE;
	}
	
	/**
	 * Return a lovely human description for this rule if used
	 *
	 * @param	array				$filters	The array returned from the save() method
	 * @return	string|NULL
	 */
	public function getDescription( array $filters ) : ?string
	{
		if ( ! empty( $filters['reputation_score'] ) and $filters['reputation_score'] > 0 )
		{
			switch ( $filters['reputation_operator'] )
			{
				case 'gt':
					return Member::loggedIn()->language()->addToStack( 'member_filter_core_reputation_gt_desc', FALSE, array( 'sprintf' => array( $filters['reputation_score'] ) ) );

				case 'lt':
					return Member::loggedIn()->language()->addToStack( 'member_filter_core_reputation_lt_desc', FALSE, array( 'sprintf' => array( $filters['reputation_score'] ) ) );

				case 'eq':
					return Member::loggedIn()->language()->addToStack( 'member_filter_core_reputation_eq_desc', FALSE, array( 'sprintf' => array( $filters['reputation_score'] ) ) );

			}
		}
		
		return NULL;
	}
}