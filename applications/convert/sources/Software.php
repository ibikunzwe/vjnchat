<?php

/**
 * @brief		Converter Software Core Class
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @package		Invision Community
 * @subpackage	convert
 * @since		21 Jan 2015
 */

namespace IPS\convert;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use Exception;
use InvalidArgumentException;
use IPS\Data\Store;
use IPS\Db;
use IPS\Db\Exception as DbException;
use IPS\Db\Select;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use function defined;
use function get_class;
use function in_array;
use function is_null;
use function is_string;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Converter Software Class
 */
abstract class Software
{
	/**
	 * @brief	\IPS\convert\App instance
	 */
	public ?App $app			= NULL;
	
	/**
	 * @brief	\IPS\Db instance to the source application
	 */
	public ?Db $db			= NULL;
	
	/**
	 * @brief	Is UTF8
	 */
	public bool $isUtfEight	= TRUE;
	
	/**
	 * @brief	Flag to indicate the post data has been fixed during conversion, and we only need to use Legacy Parser
	 */
	public static bool $contentFixed = FALSE;

	/**
	 * @var Software|string|null
	 */
	public Software|string|null $parent = null;
	
	/**
	 * Constructor
	 *
	 * @param App $app	The application to reference for database and other information.
	 * @param bool $needDB	If the database is needed or not.
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public function __construct( App $app, bool $needDB=TRUE )
	{
		$this->app = $app;
		
		/* If we have a parent and no DB details we need to use that instead */
		if ( $this->app->parent > 0 AND !$this->app->db_host )
		{
			$this->app->db_host 	= $this->app->_parent->db_host;
			$this->app->db_port     = $this->app->_parent->db_port;
			$this->app->db_user		= $this->app->_parent->db_user;
			$this->app->db_pass		= $this->app->_parent->db_pass;
			$this->app->db_db		= $this->app->_parent->db_db;
			$this->app->db_prefix	= $this->app->_parent->db_prefix;
			$this->app->db_charset	= $this->app->_parent->db_charset;
		}
		
		$connectionSettings = array(
			'sql_host'			=> $this->app->db_host,
			'sql_port'          => $this->app->db_port,
			'sql_user'			=> $this->app->db_user,
			'sql_pass'			=> $this->app->db_pass,
			'sql_database'		=> $this->app->db_db,
			'sql_tbl_prefix'	=> $this->app->db_prefix,
		);
		
		if ( $this->app->db_charset === 'utf8mb4' )
		{
			$connectionSettings['sql_utf8mb4'] = TRUE;
		}
		
		//-----------------------------------------
		// Are we connected?
		// (in the great circle of life...)
		//-----------------------------------------
		/* If we are rebulding, we don't actually need to establish a connection to the source database. This will both help improve speed, as well as avoid an exception if the connection no longer works */
		if ( $needDB )
		{
			try
			{
				$this->db = Db::i( 'convert_' . $this->app->app_id, $connectionSettings );
				
				/* Are we utf8? */
				if ( !in_array( $this->app->db_charset, array( 'utf8', 'utf8mb4' ) ) )
				{
					/* Get all db charsets */
					$charsets = static::getDatabaseCharsets( $this->db );

					/* Set a flag that the data is not UTF8... we need to convert it to UTF8 at conversion time. */
					if ( !in_array( mb_strtolower( $this->app->db_charset ), $charsets ) )
					{
						/* @todo We need to use a comprehensive conversion system like the UTF8 Converter... or maybe we can provide instructions on how to use it on non-IPS databases if MB can't do it? */
						throw new InvalidArgumentException( 'invalid_charset' );
					}
					
					$this->isUtfEight = FALSE;
					$this->db->set_charset( $this->app->db_charset );
				}
			}
			catch( DbException $e )
			{
				throw new InvalidArgumentException( "Database Connection Failed: " . $e->getMessage() );
			}
		}
	}
	
	/**
	 * Magic __call() method
	 *
	 * @param	string	$name			The method to call without convert prefix.
	 * @param	mixed	$arguments		Arguments to pass to the method
	 * @return 	mixed
	 */
	public function __call( string $name, mixed $arguments )
	{
		if ( method_exists( $this, 'convert' . $name ) )
		{
			$function = 'convert' . $name;
			return $this->$function( $arguments );
		}
		elseif ( method_exists( $this, $name ) )
		{
			return $this->$name( $arguments );
		}
		else
		{
			Log::log( "Call to undefined method in " . get_class( $this ) . "::{$name}", 'converters' );
			return NULL;
		}
	}
	
	/**
	 * Software
	 *
	 * @return	array
	 */
	public static function software() : array
	{
		return array(
			'core'			=> array(
				'expressionengine'		=> 'IPS\convert\Software\Core\Expressionengine',
				'invisioncommunity'		=> 'IPS\convert\Software\Core\Invisioncommunity',
				'joomla'				=> 'IPS\convert\Software\Core\Joomla',
				'mybb'					=> 'IPS\convert\Software\Core\Mybb',
				'photopost'				=> 'IPS\convert\Software\Core\Photopost',
				'phpbb'					=> 'IPS\convert\Software\Core\Phpbb',
				'phpmyforum'			=> 'IPS\convert\Software\Core\Phpmyforum',
				'punbb'					=> 'IPS\convert\Software\Core\Punbb',
				'smf'					=> 'IPS\convert\Software\Core\Smf',
				'ubbthreads'			=> 'IPS\convert\Software\Core\UBBthreads',
				'vanilla'				=> 'IPS\convert\Software\Core\Vanilla',
				'vbulletin'				=> 'IPS\convert\Software\Core\Vbulletin',
				'vbulletin5'			=> 'IPS\convert\Software\Core\Vbulletin5',
				'woltlab'				=> 'IPS\convert\Software\Core\Woltlab',
				'wordpress'				=> 'IPS\convert\Software\Core\Wordpress',
				'wpforo'				=> 'IPS\convert\Software\Core\Wpforo',
				'xenforo'				=> 'IPS\convert\Software\Core\Xenforo',
			),
			'blog'			=> array(
				'vbulletin'				=> 'IPS\convert\Software\Blog\Vbulletin',
				'invisioncommunity'		=> 'IPS\convert\Software\Blog\Invisioncommunity',
			),
			'calendar'		=> array(
				'mybb'					=> 'IPS\convert\Software\Calendar\Mybb',
				'vbulletin'				=> 'IPS\convert\Software\Calendar\Vbulletin',
				'invisioncommunity'		=> 'IPS\convert\Software\Calendar\Invisioncommunity',
			),
			'cms'			=> array(
				'joomla'				=> 'IPS\convert\Software\Cms\Joomla',
				'vbulletin'				=> 'IPS\convert\Software\Cms\Vbulletin',
				'wordpress'				=> 'IPS\convert\Software\Cms\Wordpress',
				'xenforo'				=> 'IPS\convert\Software\Cms\Xenforo',
				'xenfororm'				=> 'IPS\convert\Software\Cms\Xenfororm',
			),
			'downloads'		=> array(
				'invisioncommunity'		=> 'IPS\convert\Software\Downloads\Invisioncommunity',
				'xenforo'				=> 'IPS\convert\Software\Downloads\Xenforo',
			),
			'forums'		=> array(
				'bbpress'				=> 'IPS\convert\Software\Forums\Bbpress',
				'expressionengine'		=> 'IPS\convert\Software\Forums\Expressionengine',
				'invisioncommunity'		=> 'IPS\convert\Software\Forums\Invisioncommunity',
				'mybb'					=> 'IPS\convert\Software\Forums\Mybb',
				'phpbb'					=> 'IPS\convert\Software\Forums\Phpbb',
				'phpmyforum'			=> 'IPS\convert\Software\Forums\Phpmyforum',
				'punbb'					=> 'IPS\convert\Software\Forums\Punbb',
				'smf'					=> 'IPS\convert\Software\Forums\Smf',
				'ubbthreads'			=> 'IPS\convert\Software\Forums\UBBthreads',
				'vanilla'				=> 'IPS\convert\Software\Forums\Vanilla',
				'vbulletin'				=> 'IPS\convert\Software\Forums\Vbulletin',
				'vbulletin5'			=> 'IPS\convert\Software\Forums\Vbulletin5',
				'woltlab'				=> 'IPS\convert\Software\Forums\Woltlab',
				'wpforo'				=> 'IPS\convert\Software\Forums\Wpforo',
				'xenforo'				=> 'IPS\convert\Software\Forums\Xenforo',
			),
			'gallery'		=> array(
				'coppermine'			=> 'IPS\convert\Software\Gallery\Coppermine',
				'invisioncommunity'		=> 'IPS\convert\Software\Gallery\Invisioncommunity',
				'photoplog'				=> 'IPS\convert\Software\Gallery\Photoplog',
				'photopost'				=> 'IPS\convert\Software\Gallery\Photopost',
				'vbulletin'				=> 'IPS\convert\Software\Gallery\Vbulletin',
				'xenforo'				=> 'IPS\convert\Software\Gallery\Xenforo',
			),
			'nexus'			=> array()
		);
	}
	
	/**
	 * Software Name
	 *
	 * @return    string
	 * @throws	BadMethodCallException
	 */
	public static function softwareName(): string
	{
		/* Child classes must override this method */
		throw new BadMethodCallException( 'no_name' );
	}
	
	/**
	 * Software Key
	 *
	 * @return    string
	 * @throws	BadMethodCallException
	 */
	public static function softwareKey(): string
	{
		/* Child classes must override this method */
		throw new BadMethodCallException( 'no_key' );
	}
	
	/**
	 * Requires Parent
	 *
	 * @return    bool
	 */
	public static function requiresParent(): bool
	{
		return FALSE;
	}
	
	/**
	 * Possible Parent Conversions
	 *
	 * @return    array|NULL
	 */
	public static function parents(): ?array
	{
		return NULL;
	}
	
	/**
	 * Uses Prefix
	 *
	 * @return    bool
	 */
	public static function usesPrefix(): bool
	{
		return TRUE;
	}
	
	/**
	 * Content we can convert from this software. 
	 *
	 * @return    array|NULL
	 * @throws	BadMethodCallException
	 */
	public static function canConvert(): ?array
	{
		/* Child classes must override this method */
		throw new BadMethodCallException( 'nothing_to_convert' );
	}
	
	/**
	 * Can we translate settings over to our Invision Community equivalents?
	 *
	 * @return    bool
	 */
	public static function canConvertSettings(): bool
	{
		return FALSE;
	}
	
	/**
	 * Settings Map
	 *
	 * @code
	 	* return array(
		 	* 'source_setting_key' => array( 'ips_setting_key' );
	 * @endcode
	 * @return    array
	 */
	public function settingsMap(): array
	{
		return array();
	}
	
	/**
	 * Settings Map List
	 *
	 * @code
	 	* return array(
		 	* 'source_setting_key' => array(
			 	* 'title'		=> 'Human Readable Name as presented in the source',
			 	* 'value'		=> 'The value from the source',
		 * );
	 * @endcode
	 * @return    array
	 */
	public function settingsMapList():array
	{
		return array();
	}
	
	/**
	 * List of Conversion Methods that require more information
	 *
	 * @return    array
	 */
	public static function checkConf(): array
	{
		return array();
	}
	
	/**
	 * An array of steps which require more information. This method should return array like below if any steps require additional information. Otherwise, it should return NULL.
	 *
	 * @param string $method Conversion method
	 * @return    array|NULL
	 *
	 * @code
	 	* return array(
		 	* 'convertAttachments' => array(
			 * 'upload_path'	=> array( // The key is the name of the field, or the first parameter of the helper class constructor.
				* 'field_class' 		=> 'IPS\\Helpers\\Form\\Text', // The form helper class for this field
				* 'field_default'		=> NULL, // The default value for this field.
				* 'field_required'	=> TRUE, // TRUE if this field is required.
				* 'field_extra'		=> array(), // Array of extra data that would normally go in $options for the form helper.
				* 'field_hint'		=> NULL, // A language key of hint text to display after the form (such as path to IPS Suite files). NULL for no additional text.
			 * ),
			* )
		* );
	 * @endcode
	 */
	public function getMoreInfo( string $method ):?array
	{
		return NULL;
	}
	
	/**
	 * Can we convert passwords from this software.
	 *
	 * @return    bool
	 */
	public static function loginEnabled(): bool
	{
		return FALSE;
	}
	
	/**
	 * Count Source Rows for a specific step
	 *
	 * @param string $table		The table containing the rows to count.
	 * @param string|array|NULL $where		WHERE clause to only count specific rows, or NULL to count all.
	 * @param bool $recache	Skip cache and pull directly (updating cache)
	 * @return    int
	 * @throws    Exception
	 */
	public function countRows( string $table, string|array|null $where=NULL, bool $recache=FALSE ): int
	{
		try
		{
			$cacheKey = 'convert_' . $this->app->app_id . '_' . md5( $table . json_encode( $where ) );

			if( !isset( Store::i()->$cacheKey ) OR $recache === TRUE )
			{
				Store::i()->$cacheKey = $this->db->select( 'COUNT(*)', $table, $where )->first();
			}

			return Store::i()->$cacheKey;
		}
		catch( Exception $e )
		{
			throw new Exception( sprintf( Member::loggedIn()->language()->get( 'could_not_count_rows' ), $table ) );
		}
	}
	
	/**
	 * Get Library Class
	 *
	 * @param string|NULL $library	Specific library to fetch
	 * @return    Library
	 * @throws	InvalidArgumentException
	 */
	public function getLibrary( ?string $library=NULL ): Library
	{
		$library = $library ?: $this->app->sw;
		
		$classname = Library::libraries()[ $library ];
		
		if ( ! class_exists( $classname ) )
		{
			throw new InvalidArgumentException( 'invalid_library' );
		}
		
		return new $classname( $this );
	}

	/**
	 * Allows software to add additional menu row options
	 *
	 * @return    array
	 */
	public function extraMenuRows(): array
	{
		return array();
	}
	
	/**
	 * Returns a block of text, or a language string, that explains what the admin must do to start this conversion
	 *
	 * @return    string|NULL
	 */
	public static function getPreConversionInformation(): ?string
	{
		return NULL;
	}
	
	/**
	 * Fetch Remote Data
	 *
	 * @param string|array $table		The table to selected data from
	 * @param string $idColumn	The ID column to sort on, when not using keys.
	 * @param string|array|NULL $where		WHERE clause for specific data to fetch.
	 * @param string $what		What to fetch
	 * @return    Select    An \IPS\Db\Select object that can be further manipulated if necessary (e.g. joins)
	 */
	public function fetch( string|array $table, string $idColumn='id', string|array|null $where=NULL, string $what='*' ): Select
	{
		$libraryClass = $this->getLibrary();
		if ( $libraryClass::$usingKeys === FALSE )
		{
			return $this->db->select( $what, $table, $where, $idColumn . ' ASC', array( $libraryClass::$startValue, $libraryClass::$perCycle ) );
		}
		else
		{
			if ( !isset( $_SESSION['currentKeyValue'] ) )
			{
				$libraryClass->setLastKeyValue( 0 );
			}
			
			$whereClause		= array();
			$whereClause[]	= array( $libraryClass::$currentKeyName . '>?', $_SESSION['currentKeyValue'] );
			
			if ( !is_null( $where ) )
			{
				if ( is_string( $where ) )
				{
					$whereClause[] = array( $where );
				}
				else
				{
					$whereClause[] = $where;
				}
			}

			return $this->db->select( $what, $table, $whereClause, $libraryClass::$currentKeyName . ' ASC', array( 0, $libraryClass::$perCycle ) );
		}
	}

	
	/**
	 * Finish - Adds everything it needs to the queues and clears data store
	 *
	 * @return    array        Messages to display
	 */
	public function finish(): array
	{
		return array();
	}

	/**
	 * Pre-process content for the Invision Community text parser
	 *
	 * @param	string			$post	The post
	 * @param string|null $className	Content Classname passed by post-conversion rebuild
	 * @param	int|null		$contentId ID passed by post-conversion rebuild
	 * @return	string			The converted post
	 */
	public static function fixPostData( string $post, ?string $className=null, ?int $contentId=null ): string
	{
		return $post;
	}

	/**
	 * Get database charsets
	 *
	 * @param	Db		$database	Database connection
	 * @return	array
	 */
	public static function getDatabaseCharsets( Db $database ) : array
	{
		$charsets = array();
		$result   = $database->query( "SHOW CHARACTER SET;" );

		while( $row = $result->fetch_assoc() )
		{
			$charsets[] = mb_strtolower( $row['Charset'] );
		}
		
		return $charsets;
	}

	/**
	 * Check if we can redirect the legacy URLs from this software to the new locations
	 *
	 * @return    Url|NULL
	 */
	public function checkRedirects(): ?Url
	{
		return NULL;
	}
}