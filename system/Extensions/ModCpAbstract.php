<?php

/**
 * @brief        ModCpAbstract
 * @author        <a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright    (c) Invision Power Services, Inc.
 * @license        https://www.invisioncommunity.com/legal/standards/
 * @package        Invision Community
 * @subpackage
 * @since        11/20/2023
 */

namespace IPS\Extensions;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

abstract class ModCpAbstract
{
	/**
	 * Returns the primary tab key for the navigation bar
	 *
	 * @return	string|null
	 */
	abstract public function getTab() : ?string;

	/**
	 * What do I manage?
	 * Acceptable responses are: content, members, or other
	 *
	 * @return	string
	 */
	abstract public function manageType() : string;

	/**
	 * Manage
	 *
	 * @return	void
	 */
	abstract public function manage() : void;
}