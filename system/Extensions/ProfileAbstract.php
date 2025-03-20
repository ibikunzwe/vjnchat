<?php

/**
 * @brief        ProfileAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/20/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Member;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ProfileAbstract
{
	/**
	 * Member
	 */
	protected ?Member $member = null;

	/**
	 * Constructor
	 *
	 * @param	Member	$member	Member whose profile we are viewing
	 * @return	void
	 */
	public function __construct( Member $member )
	{
		$this->member = $member;
	}

	/**
	 * Is there content to display?
	 *
	 * @return	bool
	 */
	abstract public function showTab(): bool;

	/**
	 * Display
	 *
	 * @return	string
	 */
	abstract public function render(): string;
}