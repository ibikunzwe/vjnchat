<?php
/**
 * @brief		Magic Template Class for IN_DEV mode
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		18 Feb 2013
 */

namespace IPS\Theme\Dev;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadMethodCallException;
use IPS\Request;
use IPS\Theme;
use IPS\Theme\Template as ThemeTemplate;
use function defined;
use function function_exists;
use function get_called_class;
use function in_array;
use const IPS\DEBUG_TEMPLATES;
use const IPS\IN_DEV;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Magic Template Class for IN_DEV mode
 */
class Template extends ThemeTemplate
{
	/**
	 * @brief	Source Folder
	 */
	public ?string $sourceFolder = NULL;
	
	/**
	 * Contructor
	 *
	 * @param string $app				Application Key
	 * @param string $templateLocation	Template location (admin/public/etc.)
	 * @param string $templateName		Template Name
	 * @return	void
	 */
	public function __construct( string $app, string $templateLocation, string $templateName )
	{
		parent::__construct( $app, $templateLocation, $templateName );
		$this->app = $app;
		$this->templateLocation = $templateLocation;
		$this->templateName = $templateName;
		$this->sourceFolder = \IPS\ROOT_PATH . "/applications/{$app}/dev/html/{$templateLocation}/{$templateName}/";

	}
	
	/**
	 * Magic Method: Call Template Bit
	 *
	 * @param string $bit	Template Bit Name
	 * @param array $params	Parameters
	 * @return	string
	 */
	public function __call( string $bit, array $params )
	{
		/* What are we calling this? */
		$functionName = "theme_{$this->app}_{$this->templateLocation}_{$this->templateName}_{$bit}";

		/* If it doesn't exist, build it */
		if( !function_exists( 'IPS\\Theme\\'.$functionName ) )
		{
			/* Find the file */
			$file = $this->sourceFolder . $bit . '.phtml';
			
			/* Get the content or return an BadMethodCallException if the template doesn't exist */
			if ( !file_exists( $file ) )
			{
				throw new BadMethodCallException( 'NO_TEMPLATE_FILE - ' . $file );
			}
			
			$output = file_get_contents( $file );
			
			/* Parse the header tag */
			if ( !preg_match( '/^<ips:template parameters="(.+?)?"(\s+)?\/>(\r\n?|(\r\n?|\n))/', $output, $matches ) )
			{
				throw new BadMethodCallException( 'NO_HEADER - ' . $file );
			}
			
			/* Strip it */
			$output = preg_replace( '/^<ips:template parameters="(.+?)?"(\s+)?\/>(\r\n?|\n)/', '', $output );

			if ( DEBUG_TEMPLATES and ! Request::i()->isAjax() )
			{
				$output = "<!-- " . $functionName . " -->" . $output;
			}
			
			if ( IN_DEV AND get_called_class() !== 'IPS\Theme\System\Template' )
			{
				/* Template names that will allow inline style="" attributes */
				$allowedInlineStyle = array(
					'theme_core_admin_forms_widthheight', 'theme_core_global_forms_matrixRows', 'theme_core_admin_tables_table',
					'theme_core_admin_dashboard_dashboard', 'theme_core_front_messaging_template', 'theme_core_front_global_error',
					'theme_core_front_system_notifications', 'theme_core_front_system_coppaConsent', 'theme_calendar_front_view_view',
					'theme_core_global_global_poll', 'theme_forums_admin_settings_archiveRules', 'theme_core_front_system_test_menus',
					'theme_core_front_global_thumbImage', 'theme_downloads_front_browse_index',	'theme_core_front_system_test_submit', 
					'theme_core_front_system_test_galleryHome', 'theme_core_front_system_test_galleryAlbum', 'theme_core_front_system_test_galleryView', 'theme_downloads_front_submit_topic'
				);
	
				$allowedStyleBlocks = array(
					'globalTemplate', 'blankTemplate', 'loginTemplate', 'redirect', 'includeCSS',
					'dashboard', 'profile', 'profileHeader', 'diffExportWrapper', 'coppaConsent', 'attendees',
					'printInvoice', 'giftvoucherPrint'
				);

				$allowedScriptBlocks = array(
					'globalTemplate', 'blankTemplate', 'loginTemplate', 'includeJS', 'dashboard',
					'onlineUsers', 'registrations', 'linkedin', 'reddit', 'poll',
					'giftvoucherPrint', 'packingLabel', 'packingSheet', 'streamWrapper', 'embedExternal', 'embedInternal', 'pixel',
					'captchaKeycaptcha'
				);
	
				/* Check we're not being naughty */
				if( preg_match( "/<.+?style=['\"].+?>/i", $output ) and !in_array( $functionName, $allowedInlineStyle ) && $this->app != 'documentation' )
				{
					//trigger_error( "There is inline CSS in {$functionName}. Please move all styling into CSS files.", E_USER_ERROR );
				}
				if( !in_array( $bit, $allowedStyleBlocks ) and preg_match( "/<style.*?>/i", $output ) && $this->app != 'documentation' )
				{
					//trigger_error( "There is a style block in {$functionName}. Please move all styling into CSS files.", E_USER_ERROR );
				}
				if( preg_match( '/<[^>]+?\son(blur|change|click|contextmenu|copy|cut|dblclick|error|focus|focusin|focusout|hashchange|keydown|keypress|keyup|load|mousedown|mouseenter|mouseleave|mousemove|mouseout|mouseover|mouseup|mousewheel|paste|reset|resize|scroll|select|submit|textinput|unload|wheel)=[\'\"].+?>/i', $output ) )
				{
					//trigger_error( "There is a inline JavaScript {$functionName}. Please move all JavaScript into JS files.", E_USER_ERROR );
				}
				if( !in_array( $bit, $allowedScriptBlocks ) and $this->templateName !== 'embed' and preg_match( "/<script((?!src).)*>/i", $output ) && $this->app != 'documentation' )
				{
					//trigger_error( "There is a script block in {$functionName}. Please move all JavaScript into JS files.", E_USER_ERROR );
				}
			}
			
			/* Make it into a lovely function */
			Theme::makeProcessFunction( $output, $functionName, ( $matches[1] ?? '' ) );
		}
		
		/* Run it */
		ob_start();
		$function = 'IPS\\Theme\\'.$functionName;
		$return = $function( ...$params );
		if( $error = ob_get_clean() )
		{
			echo "<strong>{$functionName}</strong><br>{$error}<br><br><pre>{$output}";
			exit;
		}
		
		/* Return */
		return $return;
	}
}