<?php
/**
 * @brief		Setup Language Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Lang\Setup;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Db;
use UnderflowException;
use function count;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Setup Language Class
 */
class Lang extends \IPS\Lang
{
	/**
	 * Add to output stack
	 *
	 * @param string $key	Language key
	 * @param bool|null $vle	Add VLE tags?
	 * @param array $options Options
	 * @return	string	Unique id
	 */
	public function addToStack( string $key, ?bool $vle=TRUE, array $options=array() ): string
	{
		/* if we don't have any options, there's no need to add it to the stack */
		if( count($options) == 0 )
		{
			return ( isset( $this->words[ $key ] ) ) ? $this->words[ $key ] : $key;
		}

		$id = md5( 'ipslang_' . self::$outputSalt . $key . json_encode( $options ) );
		$this->outputStack[ $id ]['key']		= $key;
		$this->outputStack[ $id ]['options']	= $options;
		$this->outputStack[ $id ]['vle']		= $vle;

		/* Return */
		return $id;
	}

	/**
	 * Parse output and replace language keys
	 *
	 * @param string $output	Unparsed
	 * @return	void
	 */
	public function parseOutputForDisplay( mixed &$output ) : void
	{
		/* Do we actually have any? */
		if( !count( $this->outputStack ) )
		{
			return;
		}

		$this->outputStack = array_reverse( $this->outputStack );

		$this->replaceWords( $output );
	}
}