<?php
/**
 * @brief		Build CodeMirror for release
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		21 Nov 2016
 */

namespace IPS\core\extensions\core\Build;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Garfix\JsMinify\Minifier;
use IPS\Extensions\BuildAbstract;
use IPS\Theme;
use RuntimeException;
use function defined;
use function file_put_contents;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Build CodeMirror for release
 */
class Codemirror extends BuildAbstract
{
	/**
	 * Build
	 *
	 * @return	void
	 * @throws	RuntimeException
	 * @note	You can define JAVA_PATH in constants.php if you need to specify the path to your java executable
	 */
	public function build() : void
	{
		/* Copy the CSS file */
		$css = file_get_contents( \IPS\ROOT_PATH . '/applications/core/dev/codemirror/lib/codemirror.css' );
		
		/* Copy the JS files */
		$js = ";";
		$js .= file_get_contents( \IPS\ROOT_PATH . '/applications/core/dev/codemirror/lib/codemirror.js' );		
		foreach ( array( 'clike', 'css', 'htmlmixed', 'javascript', 'lua', 'perl', 'php', 'python', 'ruby', 'sql', 'stex', 'swift', 'xml' ) as $mode )
		{
			$js .= ';' . file_get_contents( \IPS\ROOT_PATH . "/applications/core/dev/codemirror/mode/{$mode}/{$mode}.js" );
		}
		
		/* Add our addons */
		foreach ( array( 'merge/merge', 'search/search', 'search/searchcursor' ) as $addon )
		{
			if ( file_exists( \IPS\ROOT_PATH . "/applications/core/dev/codemirror/addon/{$addon}.js" ) )
			{
				$js .= ';' . file_get_contents( \IPS\ROOT_PATH . "/applications/core/dev/codemirror/addon/{$addon}.js" );
			}
			if ( file_exists( \IPS\ROOT_PATH . "/applications/core/dev/codemirror/addon/{$addon}.css" ) )
			{
				$css .= file_get_contents( \IPS\ROOT_PATH . "/applications/core/dev/codemirror/addon/{$addon}.css" );
			}
		}
				
		/* Minify and write */
		require_once( \IPS\ROOT_PATH . '/system/3rd_party/JsMinify/Minifier.php' );
		require_once( \IPS\ROOT_PATH . '/system/3rd_party/JsMinify/MinifierError.php' );
		require_once( \IPS\ROOT_PATH . '/system/3rd_party/JsMinify/MinifierExpressions.php' );

		$css = Theme::minifyCss( $css );
		file_put_contents( \IPS\ROOT_PATH . '/applications/core/interface/static/codemirror/codemirror.css', $css );
		$js = Minifier::minify( $js, array( 'flaggedComments' => false ) );
		file_put_contents( \IPS\ROOT_PATH . '/applications/core/interface/static/codemirror/codemirror.js', $js );
		
		/* Finish */
		$this->finish();
	}
	
	/**
	 * Finish Build
	 *
	 * @return	void
	 */
	protected function finish() : void
	{

	}
}