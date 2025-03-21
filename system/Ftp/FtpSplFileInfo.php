<?php
/**
 * @brief		FTP SplFileInfo Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		30 Oct 2013
 */

namespace IPS\Ftp;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use SplFileInfo;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * FTP file information iterator
 */
class FtpSplFileInfo extends SplFileInfo
{
	/**
	 * @brief   Type: Unknown
	 */
	const TYPE_UNKNOWN		= 1;

	/**
	 * @brief   Type: Directory
	 */
	const TYPE_DIRECTORY	= 2;

	/**
	 * @brief   Type: File
	 */
	const TYPE_FILE			= 3;

	/**
	 * @brief   Type: Symlink
	 */
	const TYPE_LINK			= 4;

	/**
	 * @brief   Current item type
	 */
	protected int $type			= self::TYPE_DIRECTORY;

	/**
	 * @brief   Current directory
	 */
	protected mixed $directory	= '/';

	/**
	 * @brief   Current filename
	 */
	protected mixed $filename		= '.';

	/**
	 * Constructor: Create a new spl file info object
	 *
	 * @param   string  $file   Filename or directory name
	 * @param int $type   The type of item passed
	 * @return  void
	 */
	public function __construct( string $file, int $type = self::TYPE_DIRECTORY )
	{
		$this->type = $type;

		if( $type === self::TYPE_DIRECTORY )
		{
			$this->filename		= '.';
			$this->directory	= $file;
		}
		else if( $type === self::TYPE_FILE )
		{
			$tmp = self::parseFile( $file );

			$this->filename		= $tmp['filename'];
			$this->directory	= $tmp['directory'];
		}

		parent::__construct( $this->getPathname() );
	}

	/**
	 * Is this a directory?
	 *
	 * @return bool
	 */
	public function isDir(): bool
	{
		return ( $this->type === self::TYPE_DIRECTORY );
	}

	/**
	 * Is this a file?
	 *
	 * @return bool
	 */
	public function isFile(): bool
	{
		return ( $this->type === self::TYPE_FILE );
	}

	/**
	 * Return the type
	 *
	 * @return string|false
	 */
	public function getType(): string|false
	{
		return $this->type;
	}

	/**
	 * Return the directory
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->directory;
	}

	/**
	 * Return the filename
	 *
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->filename;
	}

	/**
	 * Return the full filename with the path
	 *
	 * @return string
	 */
	public function getPathname(): string
	{
		if( $this->isDir() )
		{
			return $this->getPath();
		}
		else
		{
			return $this->getItemname( $this->filename );
		}
	}

	/**
	 * Get the current item name
	 * 
	 * @param	string	$file	The filename or directory name
	 * @return	string
	 */
	public function getItemname( string $file ): string
	{
		if( $this->directory === '/' AND $file === '.' )
		{
			return $this->directory;
		}

		return ( ( $this->directory === '/' ) ? ( $this->directory . $file ) : ( $this->directory . '/' . $file ) );
	}

	/**
	 * Parse a raw list item to extract the type
	 *
	 * @param	mixed	$item	The item we are parsing, which could be a file or directory (or unknown)
	 * @return	int
	 */
	public static function getTypeFromRaw( mixed $item ): int
	{
		if( $item === '' )
		{
			return self::TYPE_UNKNOWN;
		}

		switch( $item[0] )
		{
			case 'd':
				return self::TYPE_DIRECTORY;

			case '-':
				return self::TYPE_FILE;

			case 'l':
				return self::TYPE_LINK;

			default:
				return self::TYPE_UNKNOWN;
		}
	}

	/**
	 * Parse a raw list item to extract the needed information
	 *
	 * @param string $file	The item we are parsing, which could be a file or directory (or unknown)
	 * @return	array	( 'filename' => ..., 'directory' => ... )
	 * @throws	InvalidArgumentException
	 */
	public static function parseFile( string $file = '/' ): array
	{
		if( mb_strpos( $file, '/' ) !== 0 )
		{
			throw new InvalidArgumentException( sprintf( 'File "%s" does not start with a /', $file ) );
		}

		if( mb_strlen( $file ) < 2 )
		{
			throw new InvalidArgumentException( sprintf( 'File "%s" must contain at least two characters', $file ) );
		}

		$pathBreak	= mb_strrpos( $file, '/' );

		return array(
			'filename'	=> mb_substr( $file, $pathBreak + 1 ),
			'directory'	=> ( mb_substr( $file, 0, $pathBreak ) === '' ) ? '/' : mb_substr( $file, 0, $pathBreak )
		);
	}

	/**
	 * If we cast to a string, return the full filename
	 *
	 * @return string
	 */
	public function __toString() : string
	{
		return $this->getPathname();
	}
}