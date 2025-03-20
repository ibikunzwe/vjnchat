<?php
/**
 * @brief		Community Enhancements
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community

 * @since		13 Sep 2021
 */

namespace IPS\core\extensions\core\CommunityEnhancements;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Extensions\CommunityEnhancementsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Codemirror;
use IPS\Helpers\Form\YesNo;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Request;
use IPS\Settings;
use IPS\Theme;
use LogicException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Community Enhancement
 */
class GoogleAnalytics extends CommunityEnhancementsAbstract
{
	/**
	 * @brief	Enhancement is enabled?
	 */
	public bool $enabled	= FALSE;

	/**
	 * @brief	IPS-provided enhancement?
	 */
	public bool $ips	= FALSE;

	/**
	 * @brief	Enhancement has configuration options?
	 */
	public bool $hasOptions	= TRUE;

	/**
	 * @brief	Icon data
	 */
	public string $icon	= "google_analytics.png";
	
	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		$this->enabled = ( Settings::i()->ga_enabled and Settings::i()->ga_code );
	}
	
	/**
	 * Edit
	 *
	 * @return	void
	 */
	public function edit() : void
	{
		$form = new Form;
		
		$form->add( new YesNo( 'ga_enabled', Settings::i()->ga_enabled, FALSE, array(
			'togglesOn' => [ 'ga_code' ]
		) ) );
		$form->add( new Codemirror( 'ga_code', Settings::i()->ga_code, null, array( 'height' => 150, 'codeModeAllowedLanguages' => [ 'xml' ] ), function( $val ){
			if( empty( $val ) and Request::i()->ga_enabled_checkbox )
			{
				throw new InvalidArgumentException('form_required');
			}
		}, NULL, NULL, 'ga_code' ) );
		
		if( $form->values() )
		{
			try
			{
				$form->saveAsSettings();

				Output::i()->inlineMessage	= Member::loggedIn()->language()->addToStack('saved');
			}
			catch ( LogicException $e )
			{
				$form->error = $e->getMessage();
			}
		}
		
		Output::i()->sidebar['actions'] = array(
			'help'	=> array(
				'title'		=> 'learn_more',
				'icon'		=> 'question-circle',
				'link'		=> Url::ips( 'docs/googleanalytics' ), //@todo
				'target'	=> '_blank'
			),
		);
		
		Output::i()->output = Theme::i()->getTemplate( 'global' )->block( 'enhancements__core_GoogleAnalytics', $form );
	}
	
	/**
	 * Enable/Disable
	 *
	 * @param	$enabled	bool	Enable/Disable
	 * @return	void
	 * @throws	LogicException
	 */
	public function toggle( bool $enabled ) : void
	{
		if ( $enabled )
		{
			if ( Settings::i()->ga_code )
			{
				Settings::i()->changeValues( array( 'ga_enabled' => 1 ) );
			}
			else
			{
				throw new DomainException;
			}
		}
		else
		{
			Settings::i()->changeValues( array( 'ga_enabled' => 0 ) );
		}
	}
}