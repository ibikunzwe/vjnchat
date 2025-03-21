<?php
/**
 * @brief		Upgrader
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 May 2014
 * @todo		MAKE DATABASE CHECK RUN BEFORE EVERYTHING ELSE
 */

namespace IPS\core\Setup;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Cache;
use IPS\Data\Store;
use IPS\Data\Store\FileSystem;
use IPS\Db;
use IPS\Db\Exception;
use IPS\File;
use IPS\File\FileSystem as FileSystemClass;
use IPS\Helpers\Form\Upload;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Output;
use IPS\Output\Plugin\Filesize;
use IPS\Request;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use IPS\Widget;
use LogicException;
use RuntimeException;
use UnderflowException;
use function count;
use function defined;
use function file_get_contents;
use function function_exists;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function strtolower;
use const IPS\CIC;
use const IPS\ROOT_PATH;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Upgrader
 */
class Upgrade
{
	/**
	 * System Requirements
	 *
	 * @return	array
	 */
	public static function systemRequirements() : array
	{
		/* We don't need to check the CIC platform */
		if( CIC )
		{
			return array( 'recommendations' => array(), 'requirements' => array() );
		}

		$return = Install::systemRequirements();

		/* MySQL Requirements */
		$return = array_merge_recursive( $return, static::mysqlRequirements( ) );

		$writeablesKey = Member::loggedIn()->language()->addToStack('requirements_file_system');

		/* Writeables */
		if( Db::i()->checkForTable('core_file_storage') )
		{
			$fileSystemItems = array();
			$successfulStorage = FALSE;
			
			$maxChunkSize = Upload::maxChunkSize();
			if ( $maxChunkSize = Upload::maxChunkSize() )
			{
				$maxChunkSize = $maxChunkSize / 1048576;
			}
			
			foreach ( Application::allExtensions( 'core', 'FileStorage', FALSE ) as $k => $v )
			{
				try
				{
					try
					{
						$class = File::getClass( $k );
					}
					catch( RuntimeException $e )
					{
						throw new RuntimeException( 'no_filestorage_class', 115115 );
					}

					if ( !isset( $fileSystemItems[ $class->configurationId ] ) )
					{
						if ( method_exists( $class, 'testSettings' ) )
						{
							$class->testSettings( $class->configuration );
						}
						
						$fileSystemItems[ $class->configurationId ] = array(
							'success'	=> TRUE,
							'message'	=> ( $class instanceof FileSystemClass ) ? Member::loggedIn()->language()->addToStack( 'requirements_file_writable', FALSE, array( 'sprintf' => array( str_replace( '{root}', ROOT_PATH, $class->configuration['dir'] ) ) ) ) : $class->displayName( $class->configuration )
						);

						if ( $maxChunkSize and $class::$supportsChunking and isset( $class::$minChunkSize ) and $class::$minChunkSize > $maxChunkSize )
						{
							$maxChunkSizeValues = [];
							foreach ( Upload::maxChunkSizeValues() as $_k => $_v )
							{
								if ( ( $_v / 1048576 ) < $class::$minChunkSize )
								{
									$maxChunkSizeValues[] = Member::loggedIn()->language()->addToStack( 'file_storage_test_chunk_mismatch_value', FALSE, array( 'sprintf' => array( $_k, Filesize::humanReadableFilesize( $_v ) ) ) );
								}
							}
							$maxChunkSizeValues = Member::loggedIn()->language()->formatList( $maxChunkSizeValues );
							
							$return['advice'][ $writeablesKey ]["filesystem{$class->configurationId}"] = Member::loggedIn()->language()->addToStack( 'file_storage_test_chunk_mismatch', FALSE, array( 'sprintf' => array( $class->displayName( $class->configuration ), Filesize::humanReadableFilesize( $maxChunkSize * 1048576 ), Filesize::humanReadableFilesize( $class::$minChunkSize * 1048576 ), $maxChunkSizeValues ) ) );
						}

						$successfulStorage = TRUE;
					}
				}
				catch ( \Exception $e )
				{
					if( $e->getCode() !== 115115 )
					{
						/* We don't want to stop upgrader for this, but we can show the message if the upgrader otherwise cannot continue */
						$fileSystemItems[ $class->configurationId ] = array(
							'success'	=> TRUE,
							'message'	=> $e->getMessage()
						);
					}
				}
			}

			/* Flag a storage issue in upgrader/system check since zero storage methods are working */
			if( !$successfulStorage )
			{
				array_walk( $fileSystemItems, function( &$item ) {
					$item['success'] = FALSE;
				} );
			}

			array_splice( $return['requirements'][ $writeablesKey ], array_search( 'uploads', array_keys( $return['requirements'][ $writeablesKey ] ) ), 1, $fileSystemItems );
		}
		
		if ( Store::i() instanceof FileSystem )
		{
			$success = ( is_dir( Store::i()->_path ) and is_writeable( Store::i()->_path ) );
			$return['requirements'][ $writeablesKey ]['datastore'] = array(
				'success'	=> $success,
				'message'	=> $success ? Member::loggedIn()->language()->addToStack( 'requirements_file_writable', FALSE, array( 'sprintf' => array( Store::i()->_path ) ) ) : Member::loggedIn()->language()->addToStack('bad_datastore_configuration', FALSE, array( 'sprintf' => array( Store::i()->_path ) ) )
			);
		}
		else
		{
			unset( $return['requirements'][ $writeablesKey ]['datastore'] );
		}

		/* If InnoDB is in use check that COMPACT isn't used */
		if ( iterator_to_array( Db::i()->query( "SHOW TABLE STATUS WHERE Engine='InnoDB' AND Row_format='Compact'" ) ) )
		{
			$ifpt = '';
			foreach ( Db::i()->query("SHOW VARIABLES LIKE 'innodb_file_per_table'") as $row )
			{
				if( strtolower( $row['Value'] ) !== strtolower( 'ON' ) )
				{
					$ifpt = Member::loggedIn()->language()->get( 'requirements_mysql_ifpt_advice' );
				}
			}

			$return['advice']['MySQL']['compact'] = Member::loggedIn()->language()->addToStack( 'requirements_mysql_format_advice', FALSE, array( 'htmlsprintf' => array( $ifpt ) ) );
		}
				
		return $return;
	}

	/**
	 * Check MySQL Requirements
	 *
	 * @param	Db|NULL	$db		DB Object to use for version check
	 * @return	array
	 */
	public static function mysqlRequirements( ?Db $db=NULL ) : array
	{
		/* We don't need to check the CIC platform */
		if( CIC )
		{
			return array( 'recommendations' => array(), 'requirements' => array() );
		}

		/* MySQL Version */
		$return = array();
		$db ? $db->checkConnection() : Db::i()->checkConnection();
		$mysqlVersion = $db ? $db->server_info : Db::i()->server_info;
		$requirements = json_decode( file_get_contents( ROOT_PATH . '/applications/core/data/requirements.json' ), TRUE );
		/* MySQL timeouts */
		$db = $db ?: Db::i();
		if ( version_compare( $mysqlVersion, $requirements['mysql']['required'] ) >= 0 )
		{
			$return['requirements']['MySQL']['version'] = array(
				'success'	=> TRUE,
				'message'	=> Member::loggedIn()->language()->addToStack( 'requirements_mysql_version_success', FALSE, array( 'sprintf' => array( $mysqlVersion ) ) )
			);

			if ( version_compare( $mysqlVersion, $requirements['mysql']['recommended'] ) == -1 AND !mb_strpos( $mysqlVersion, "MariaDB" ) )
			{
				$return['advice']['MySQL']['version'] = Member::loggedIn()->language()->addToStack( 'requirements_mysql_version_advice', FALSE, array( 'sprintf' => array( $mysqlVersion, $requirements['mysql']['recommended'] ) ) );
			}
		}
		else
		{
			$return['requirements']['MySQL']['version'] = array(
				'success'	=> FALSE,
				'message'	=> Member::loggedIn()->language()->addToStack( 'requirements_mysql_version_fail', FALSE, array( 'sprintf' => array( $mysqlVersion, $requirements['mysql']['required'], $requirements['mysql']['recommended'] ) ) ),
			);
		}

		/* Utf8mb4 */
		if( strpos( $db->getCharset(), 'utf8mb4' ) === false )
		{
			$return['requirements']['MySQL']['collation'] = array(
				'success' => FALSE,
				'message' => Member::loggedIn()->language()->addToStack( 'requirements_mysql_charset_fail' )
			);
		}
	


		try
		{
			$query = $db->query( "SHOW VARIABLES LIKE '%wait_timeout%'" );

			while( $row = $query->fetch_assoc() )
			{
				if ( $row['Variable_name'] == 'wait_timeout' AND $row['Value'] < 20 )
				{
					$return['advice']['MySQL']['timeout'] = Member::loggedIn()->language()->addToStack( 'requirements_mysql_timeout', FALSE, array( 'sprintf' => array( $row['Variable_name'], $row['Value'] ) ) );
				}
			}
		}
		catch( Exception $e )
		{
			$return['advice']['MySQL'][] = $e->getMessage();
		}

		return $return;
	}
	
	/**
	 * @brief	Percentage of *this step* completed (used for the progress bar)
	 */
	protected int $stepProgress = 0;
	
	/**
	 * @brief	Custom title to use for refresh/progress bar
	 */
	protected ?string $customTitle = NULL;

	protected array $apps = [];

	/**
	 * Constructor
	 *
	 * @param	array	$apps		Application keys of apps to upgrade
	 * @return	void
	 * @throws	InvalidArgumentException
	 */
	public function __construct( array $apps )
	{
		/* Store data */
		$this->apps			= $apps;
	}
	
	/**
	 * Process
	 *
	 * @param	array|null		$data	Multiple-Redirector Data
	 * @return	array|null	Multiple-Redirector Data or NULL indicates done
	 */
	public function process( ?array $data ) : ?array
	{
		/* Start */
		if ( ! $data )
		{
			$data	= array( 0 => 1 );
		}
		
		/* Clear the last SQL error if we are continuing upgrade to prevent it erroneously showing up again later */
		if( isset( Request::i()->mr_continue ) )
		{
			unset( $_SESSION['lastSqlError'] );
		}
		
		/* Run the step */
		$step = intval( $data[0] );
		
		if ( $step === 1 )
		{
			/* Set the we're setting up flag */
			static::setUpgradingFlag( TRUE );
			
			if ( function_exists('opcache_reset') )
			{
				@opcache_reset();
			}
		}
		
		/* Write the upgrade data */
		$upgraderData = json_encode( array(
			'session' => $_SESSION,
			'step'    => $step,
			'data'    => $data
		) );
		Db::i()->replace( 'upgrade_temp', array( 'id' => 1, 'upgrade_data' => $upgraderData, 'lastaccess' => time() ) );
		
		if ( $step == 11 )
		{
			/* Clear javascript caches */
			Output::clearJsFiles();

			/* Clear widget caches */
			Widget::deleteCaches();
			
			/* Clear theme cache for default theme */
			try
			{
				$theme = Theme::load( Theme::defaultTheme() );
				$theme->buildResourceMap();
				$theme->saveSet();

				Theme::deleteCompiledCss();
			}
			catch( \Exception $e )
			{
				Log::log( $e, 'upgrade_error' );
			}

			/* Clear store */
			Cache::i()->clearAll();
			Store::i()->clearAll();
			
			/* Remove the flag */
			static::setUpgradingFlag( FALSE );
			
			return NULL;
		}
		elseif ( !method_exists( $this, "step{$step}" ) )
		{
			throw new BadMethodCallException( 'NO_STEP' );
		}
		
		$stepFunc	= array( $this, "step{$step}" );
		$response	= $stepFunc( $data );
		
		return array( $response, Member::loggedIn()->language()->addToStack( ( $this->customTitle ) ?: 'upgrade_step_' . $step ), ( ( ( 100/10 ) * $data[0] + ( ( 100/10 ) / 100 * $this->stepProgress ) ) ) ?: 1 );
	}
	
	/**
	 * Fetch the next update ID
	 *
	 * @param	string		$app		Application key
	 * @param	int			$current	Current upgrading ID
	 * @return	int|null				Upgrade or ID if none
	 */
	protected function getNextUpgradeId( string $app, int $current=0 ) : ?int
	{
		$currentVersion	= Application::load( $app )->long_version;
		$upgradeSteps	= Application::load( $app )->getUpgradeSteps( $current ?: $currentVersion );
		
		/* Grab next upgrade step to run */
		if ( ! count( $upgradeSteps ) )
		{
			return NULL;
		}
		
		$next	= array_shift( $upgradeSteps );
		$next	= intval( $next );

		if( !isset( $_SESSION['upgrade_steps'] ) )
		{
			$_SESSION['upgrade_steps']	= array( $next => array( $app => $app ) );
		}
		elseif( !isset( $_SESSION['upgrade_steps'][ $next ] ) )
		{
			$_SESSION['upgrade_steps'][ $next ]	= array( $app => $app );
		}
		else
		{
			$_SESSION['upgrade_steps'][ $next ][ $app ]	= $app;
		}

		return $next;
	}
	
	/**
	 * App Looper
	 *
	 * @param	array		$data	Multiple-Redirector Data
	 * @param	callable	$code	Code to execute for each app
	 * @return	array|string|null		Data to Multiple-Redirector Data
	 */
	protected function appLoop( array $data, callable $code ) : array|string|null
	{
		$this->stepProgress = 0;
		
		$returnNext = FALSE;
		foreach ( $this->apps as $app )
		{
			$this->stepProgress += ( 100 / count( $this->apps ) );
						
			if ( !isset( $data[1] ) )
			{
				return array( $data[0], $app );
			}
			elseif ( $data[1] == $app )
			{
				$val = $code( $app );
				
				if ( is_array( $val ) OR is_string( $val ) )
				{
					return $val;
				}
				else
				{
					$returnNext = true;
				}
			}
			elseif ( $returnNext )
			{
				return array( $data[0], $app );
			}
		}
		
		return array( ( $data[0] + 1 ) );
	}
	
	/**
	 * Step 1
	 * Upgrade database
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array|string	Multiple-Redirector Data
	 */
	protected function step1( array $data ) : array|string
	{
		/* Clear cache before we start */
		Cache::i()->clearAll();
		Store::i()->clearAll();

		if ( CIC )
		{
			/* If this is CiC, make sure the core_store and core_cache tables are empty in case the primary caching engines fail, so we're not serving old data when it falls back to database storage */
			Db::i()->delete( 'core_store' );
			Db::i()->delete( 'core_cache' );
		}

		$this->stepProgress	= 0;
		$perAppProgress		= floor( 100 / count( $this->apps ) );
		$returnNext	= FALSE;
		$extra		= array();
		
		$lastAppToRun = end( $this->apps );
		reset( $this->apps );
		
		foreach ( $this->apps as $app )
		{
			$this->stepProgress += $perAppProgress;
						
			if ( !isset( $data[1] ) )
			{
				$this->customTitle = "Preparing to upgrade database";
				
				return array( $data[0], $app );
			}
			/* If we finished with the last app, $returnNext would get set to true and we'd want to run this again */
			elseif ( $data[1] == $app OR $returnNext )
			{
				if ( isset( $_SESSION['updatedData'] ) )
				{
					unset( $_SESSION['updatedData'] );
				}
				
				if ( $returnNext )
				{
					/* Start next app */
					$data['extra']	= array();
					$data[1]        = $app;
					
					$_SESSION['updatedData'] = $data;
					$_SESSION['lastJsonIndex'] = 0;
				}

				Log::debug( "Step1 Loop: " . json_encode( $data ), 'upgrade' );
				
				/* Re-initialize $extra variable */
				$extra	= array();
				
				$extra['lastSqlId'] = $data['extra']['lastSqlId'] ?? 0;
				
				/* Currently running version */
				if ( isset( $data['extra'] ) and array_key_exists( '_current', $data['extra'] ) ) # Can be null which isset() ignores
				{
					$extra['_current'] = $data['extra']['_current'];
				}
				else
				{
					$extra['_current']	= $this->getNextUpgradeId( $app );
					$extra['lastSqlId']	= 0;
				}

				/* Did we find any? */
				if( $extra['_current'] )
				{
					/* We need to populate \IPS\Request with the extra data returned from the last upgrader step call */
					if( isset( $data['extra']['_upgradeData'] ) )
					{
						Request::i()->extra = $data['extra']['_upgradeData'];
					}
					
					/* What step in the upgrader file are we on? */
					$upgradeStep = ( isset($data['extra']['_upgradeStep']) ) ? intval($data['extra']['_upgradeStep']) : 1;

					/* We're on the first step of the current version's upgrade.php, so run the raw queries yet, do so */
					if( $upgradeStep == 1 AND ! isset( $data['extra']['_upgradeData'] ) and ( ! ( isset( $_SESSION['sqlFinished'][ $app ][ $extra['_current'] ] ) AND $_SESSION['sqlFinished'][ $app ][ $extra['_current'] ] ) ) )
					{
						$lastSqlId = ( $extra['lastSqlId'] ?? 0 );
						
						$this->customTitle = "Upgrading database (" . ucfirst( $app ) . ': Upgrade ID ' . $extra['_current'] . '-' . $lastSqlId . ')';
						
						Log::debug( "Upgrading database for app: " . $app, 'upgrade' );
						
						$_SESSION['lastSqlError'] = null;
						
						try
						{
							if( file_exists( Application::load( $app )->getApplicationPath() . "/setup/upg_{$extra['_current']}/lang.json" ) )
							{
								$langChanges = json_decode( file_get_contents( Application::load( $app )->getApplicationPath() . "/setup/upg_{$extra['_current']}/lang.json" ), TRUE );
								if ( isset( $langChanges['normal']['removed'] ) and $langChanges['normal']['removed'] )
								{
									Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=?', $app ), array( 'word_js=0' ), array( Db::i()->in( 'word_key', $langChanges['normal']['removed'] ) ) ) );
								}
								if ( isset( $langChanges['js']['removed'] ) and $langChanges['js']['removed'] )
								{
									Db::i()->delete( 'core_sys_lang_words', array( array( 'word_app=?', $app ), array( 'word_js=1' ), array( Db::i()->in( 'word_key', $langChanges['js']['removed'] ) ) ) );
								}
							}
							
							$fetched = Application::load( $app )->installDatabaseUpdates( $extra['_current'], ( Request::i()->run_anyway ) ? $lastSqlId - 0.1 : $lastSqlId, 10, !Request::i()->run_anyway );

							if( isset( Request::i()->mr_continue ) AND Request::i()->mr_continue )
							{
								$fetched = array( 'count' => $fetched['count'] );
							}

							$extra['lastSqlId'] = $_SESSION['lastJsonIndex'];
							
							Log::debug( (int) $fetched['count'] . ' queries run, last ID ' . $_SESSION['lastJsonIndex'], 'upgrade' );
						}
						catch( Exception $ex )
						{
							$trace    = $ex->getTrace();
							$queryRun = '';
							$message  = $ex->getMessage();
							
							if ( isset( $trace[0]['args'][0] ) )
							{
								$queryRun = $trace[0]['args'][0];
							}
							
							$_SESSION['lastSqlError'] = $message . ' ' . $queryRun;
							
							Log::log( "Error: " . $message . ' ' . $queryRun . "\n" . $ex->getTraceAsString(), 'upgrade_error' );
							
							/* Throw so ajax returns 500 and stops upgrader */
							throw $ex;
						}
						
						/* Queries to run manually */
						if( is_array( $fetched['queriesToRun'] ) and count( $fetched['queriesToRun'] ) )
						{
							Upgrade::adjustMultipleRedirect( array( 1 => $app, 'extra' => array( 'lastSqlId' => $_SESSION['lastJsonIndex'], '_current' => $extra['_current'] ) ) );

							return Theme::i()->getTemplate( 'forms' )->queries( $fetched['queriesToRun'], Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => Request::i()->mr ) ) );
						}
						else
						{
							/* Got more? */
							if ( $fetched['count'] > 0 AND $_SESSION['lastJsonIndex'] )
							{
								return array( $data[0], $app, 'extra' => $extra );
							}
							
							$extra['lastSqlId']		= 0;
							$_SESSION['sqlFinished'][ $app ][ $extra['_current'] ] = 1;
							$_SESSION['lastJsonIndex'] = 0;
							$_SESSION['lastSqlError']  = null;
							
							/* All done, allow code to finish this foreach and process below */
						}
					}
					
					/* The "run anyway" button uses the same URL as the "I have run, continue" button which increments the upgrade step count, but this means that the run anyway step never actually runs */
					if ( isset( Request::i()->run_anyway ) and $upgradeStep > 1 )
					{
						$upgradeStep--;
					}
						
					/* Get the object */
					$_className		= "\\IPS\\{$app}\\setup\\upg_{$extra['_current']}\\Upgrade";
					$_methodName	= "step{$upgradeStep}";

					if( class_exists( $_className ) )
					{ 
						$upgrader = new $_className;
						
						/* If the next step exists, run it */
						if( method_exists( $upgrader, $_methodName ) )
						{
							$this->customTitle = "Running upgrade step " . $upgradeStep . " (" . ucfirst( $app ) . ': Upgrade ID ' . $extra['_current'] . ')';
							
							try
							{
								/* Get custom title first as the step may unset session variables that are being referenced */
								$customTitleMethod = 'step' . $upgradeStep . 'CustomTitle';
								
								if ( method_exists( $upgrader, $customTitleMethod ) )
								{
									$this->customTitle = $upgrader->$customTitleMethod();
								}

								$result = $upgrader->$_methodName();
							}
							catch( Exception $ex )
							{
								$trace    = $ex->getTrace();
								$queryRun = '';
								$message  = $ex->getMessage();
								
								if ( isset( $trace[0]['args'][0] ) )
								{
									$queryRun = $trace[0]['args'][0];
								}
								
								$_SESSION['lastSqlError'] = $message . ' ' . $queryRun;
								$_SESSION['updatedData']  = array_merge( $data, array( 'extra' => $extra ), array( 'extra' => array( '_current' => $extra['_current'], '_upgradeStep' => $upgradeStep ) ) );
								
								Log::log( "(Upgrader " . $_methodName . ") " . $message . ' ' . $queryRun . $ex->getTraceAsString(), 'upgrade_error' );
								
								/* Throw so ajax returns 500 and stops upgrader */
								throw $ex;
							}
							catch( UnderflowException $ex )
							{
								$trace    = $ex->getTrace();
								$message  = $ex->getMessage();
								
								$_SESSION['lastSqlError'] = $message;
								$_SESSION['updatedData']  = array_merge( $data, array( 'extra' => $extra ), array( 'extra' => array( '_current' => $extra['_current'], '_upgradeStep' => $upgradeStep ) ) );
								
								Log::log( "(Upgrader " . $_methodName . ") " . $message, 'upgrade_error' );
								
								throw $ex;
							}
							
							/* If the result is 'true' we move on to the next step */
							if( $result === TRUE )
							{
								/* Reset this for future version IDs */
								$extra['lastSqlId']	= 0;
								$_SESSION['lastJsonIndex'] = 0;
								$_SESSION['lastSqlError']  = null;
								$_SESSION['updatedData']   = null;
								
								$_nextMethodStep = "step" . ( $upgradeStep + 1 );

								if( method_exists( $upgrader, $_nextMethodStep ) )
								{
									/* We have another step to run - set the data and move along */
									$extra['_upgradeStep']	= $upgradeStep + 1;
									
									return array( $data[0], $app, 'extra' => $extra );
								}
								else
								{
									/* Done with this current step, see if there are any more */
									$extra['_current'] = $this->getNextUpgradeId( $app, $extra['_current'] );
									
									if( $extra['_current'] )
									{
										unset( $extra['_upgradeStep'], $extra['_upgradeData'] );

										return array( $data[0], $app, 'extra' => $extra );
									}
								}
							}
							/* If the result is an array with 'html' key, we show that */
							else if( is_array( $result ) AND isset( $result['html'] ) )
							{
								return $result['html'];
							}
							/* Otherwise we need to run the same step again and store the data returned */
							else
							{
								/* Store the data returned, set the step to the same/current one, and re-run */
								$extra['_upgradeData']	= $result;
								$extra['_upgradeStep']	= $upgradeStep;
								
								return array( $data[0], $app, 'extra' => $extra );
							}
						}
						else
						{
							/* Step doesn't exist so move on to next version */
							$extra['_current']	= $this->getNextUpgradeId( $app, $extra['_current'] );
							$extra['lastSqlId']	= 0;

							unset( $extra['_upgradeStep'], $extra['_upgradeData'] );

							return array( $data[0], $app, 'extra' => $extra );
						}
					} # If has upg_xxxxx/Upgrade.php
					else
					{
						/* SQL done, no upgrade steps, lets jog on */
						$extra['_current']	= $this->getNextUpgradeId( $app, $extra['_current'] );
						$extra['lastSqlId']	= 0;

						unset( $extra['_upgradeStep'], $extra['_upgradeData'] );
						
						return array( $data[0], $app, 'extra' => $extra );
					}
					
					$returnNext = TRUE;
					
				} # If current step
				else
				{
					$_SESSION['lastJsonIndex'] = 0;
					$_SESSION['lastSqlError']  = null;
					$_SESSION['updatedData']   = null;
					
					/* Get the next app to upgrade */
					$returnNext = TRUE;
				}
				
			} # If current app
		} # Foreach

		/* We're done, look for finish methods for this upgrade ID */
		if ( $lastAppToRun === $app AND isset( $_SESSION['upgrade_steps'] ) AND count( $_SESSION['upgrade_steps'] ) )
		{
			$this->customTitle = "Running finish step (" . ucfirst( $app ) . ': Upgrade ID ' . $extra['_current'] . ')';
			
			foreach( $_SESSION['upgrade_steps'] as $_versionId => $_versionApps )
			{
				foreach( $_versionApps as $_versionApp )
				{
					Log::debug( "Running finish step for: {$_versionApp} version {$_versionId}", 'upgrade' );

					/* Get the object */
					$_className = "\\IPS\\{$_versionApp}\\setup\\upg_{$_versionId}\\Upgrade";

					if( class_exists( $_className ) )
					{
						$finisher = new $_className;
						
						if ( method_exists( $finisher, 'finish' ) )
						{
							try
							{
								$finisher->finish();
							}
							catch( \Exception $ex )
							{
								$_SESSION['lastSqlError'] = $ex->getMessage() . ' (' . $_className . '->finish() )';
								
								throw $ex;
							}
						} 
					}
				}
			}
		}
		
		/* Move on to next step */
		return array( ( $data[0] + 1 ), 'extra' => $extra );
	}

	/**
	 * Step 2
	 * Run a database check
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array|string	Multiple-Redirector Data
	 */
	protected function step2( array $data ) : array|string
	{
		return $this->appLoop( $data, function( $app ) use( $data )
		{
			$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_2_app'), $app );
			$changesToMake = Application::load( $app )->databaseCheck();
			if ( count( $changesToMake ) )
			{
				$toRun = Upgrade::runManualQueries( $changesToMake );
				if ( count( $toRun ) )
				{
					Upgrade::adjustMultipleRedirect( array( 1 => $app, 'extra' => array( '_upgradeStep' => 2 ) ) );
					return Theme::i()->getTemplate( 'forms' )->queries( $toRun, Url::internal( 'controller=upgrade' )->setQueryString( array( 'key' => $_SESSION['uniqueKey'], 'mr_continue' => 1, 'mr' => Request::i()->mr ) ) );
				}
			}
			return NULL;
		} );
	}

	/**
	 * Step 3
	 * Insert app data
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step3( array $data ) : array
	{
		return $this->appLoop( $data, function( $app )
		{
			try
			{
				$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_3_app'), $app );
				$application = Application::load( $app );
				$application->installJsonData( TRUE );

				/* Upgrade history */
				$versions		= $application->getAllVersions();
				$longVersions	= array_keys( $versions );
				$humanVersions	= array_values( $versions );

				if( count($versions) )
				{
					$latestLVersion	= array_pop( $longVersions );
					$latestHVersion	= array_pop( $humanVersions );

					Db::i()->insert( 'core_upgrade_history', array( 'upgrade_version_human' => $latestHVersion, 'upgrade_version_id' => $latestLVersion, 'upgrade_date' => time(), 'upgrade_mid' => (int) Member::loggedIn()->member_id, 'upgrade_app' => $app ) );
				}
			}
			catch( \Exception $e )
			{
				Log::log( $e, 'upgrade_error' );

				throw $e;
			}
			
			/* Update application data */
			if( file_exists( Application::load( $app )->getApplicationPath() . '/data/application.json' ) )
			{
				Log::debug( "Installing application data for " . $app, 'upgrade' );
				
				$application	= json_decode( file_get_contents( Application::load( $app )->getApplicationPath() . '/data/application.json' ), TRUE );

				//\IPS\Lang::saveCustom( $app, "__app_{$app}", $application['application_title'] );

				unset( $application['app_directory'], $application['app_protected'], $application['application_title'] );

				$app	= Application::load( $app );

				foreach( $application as $column => $value )
				{
					$column = str_replace( 'app_', '', $column );
					$app->$column	= $value;
				}

				$app->save();
				$app::postToggleEnable( TRUE );
			}
			else
			{
				Log::log( "Error: Missing app data", 'upgrade_error' );
				throw new LogicException( Member::loggedIn()->language()->addToStack( 'err_missing_app_data', FALSE, array( 'sprintf' => array( $app ) ) ) );
			}
		} );
	}

	/**
	 * Step 4
	 * Update settings, tasks, etc.
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step4( array $data ) : array
	{
		return $this->appLoop( $data, function( $app )
		{
			Log::debug( "Installing settings, tasks and keywords for " . $app, 'upgrade' );
			$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_4_app'), $app );
			Application::load( $app )->installSettings();
			Application::load( $app )->installTasks();
			Application::load( $app )->installSearchKeywords();
		} );
	}

	/**
	 * Step 5
	 * Update Languages
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step5( array $data ) : array
	{
		return $this->appLoop( $data, function( $app ) use ($data)
		{
			if ( !isset( $data[2] ) )
			{
				$data[2] = 0;
			}
			
			try
			{
				$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_5_app'), $app, $data[2] );
				$inserted = Application::load( $app )->installLanguages( $data[2], 250 );
				
				Log::debug( "Inserted language keys for " . $app . ", offset of " . $data[2], 'upgrade' );
			}
			catch( \Exception $ex )
			{
				Log::log( "Step 5, " . $app . ' ' . $ex->getMessage(), 'upgrade_error' );
				
				throw $ex;
			}

			if ( $inserted )
			{
				$data[2] += $inserted;
				return $data;
			}
			else
			{
				return null;
			}
		} );
	}
	
	/**
	 * Step 6
	 * Update Email Templates
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step6( array $data ) : array
	{
		return $this->appLoop( $data, function( $app )
		{
			$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_6_app'), $app );
			Application::load( $app )->installEmailTemplates();
		} );
	}

	/**
	 * Step 7
	 * Update theme settings
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step7( array $data ) : array
	{
		return $this->appLoop( $data, function( $app )
		{
			try
			{
				$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_7_app'), $app );
				Application::load( $app )->installThemeEditorSettings();
			}
			catch( \Exception $e )
			{
				Log::log( $e, 'upgrade_error' );

				throw $e;
			}
		
			Log::debug( "Installed editor theme settings for " . $app, 'upgrade' );

			return null;
		} );
	}

	/**
	 * Step 8
	 * Clear existing templates
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step8( array $data ) : array
	{
		/* Clear old caches */
		Cache::i()->clearAll();
		Store::i()->clearAll();
		
		if ( CIC )
		{
			/* If this is CiC, make sure the core_store and core_cache tables are empty in case the primary caching engines fail, so we're not serving old data when it falls back to database storage */
			Db::i()->delete( 'core_store' );
			Db::i()->delete( 'core_cache' );
		}
		 
		Theme::clearFiles( Theme::IMAGES );
		
		return $this->appLoop( $data, function( $app ) use( $data )
		{
			try
			{
				/* Deletes old data from the database */
				$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_8_app'), $app );
				Application::load( $app )->clearTemplates();
			}
			catch( \Exception $e )
			{
				Log::log( $e, 'upgrade_error' );
			}
		} );
	}

	/**
	 * Step 9
	 * Update templates
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step9( array $data ) : array
	{
		Settings::i()->clearCache();

		if( isset( Store::i()->storageConfigurations ) )
		{
			unset( Store::i()->storageConfigurations );
		}
		
		return $this->appLoop( $data, function( $app ) use( $data )
		{
			if ( !isset( $data[2] ) )
			{
				$data[2] = 0;
			}

			try
			{
				$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_9_app'), $app, $data[2] );
				$inserted = Application::load( $app )->installTemplates( TRUE, $data[2], 75 );
			}
			catch( \Exception $e )
			{
				Log::log( $e, 'upgrade_error' );

				throw $e;
			}
			
			Log::debug( "Installed templates for " . $app . ", offset of " . $data[2], 'upgrade' );
			
			if ( $inserted )
			{
				$data[2] += $inserted;
				return $data;
			}
			else
			{
				Application::load( $app )->installCustomTemplates();
				return null;
			}
		} );
	}

	/**
	 * Step 10
	 * Update Javascript
	 *
	 * @param	array	$data	Multi-redirector data
	 * @return	array	Multiple-Redirector Data
	 */
	protected function step10( array $data ) : array
	{
		return $this->appLoop( $data, function( $app ) use( $data )
		{
			if ( !isset( $data[2] ) )
			{
				$data[2] = 0;
			}
			
			$this->customTitle = sprintf( Member::loggedIn()->language()->get('upgrade_step_10_app'), $app, $data[2] );
			$inserted = Application::load( $app )->installJavascript( $data[2], 100 );
			
			Log::debug( "Installed javascript for " . $app, 'upgrade' );

			if ( $inserted )
			{
				$data[2] += $inserted;
				return $data;
			}
			else
			{
				return null;
			}
		} );
	}
	
	/**
	 * Class static methods
	 *
	 */
	 
	 /**
	 * Set the upgrading flag to prevent compilation of themes/js elsewhere until we're ready
	 *
	 * @param	boolean		$value		Value of flag to save
	 * @return	void
	 */
	public static function setUpgradingFlag( bool $value=TRUE ) : void
	{
		try
		{
			Db::i()->select( 'conf_value', 'core_sys_conf_settings', array( 'conf_key=?', 'setup_in_progress' ) )->first();
		}
		catch( UnderflowException $ex )
		{
			if ( Application::load('core')->long_version >= 40000 )
			{
				$insert = array(
					'conf_key'      => 'setup_in_progress',
					'conf_value'    => 0,
					'conf_default'  => 0, 
					'conf_keywords' => '',
					'conf_app'      => 'core'
				);
			}
			else
			{
				$insert = array(
					'conf_key'      => 'setup_in_progress',
					'conf_value'    => 0,
					'conf_default'  => 0, 
					'conf_keywords' => '',
					'conf_type'     => 'yes_no'
				);
			}
			
			/* This key was added in 4.0.8 so it may not exist */
			Db::i()->insert( 'core_sys_conf_settings', $insert );
		}
		
		Settings::i()->changeValues( array( 'setup_in_progress' => intval( $value ) ) );
	}
	
	 /**
	  * Runs a bunch of legacy SQL queries
	  *
	  * @param	string	$app		App key to run
	  * @param	int		$upgradeId	Current upgrade ID
	  * @param	string	$file		File holding $SQL array
	  * @return bool
	  * @note	We ignore some database errors that shouldn't prevent us from continuing.
	  * @li	1050: Can't rename a table as it already exists
	  * @li	1051: Can't drop a table because it doesn't exist
	  * @li	1060: Can't add a column as it already exists
	  * @li	1062: Can't add an index as index already exists
	  * @li	1062: Can't add a row as PKEY already exists
	  * @li	1091: Can't drop key or column because it does not exist
	  */
	 public static function runLegacySql( string $app, int $upgradeId, string $file='queries.php' ) : bool
	 {
	 	$queryFile = Application::load( $app )->getApplicationPath() . '/setup/upg_' . $upgradeId . '/' . $file;
		$lastIndex = ( isset( $_SESSION['lastJsonIndex'] ) ) ? $_SESSION['lastJsonIndex'] : 0;
		
		if ( file_exists( $queryFile ) )
		{
			require( $queryFile );
			
			if ( isset( $SQL ) and is_array( $SQL ) )
			{
				foreach( $SQL as $k => $query )
				{
					if ( $lastIndex AND $lastIndex >= $k )
					{
						continue;
					}

					$_SESSION['lastJsonIndex'] = $k;
					
					try
					{
						Db::i()->query( static::addPrefixToQuery( $query ) );
					}
					catch( Exception $e )
					{
						if ( ! in_array( $e->getCode(), array( 1007, 1008, 1050, 1060, 1061, 1062, 1091, 1051 ) ) )
						{
							throw $e;
						}
					}
				}
			}
		}

		return true;
	 }
	 
	/**
	 * Add SQL Prefix to Query
	 *
	 * @param	string		$query	SQL Query
	 * @return  string
	 */
	public static function addPrefixToQuery( string $query ) : string
	{
		if ( Db::i()->prefix )
		{
			$query = preg_replace( "#^CREATE TABLE(?:\s+?)?(\S+)#i"        , "CREATE TABLE "  . Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^RENAME TABLE(?:\s+?)?(\S+)\s+?TO\s+?(\S+?)(\s|$)#i"     , "RENAME TABLE "  . Db::i()->prefix . "\\1 TO " . Db::i()->prefix ."\\2", $query );
			$query = preg_replace( "#^DROP TABLE( IF EXISTS)?(?:\s+?)?(\S+)(\s+?)?#i"    , "DROP TABLE \\1 "    . Db::i()->prefix . "\\2 ", $query );
			$query = preg_replace( "#^TRUNCATE TABLE(?:\s+?)?(\S+)(\s+?)?#i", "TRUNCATE TABLE ". Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^DELETE FROM(?:\s+?)?(\S+)(\s+?)?#i"   , "DELETE FROM "   . Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^INSERT INTO(?:\s+?)?(\S+)\s+?#i"      , "INSERT INTO "   . Db::i()->prefix . "\\1 ", $query );
			//$query = preg_replace( "#^INSERT IGNORE INTO(?:\s+?)?(\S+)\s+?#i", "INSERT IGNORE INTO "   . \IPS\Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^UPDATE(?:\s+?)?(\S+)\s+?#i"           , "UPDATE "        . Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^REPLACE INTO(?:\s+?)?(\S+)\s+?#i"     , "REPLACE INTO "  . Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^ALTER TABLE(?:\s+?)?(\S+)\s+?#i"      , "ALTER TABLE "   . Db::i()->prefix . "\\1 ", $query );
			$query = preg_replace( "#^ALTER IGNORE TABLE(?:\s+?)?(\S+)\s+?#i"      , "ALTER IGNORE TABLE "   . Db::i()->prefix . "\\1 ", $query );
			
			$query = preg_replace( "#^CREATE INDEX (\S+) ON (\S+) #", "CREATE INDEX \\1 ON " . Db::i()->prefix . "\\2 " , $query );
			$query = preg_replace( "#^CREATE UNIQUE INDEX (\S+) ON (\S+) #", "CREATE UNIQUE INDEX \\1 ON " . Db::i()->prefix . "\\2 " , $query );
		}

		return $query;
	}
	
	/**
	 * Run manual queries, that may be rather large
	 *
	 * @note	We ignore some database errors that shouldn't prevent us from continuing.
	 * @li	1007: Can't create database because it already exists
	 * @li	1008: Can't drop database because it does not exist
	 * @li	1050: Can't rename a table as it already exists
	 * @li	1060: Can't add a column as it already exists
	 * @li	1062: Can't add an index as index already exists
	 * @li	1062: Can't add a row as PKEY already exists
	 * @li	1069: MyISAM has maxed out number of allowed indexes per table
	 * @li	1091: Can't drop key or column because it does not exist
	 * @param	array	$queries		Array of queries in the following format ( table => x, query = x, db => null ); Supply an \IPS\Db object as db if necessary (i.e. remote archiving)
	 * @return  array
	 */
	public static function runManualQueries( array $queries ) : array
	{
		if ( isset( Request::i()->mr_continue ) AND Request::i()->mr_continue )
		{
			return array();
		}
		
		$toReturn    = array();

		foreach( $queries as $id => $data )
		{
			$database = ( isset( $data['db'] ) ) ? $data['db'] : Db::i();

			if( !Request::i()->run_anyway and $database->recommendManualQuery( $data['table'] ) )
	        {
	        	Log::debug( "Big table " . $data['table'] . ", storing query to run manually", 'upgrade' );
	            
	            $toReturn[] = $data['query'];
			}
			else
			{
				try
				{
					$database->query( $data['query'] );
				}
				catch( Exception $e )
				{
					/* If the error isn't important we should ignore it and is consistent with \Application::installDatabaseUpdates() */
					if( !in_array( $e->getCode(), array( 1007, 1008, 1050, 1060, 1061, 1062, 1069, 1091 ) ) )
					{
						throw $e;
					}
				}

			}
		}
		
		return $toReturn;
	}

	/**
	 * Determine what our cutoff should be for long running queries
	 *
	 * @return  null|int
	 */
	public static function determineCutoff() : ?int
	{
		$cutOff	= 30;

		if( $maxExecution = @ini_get( 'max_execution_time' ) AND $maxExecution > 0 AND $maxExecution < $cutOff )
		{
			$cutOff	= $maxExecution;
		}

		return time() + ( $cutOff * .6 );
	}
	
	/**
	 * Determine what our cutoff should be for long running queries
	 *
	 * @param	array 	$changes	The changes to make to the mr data
	 * @return  string
	 */
	public static function adjustMultipleRedirect( array $changes ) : string
	{
		$key	= 'mr-' . md5( (string) Url::internal( 'controller=upgrade' )->setQueryString( 'key', $_SESSION['uniqueKey'] ) );
		$mr		= $_SESSION[$key] ?? NULL;
		$mr		= $mr ? json_decode( $mr, TRUE ) : NULL;
		
		foreach( $changes as $k => $v )
		{
			if( is_array( $v ) )
			{
				foreach( $v as $_k => $_v )
				{
					$mr[ $k ][ $_k ]	= $_v;
				}
			}
			else
			{
				$mr[ $k ]	= $v;
			}
		}
		
		$_SESSION[ $key ]	= json_encode( $mr );

		return $_SESSION[ $key ];
	}
}