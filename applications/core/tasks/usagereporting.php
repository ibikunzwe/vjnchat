<?php
/**
 * @brief		usagereporting Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		27 Dec 2017
 */

namespace IPS\core\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Application;
use IPS\convert\App;
use IPS\Db;
use IPS\File;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Lang;
use IPS\Login;
use IPS\nexus\Gateway;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use function defined;
use function get_class;
use function in_array;
use function strpos;
use function strtolower;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * usagereporting Task
 */
class usagereporting extends Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws    Task\Exception
	 */
	public function execute() : mixed
	{
		/* Check usage reporting is enabled */
		if ( !Settings::i()->usage_reporting )
		{
			Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'usagereporting' ) );
			return NULL;
		}
		
		/* Check this isn't a test install */
		$licenseData = IPS::licenseKey();
		if ( \IPS\IN_DEV or !$licenseData or mb_substr( $licenseData['key'], -12 ) === '-TESTINSTALL' )
		{
			return NULL;
		}
		
		/* Send the report */
		try
		{
			$response = Url::external('https://invisionpowerdiagnostics.com/usage/')->request(2)->post( $this->buildReport() ); // Timeout is deliberately very low because we don't care if it times out (but not 0 because we still need to let the DNS resolve)
			
			/* If the server has asked us to stop sending reports, then turn it off */
			if ( $response->httpResponseCode == 410 )
			{
				Settings::i()->changeValues( array( 'usage_reporting' => 0 ) );
				Db::i()->update( 'core_tasks', array( 'enabled' => 0 ), array( '`key`=?', 'usagereporting' ) );
			}
		}
		catch ( \IPS\Http\Request\Exception $e )
		{
			// It will probably time out, but that's okay
		}
		
		return NULL;
	}
	
	/**
	 * Create report
	 *
	 * @return	array
	 */
	final protected function buildReport() : array
	{
		/* Identifier which allows us to keep separate reports from this community
			from other reports (so we can see how communities change things as they grow) but
			does NOT allow us to identify which community is sending the report */
		$report = array( 'anonymized_id' => md5( Settings::i()->base_url . Settings::i()->site_secret_key ) );

		/* Community data */
		$licenseData = IPS::licenseKey(); // this is just to know if it is *active* and if it is CiC - we don't actually send the license key
		$report['community'] = array(
			'version'		=> Application::load('core')->version,
			'installed'		=> date( 'Y-m-d', Settings::i()->board_start ),
			'active_license'=> $licenseData && $licenseData['active'],
			'cloud'			=> $licenseData && $licenseData['cloud'],
		);
		
		/* Server Data */
		$report['server'] = array(
			'php_version'		=> PHP_VERSION_ID,
			'php_extensions'	=> get_loaded_extensions(),
			'mysql_version'		=> Db::i()->server_version,
			'os'				=> PHP_OS,
		);
		
		/* Apps */
		$report['apps'] = array();
		foreach ( Application::applications() as $app )
		{
			if ( $app->enabled )
			{
				$report['apps'][ $app->directory ] = $app->long_version;
			}
		}
		
		/* Themes */
		$report['themes'] = array();
		foreach ( Theme::themes() as $theme )
		{
			$settings = array();
			foreach ( $theme->settings as $k => $v )
			{
				if ( in_array( $k, array( 'responsive', 'rounded_photos', 'social_links', 'sidebar_position', 'sidebar_responsive', 'enable_fluid_width', 'fluid_width_size', 'js_incclude', 'ajax_pagination', 'cm_store_view', 'body_font', 'headline_font' ) ) )
				{
					$settings[ $k ] = $v;
				}
			}
			
			$report['themes'][] = array(
				'customised'	=> Db::i()->select( 'COUNT(*)', 'core_theme_templates', array( 'template_set_id=?', $theme->id ) )->first(),
				'settings'		=> $settings,
			);
		}
		
		/* Languages */
		$report['languages'] = array();
		foreach ( Lang::languages() as $lang )
		{
			$localeDot = strpos( $lang->short, '.' );
			$langKey = $localeDot ? substr( $lang->short, 0, $localeDot ) : $lang->short;
			
			if ( !isset( $report['languages'][ $langKey ] ) )
			{
				$report['languages'][ $langKey ] = 0;
			}
			$report['languages'][ $langKey ]++;
		}
		
		/* Settings */
		foreach ( Db::i()->select( array( 'conf_key', 'conf_value', 'conf_default', 'conf_app', 'conf_report' ), 'core_sys_conf_settings', 'conf_report IS NOT NULL' ) as $row )
		{
			if ( in_array( $row['conf_app'], IPS::$ipsApps ) and Application::appIsEnabled( $row['conf_app'] ) )
			{
				if ( $row['conf_report'] == 'full' )
				{
					$report['settings'][ $row['conf_key'] ] = $row['conf_value'];
				}
				else
				{
					$report['settings'][ $row['conf_key'] ] = ( $row['conf_value'] != $row['conf_default'] );
				}
			}
		}
		
		/* Database counts */
		foreach ( IPS::$ipsApps as $app )
		{
			if ( Application::appIsEnabled( $app ) and file_exists( \IPS\ROOT_PATH . "/applications/{$app}/data/schema.json" ) )
			{
				foreach ( json_decode( file_get_contents( \IPS\ROOT_PATH . "/applications/{$app}/data/schema.json" ), TRUE ) as $table => $data )
				{
					if ( isset( $data['reporting'] ) and $data['reporting'] === 'count' )
					{
						$report['tables'][ $table ] = Db::i()->select( 'COUNT(*)', $table )->first();
					}
				}
			}
		}
				
		/* File storage configurations */
		$report['files'] = array( 'amazon' => 0, 'database' => 0, 'filesystem' => 0, 'ftp' => 0, 'other' => 0 );
		foreach ( Application::allExtensions( 'core', 'FileStorage', FALSE ) as $k => $v )
		{
			try
			{
				$class = strtolower( substr( get_class( File::getClass( $k ) ), 9 ) );
				
				if ( isset( $report['files'][ $class ] ) )
				{
					$report['files'][ $class ]++;
				}
				else
				{
					$report['files']['other']++;
				}
			}
			catch ( Exception $e ) { }
		}
		
		/* Login methods */
		$loginMethods = array(
			'IPS\Login\Handler\Standard' => 'standard',
			'IPS\Login\Handler\OAuth2\Facebook' => 'facebook',
			'IPS\Login\Handler\OAuth2\Google' => 'google',
			'IPS\Login\Handler\OAuth2\LinkedIn' => 'linkedin',
			'IPS\Login\Handler\OAuth2\Microsoft' => 'microsoft',
			'IPS\Login\Handler\OAuth1\Twitter' => 'twitter',
			'IPS\Login\Handler\OAuth2\Invision' => 'invision',
			'IPS\Login\Handler\OAuth2\Wordpress' => 'wordpress',
			'IPS\Login\Handler\OAuth2\Custom' => 'oauth',
			'IPS\Login\Handler\ExternalDatabase' => 'external',
			'IPS\Login\Handler\LDAP' => 'ldap',
		);
		foreach ( $loginMethods as $k => $v )
		{
			$report['login'][ $v ] = 0;
		}
		$report['login']['other'] = 0;
		foreach ( Login::methods() as $method )
		{			
			$class = get_class( $method );
			if ( array_key_exists( $class, $loginMethods ) )
			{
				$report['login'][ $loginMethods[ $class ] ]++;
			}
			else
			{
				$report['login']['other']++;
			}
		}		
		
		/* Payment methods */
		$report['paymethods'] = array();
		if ( Application::appIsEnabled('nexus') )
		{
			$report['paymethods'] = array( 'manual' => 0, 'paypal_standard' => 0, 'paypal_pro' => 0, 'stripe_card' => 0, 'stripe_native' => 0, 'stripe_alipay' => 0, 'stripe_bancontact' => 0, 'stripe_giropay' => 0, 'stripe_ideal' => 0, 'stripe_sofort' => 0, 'test' => 0, 'other' => 0 );
			foreach ( Gateway::roots() as $gateway )
			{
				$class = strtolower( substr( get_class( $gateway ), 18 ) );
				$settings = json_decode( $gateway->settings, TRUE );
				
				if ( $class === 'paypal' )
				{
					$class = ( $settings['type'] === 'paypal' ) ? 'paypal_standard' : 'paypal_pro';
				}
				elseif ( $class === 'stripe' )
				{
					$class = 'stripe_' . $settings['type'];
				}
				
				
				if ( array_key_exists( $class, $report['paymethods'] ) )
				{
					$report['paymethods'][ $class ]++;
				}
				else
				{
					$report['paymethods']['other']++;
				}
			}
		}

		/* Converters */
		$report['converters'] = array();
		if ( Application::appIsEnabled('convert') )
		{
			foreach( App::apps() as $app )
			{
				/* Some legacy converters may not have the correct sw key */
				if( !in_array( $app->sw, IPS::$ipsApps ) OR !$app->finished )
				{
					continue;
				}

				$report['converters'][ $app->sw . '_' . $app->app_key ] = $app->start_date;
			}
		}

		/* Return */
		return $report;
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}