<?php
/**
 * @brief		File Exception Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		26 Mar 2013
 */

namespace IPS\File;

/* To prevent PHP errors (extending class does not exist) revealing path */

use ReflectionClass;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * File Exception Class
 */
class Exception extends RuntimeException
{
	/**
	 * @brief	Cannot open the file
	 */
	const CANNOT_OPEN		= 1;

	/**
	 * @brief	File does not exist
	 */
	const DOES_NOT_EXIST	= 2;

	/**
	 * @brief	Cannot write the file
	 */
	const CANNOT_WRITE		= 3;

	/**
	 * @brief	Cannot copy the file
	 */
	const CANNOT_COPY		= 4;

	/**
	 * @brief	Cannot move the file
	 */
	const CANNOT_MOVE		= 5;

	/**
	 * @brief	Cannot create a directory
	 */
	const CANNOT_MAKE_DIR	= 6;

	/**
	 * @brief	Region is missing (AWS)
	 */
	const MISSING_REGION	= 7;

	/**
	 * @brief	File path
	 */
	public ?string $filepath = NULL;

	/**
	 * @brief	Original filename (used for friendlier errors)
	 */
	public ?string $originalFilename = NULL;

	/**
	 * @brief	Additional error information
	 */
	public ?string $errorMessage = NULL;

	/**
	 * @brief	Additional log information
	 */
	public ?string $extraLog = NULL;

	/**
	 * Constructor
	 *
	 * @param	string		$file			File path
	 * @param	int			$error			One of the defined exception constants
	 * @param	string|null	$originalFilename	The original filename (used for friendlier error messages)
	 * @param	string|null	$errorMessage	Error message language string
	 * @param	string|null	$extraLog		Error information to log
	 * @return	void
	 */
	public function __construct( string $file, int $error, ?string $originalFilename = NULL, ?string $errorMessage = NULL, ?string $extraLog = NULL )
	{
		/* Store the file */
		$this->filepath = $file;
		$this->originalFilename = $originalFilename;

		/* Store additional debug info */
		$this->errorMessage	= $errorMessage;
		$this->extraLog		= $extraLog;

		$message = array_flip( ( new ReflectionClass( __CLASS__ ) )->getConstants() )[ $error ];

		parent::__construct( $message, $error );
	}

	/**
	 * Additional error message info
	 *
	 * @return	string
	 */
	public function extraErrorMessage() : string
	{
		return (string) $this->errorMessage;
	}

	/**
	 * Additional log data?
	 *
	 * @return	string
	 */
	public function extraLogData() : string
	{
		return (string) $this->extraLog;
	}
}