<?php
/**
 * @brief		Member Restrictions: Gallery
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Nov 2017
 */

namespace IPS\gallery\extensions\core\MemberRestrictions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\MemberACPProfile\Restriction;
use IPS\Helpers\Form;
use IPS\Helpers\Form\YesNo;
use IPS\Member;
use function defined;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Member Restrictions: Gallery
 */
class Gallery extends Restriction
{
	/**
	 * Modify Edit Restrictions form
	 *
	 * @param	Form	$form	The form
	 * @return	void
	 */
	public function form( Form $form ) : void
	{
		$form->add( new YesNo( 'remove_gallery_access', !$this->member->members_bitoptions['remove_gallery_access'], FALSE, array( 'togglesOn' => array( 'remove_gallery_upload' ) ) ) );
		$form->add( new YesNo( 'remove_gallery_upload', !$this->member->members_bitoptions['remove_gallery_upload'], FALSE, array(), NULL, NULL, NULL, 'remove_gallery_upload' ) );
	}
	
	/**
	 * Save Form
	 *
	 * @param	array	$values	Values from form
	 * @return	array
	 */
	public function save( array $values ): array
	{
		$return = array();
		
		if ( $this->member->members_bitoptions['remove_gallery_access'] == $values['remove_gallery_access'] )
		{
			$return['remove_gallery_access'] = array( 'old' => $this->member->members_bitoptions['remove_gallery_access'], 'new' => !$values['remove_gallery_access'] );
			$this->member->members_bitoptions['remove_gallery_access'] = !$values['remove_gallery_access'];
		}
		
		if ( $this->member->members_bitoptions['remove_gallery_upload'] == $values['remove_gallery_upload'] )
		{
			$return['remove_gallery_upload'] = array( 'old' => $this->member->members_bitoptions['remove_gallery_upload'], 'new' => !$values['remove_gallery_upload'] );
			$this->member->members_bitoptions['remove_gallery_upload'] = !$values['remove_gallery_upload'];
		}
		
		return $return;
	}
	
	/**
	 * What restrictions are active on the account?
	 *
	 * @return	array
	 */
	public function activeRestrictions(): array
	{
		$return = array();
		
		if ( $this->member->members_bitoptions['remove_gallery_access'] )
		{
			$return[] = 'restriction_no_gallery_access';
		}
		elseif ( $this->member->members_bitoptions['remove_gallery_upload'] )
		{
			$return[] = 'restriction_no_gallery_uploads';
		}
		
		return $return;
	}

	/**
	 * Get details of a change to show on history
	 *
	 * @param	array	$changes	Changes as set in save()
	 * @param   array   $row        Row of data from member history table.
	 * @return	array
	 */
	public static function changesForHistory( array $changes, array $row ): array
	{
		$return = array();
		
		foreach ( array( 'remove_gallery_access', 'remove_gallery_upload' ) as $k )
		{
			if ( isset( $changes[ $k ] ) )
			{
				$return[] = Member::loggedIn()->language()->addToStack( 'history_restrictions_' . $k . '_' . intval( $changes[ $k ]['new'] ) );
			}
		}
		
		return $return;
	}
}