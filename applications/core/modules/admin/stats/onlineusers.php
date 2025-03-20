<?php
/**
 * @brief		onlineusers
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Mar 2017
 */

namespace IPS\core\modules\admin\stats;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\core\Statistics\Chart;
use IPS\Dispatcher;
use IPS\Dispatcher\Controller;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Interval;
use IPS\Http\Url;
use IPS\Member;
use IPS\Output;
use IPS\Session;
use IPS\Settings;
use IPS\Theme;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * onlineusers
 */
class onlineusers extends Controller
{
	/**
	 * @brief	Has been CSRF-protected
	 */
	public static bool $csrfProtected = TRUE;

	/**
	 * @brief	Allow MySQL RW separation for efficiency
	 */
	public static bool $allowRWSeparation = TRUE;
	
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute() : void
	{
		Dispatcher::i()->checkAcpPermission( 'onlineusers_manage' );
		parent::execute();
	}

	/**
	 * Online users activity chart
	 *
	 * @return	void
	 */
	protected function manage() : void
	{
		/* Show button to adjust settings */
		Output::i()->sidebar['actions']['settings'] = array(
			'icon'		=> 'cog',
			'title'		=> 'prunesettings',
			'link'		=> Url::internal( 'app=core&module=stats&controller=onlineusers&do=settings' ),
			'data'		=> array( 'ipsDialog' => '', 'ipsDialog-title' => Member::loggedIn()->language()->addToStack('prunesettings') )
		);

		$chart = Chart::loadFromExtension( 'core', 'OnlineUsers' )->getChart( Url::internal( "app=core&module=stats&controller=onlineusers" ) );

		Output::i()->title = Member::loggedIn()->language()->addToStack('menu__core_stats_onlineusers');
		Output::i()->output	= (string) $chart;
	}

	/**
	 * Prune Settings
	 *
	 * @return	void
	 */
	protected function settings() : void
	{
		$form = new Form;
		$form->add( new Interval( 'stats_online_users_prune', Settings::i()->stats_online_users_prune, FALSE, array( 'valueAs' => Interval::DAYS, 'unlimited' => 0, 'unlimitedLang' => 'never' ), NULL, Member::loggedIn()->language()->addToStack('after'), NULL, 'prune_log_moderator' ) );
	
		if ( $values = $form->values() )
		{
			$form->saveAsSettings();
			Session::i()->log( 'acplog__statsonlineusers_settings' );
			Output::i()->redirect( Url::internal( 'app=core&module=stats&controller=onlineusers' ), 'saved' );
		}
	
		Output::i()->title		= Member::loggedIn()->language()->addToStack('prunesettings');
		Output::i()->output 	= Theme::i()->getTemplate('global')->block( 'prunesettings', $form, FALSE );
	}
}