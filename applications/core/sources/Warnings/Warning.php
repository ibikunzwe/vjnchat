<?php
/**
 * @brief		Warning Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		23 Jul 2013
 */

namespace IPS\core\Warnings;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DateInterval;
use DomainException;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\Content;
use IPS\Content\Comment;
use IPS\Content\Item;
use IPS\core\Messenger\Conversation;
use IPS\core\Messenger\Message;
use IPS\DateTime;
use IPS\Db;
use IPS\Email;
use IPS\File;
use IPS\Helpers\Form\CheckboxSet;
use IPS\Helpers\Form\Date;
use IPS\Helpers\Form\Editor;
use IPS\Helpers\Form\Number;
use IPS\Helpers\Form\Select;
use IPS\Http\Url;
use IPS\Log;
use IPS\Member;
use IPS\Node\Model;
use IPS\Notification;
use IPS\Output;
use IPS\Request;
use IPS\Session;
use IPS\Settings;
use IPS\Task;
use IPS\Theme;
use OutOfRangeException;
use UnderflowException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_numeric;
use const IPS\NOTIFICATION_BACKGROUND_THRESHOLD;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Warning Model
 */
class Warning extends Item
{
	/* !\IPS\Patterns\ActiveRecord */
	
	/**
	 * @brief	Database Table
	 */
	public static ?string $databaseTable = 'core_members_warn_logs';
	
	/**
	 * @brief	Database Prefix
	 */
	public static string $databasePrefix = 'wl_';
	
	/**
	 * @brief	Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	Title
	 */
	public static string $title = 'warning';

	/**
	 * @return DateInterval|null
	 */
	public function get_mq_interval() : ?DateInterval
	{
		if( $this->mq != -1 )
		{
			try
			{
				return new DateInterval( $this->mq );
			}
			catch( Exception $e ){}
		}

		return null;
	}

	/**
	 * @return DateInterval|null
	 */
	public function get_rpa_interval() : ?DateInterval
	{
		if( $this->rpa != -1 )
		{
			try
			{
				return new DateInterval( $this->rpa );
			}
			catch( Exception $e ){}
		}

		return null;
	}

	/**
	 * @return DateInterval|null
	 */
	public function get_suspend_interval() : ?DateInterval
	{
		if( $this->suspend != -1 )
		{
			try
			{
				return new DateInterval( $this->suspend );
			}
			catch( Exception $e ){}
		}

		return null;
	}
	
	/**
	 * Should posting this increment the poster's post count?
	 *
	 * @param	Model|NULL	$container	Container
	 * @return	bool
	 */
	public static function incrementPostCount( ?Model $container = NULL ): bool
	{
		return FALSE;
	}
	
	/**
	 * Load record based on a URL
	 *
	 * @param	Url	$url	URL to load from
	 * @return	static
	 * @throws	InvalidArgumentException
	 * @throws	OutOfRangeException
	 */
	public static function loadFromUrl( Url $url ): static
	{
		return static::load( $url->hiddenQueryString['w'] );
	}
	
	/**
	 * Undo warning
	 *
	 * @return	void
	 */
	public function undo() : void
	{
		$member = Member::load( $this->member );
		
		/* Take off the points */
		if ( ( !$this->expire_date or $this->expire_date == -1 ) or $this->expire_date > time() )
		{
			$member->warn_level -= $this->points;
			if ( $member->warn_level < 0 )
			{
				$member->warn_level = 0;
			}
		}
		
		/* Undo the actions */
		$consequences = array();
		foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $w => $m )
		{
			if ( $this->$w and ( $this->$w < time() or $this->$w == -1 ) )
			{
				try
				{
					$latest = Db::i()->select( '*', 'core_members_warn_logs', array( "wl_member=? AND wl_{$w}<>0 AND wl_id !=?", $member->member_id, $this->id ), 'wl_date DESC' )->first();
					$member->$m = $latest[ 'wl_' . $w ];
					$consequences[ $m ] = $latest['wl_id'];
				}
				catch ( UnderflowException $e )
				{
					$member->$m = 0;
					$consequences[ $m ] = 0;
				}
			}
		}

		if ( $this->cheev_point_reduction )
		{
			$member->achievements_points += $this->cheev_point_reduction;
			$consequences['cheev_point_reduction'] = $this->cheev_point_reduction;
		}
		
		/* Save */
		$member->save();
		
		/* Log */
		$member->logHistory( 'core', 'warning', array( 'type' => 'revoke', 'wid' => $this->id, 'reason' => $this->reason, 'points' => $this->points, 'consequences' => $consequences ) );
	}
	
	/**
	 * Delete warning
	 *
	 * @return    void
	 */
	public function delete(): void
	{		
		/* Unaknowledged Warnings? */
		$member = Member::load( $this->member );
		
		if ( Settings::i()->warnings_acknowledge )
		{
			$count = Db::i()->select( 'COUNT(*)', 'core_members_warn_logs', array( "wl_member=? AND wl_id<>? AND wl_acknowledged=0", $member->member_id, $this->id ) )->first();
			$member->members_bitoptions['unacknowledged_warnings'] = (bool) $count;
		}
		else
		{
			$member->members_bitoptions['unacknowledged_warnings'] = FALSE;
		}
		$member->save();
		
		/* Delete */
		parent::delete();
	}
	
	/**
	 * Unclaim attachments
	 *
	 * @return	void
	 */
	protected function unclaimAttachments(): void
	{
		File::unclaimAttachments( 'core_Modcp', $this->id, NULL, 'member' );
		File::unclaimAttachments( 'core_Modcp', $this->id, NULL, 'mod' );
	}
	
	/**
	 * Get Content for Warning
	 *
	 * @return	Content|NULL
	 */
	public function contentObject(): ?Content
	{		
		if ( $this->content_app and $this->content_module )
		{
			if ( $this->content_app === 'core' and $this->content_module === 'messaging' )
			{
				try
				{
					if ( $this->content_id2 )
					{
						return Message::load( $this->content_id2 );
					}
					else
					{
						return Conversation::load( $this->content_id2 );
					}
				}
				catch ( OutOfRangeException $e )
				{
					return NULL;
				}
			}
			else
			{
				try
				{
					$extensions = Application::load( $this->content_app )->extensions( 'core', 'ContentRouter' );
					foreach ( $extensions as $ext )
					{
						foreach ( $ext->classes as $class )
						{
							if ( $class::$module == $this->content_module )
							{
								try
								{
									return $class::load( $this->content_id1 );
								}
								catch ( OutOfRangeException $e )
								{
									return NULL;
								}
							}
							elseif ( $commentClass = $class::$commentClass and $class::$module . '-comment' == $this->content_module )
							{
								try
								{
									return $commentClass::load( $this->content_id2 );
								}
								catch ( OutOfRangeException $e )
								{
									return NULL;
								}
							}
							if ( isset( $class::$reviewClass ) AND $reviewClass = $class::$reviewClass and $class::$module . '-review' == $this->content_module )
							{
								try
								{
									return $reviewClass::load( $this->content_id2 );
								}
								catch ( OutOfRangeException $e )
								{
									return NULL;
								}
							}
						}
					}
				}
				catch ( OutOfRangeException $e )
				{
					return NULL;
				}
			}
		}
		return NULL;
	}

		
	/* !\IPS\Content\Item */
	
	/**
	 * @brief	Application
	 */
	public static string $application = 'core';
		
	/**
	 * @brief	Database Column Map
	 */
	public static array $databaseColumnMap = array(
		'date'		=> 'date',
		'author'	=> 'moderator',
	);
	
	/**
	 * @brief	Language prefix for forms
	 */
	public static string $formLangPrefix = 'warn_';
	
	/**
	 * Get elements for add/edit form
	 *
	 * @param Item|null $item		The current item if editing or NULL if creating
	 * @param	Model|null						$container	Container (e.g. forum) ID, if appropriate
	 * @return	array
	 */
	public static function formElements( ?Item $item=NULL, ?Model $container=NULL ): array
	{
		/* Get the reasons */
		$reasons = array();
        $roots = Reason::roots();
		foreach ( $roots as $reason )
		{
			$reasons[ $reason->_id ] = $reason->_title;
		}
		if ( Member::loggedIn()->modPermission('warnings_enable_other') )
		{
			$reasons['other'] = 'core_warn_reason_other';
		}
		
		foreach( $roots AS $root )
		{
			$first = $root;
			break;
		}

		/* Build the form */
		$elements[] = new Select( 'warn_reason', NULL, TRUE, array( 'options' => $reasons ) );
		$elements[] = new Number( 'warn_points', 0, FALSE, array( 'valueToggles' => array( 'warn_remove' ), 'disabled' => ( !$first->points_override ) ) );
		$elements[] = new Date( 'warn_remove', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'never' ), NULL, NULL, NULL, 'warn_remove' );
		$elements[] = new Number( 'warn_cheeve_point_reduction', $first->cheev_point_reduction, FALSE, array( 'disabled' => ( !$first->cheev_override ) ) );
		$elements[] = new Editor( 'warn_member_note', NULL, FALSE, array( 'app' => 'core', 'key' => 'Modcp', 'autoSaveKey' => "warn-member-" . Request::i()->id, 'attachIds' => ( $item === NULL ? NULL : array( $item->id, NULL, 'member' ) ), 'minimize' => 'warn_member_note_placeholder' ) );
		$elements[] = new Editor( 'warn_mod_note', NULL, FALSE, array( 'app' => 'core', 'key' => 'Modcp', 'autoSaveKey' => "warn-mod-" . Request::i()->id, 'attachIds' => ( $item === NULL ? NULL : array( $item->id, NULL, 'mod' ) ), 'minimize' => 'warn_mod_note_placeholder' ) );
		$elements[] = new CheckboxSet( 'warn_punishment', array(), FALSE, array(
			'options' 	=> array( 'mq' => 'warn_mq', 'rpa' => 'warn_rpa', 'suspend' => 'warn_suspend' ),
			'toggles'	=> array( 'mq' => array( 'form_warn_mq' ), 'rpa' => array( 'form_warn_rpa' ), 'suspend' => array( 'form_warn_suspend' ) ),
		) );
		$elements[] = new Date( 'warn_mq', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), function( $val )
			{
				if( $val )
				{
					$now = new DateTime;
	
					if( $val !== -1 and $val->getTimestamp() < $now->getTimestamp() )
					{
						throw new DomainException( 'error_date_not_future' );
					}
				}
				
			}, Member::loggedIn()->language()->addToStack('until'), NULL, 'warn_mq' );
		
		$elements[] = new Date( 'warn_rpa', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), function( $val )
			{
				if( $val )
				{
					$now = new DateTime;
					
					if( $val !== -1 and $val->getTimestamp() < $now->getTimestamp() )
					{
						throw new DomainException( 'error_date_not_future' );
					}
				}
				
			}, Member::loggedIn()->language()->addToStack('until'), NULL, 'warn_rpa' );
		
		$elements[] = new Date( 'warn_suspend', -1, FALSE, array( 'time' => TRUE, 'unlimited' => -1, 'unlimitedLang' => 'indefinitely' ), function( $val )
			{
				if( $val )
				{
					$now = new DateTime;
					
					if( $val !== -1 and $val->getTimestamp() < $now->getTimestamp() )
					{
						throw new DomainException( 'error_date_not_future' );
					}
				}
				
			}, Member::loggedIn()->language()->addToStack('until'), NULL, 'warn_suspend' );

		$member = Member::load( Request::i()->id );

		if( $member->temp_ban OR $member->mod_posts OR $member->restrict_post )
		{
			$elements[] = Member::loggedIn()->language()->addToStack('member_existing_penalties', TRUE, array( 'sprintf' => $member->name ) );
		}

		Member::loggedIn()->language()->words['warn_cheeve_point_reduction_desc'] = Member::loggedIn()->language()->addToStack( 'warn_cheeve_point_reduction__desc', FALSE, [ 'sprintf' => [ $member->name, $member->achievements_points, $member->name ] ] );
		
		/* Return */
		return $elements;
	}
	
	/**
	 * Process created object BEFORE the object has been created
	 *
	 * @param array $values	Values from form
	 * @return	void
	 */
	protected function processBeforeCreate( array $values ): void
	{		
		$this->member = Request::i()->member;

		/* Permission Check */
		if ( !Member::loggedIn()->canWarn( Member::load( $this->member ) ) )
		{
			Output::i()->error( 'no_module_permission', '2C150/3', 403, '' );
		}

		$values = $this->processWarning($values, Member::loggedIn());

		parent::processBeforeCreate( $values );
	}
	
	/**
	 * Process created object AFTER the object has been created
	 *
	 * @param	Comment|NULL	$comment	The first comment
	 * @param	array						$values		Values from form
	 * @return	void
	 */
	protected function processAfterCreate( ?Comment $comment, array $values ): void
	{
		File::claimAttachments( "warn-member-{$this->member}", $this->id, NULL, 'member' );
		File::claimAttachments( "warn-mod-{$this->member}", $this->id, NULL, 'mod' );
		
		$member = Member::load( $this->member );
		if ( $this->points )
		{
			$member->warn_level += $this->points;
		}
		$consequences = array();
		foreach ( array( 'mq' => 'mod_posts', 'rpa' => 'restrict_post', 'suspend' => 'temp_ban' ) as $k => $v )
		{
			if ( $this->$k )
			{
				$consequences[ $v ] = $this->$k;
				if ( $this->$k != -1 )
				{
					$member->$v = DateTime::create()->add( new DateInterval( $this->$k ) )->getTimestamp();
				}
				else
				{
					$member->$v = $this->$k;
				}
			}
		}

		if ( $this->cheev_point_reduction )
		{

			if ( $member->achievements_points >= $this->cheev_point_reduction )
			{
				/* Eg points to deduct is 50, user has 100 */
				$member->achievements_points -= $this->cheev_point_reduction;
			}
			else
			{
				/* Eg points to deduct is 50, user has 10, so set points deducted to 10 so undo doesn't add 50 back */
				$this->cheev_point_reduction = $member->achievements_points;
				$member->achievements_points -= $this->cheev_point_reduction;

				$this->save();
			}

			$consequences['cheev_point_reduction'] = $this->cheev_point_reduction;
		}

		$member->members_bitoptions['unacknowledged_warnings'] = (bool) Settings::i()->warnings_acknowledge;
		$member->save();
		$member->logHistory( 'core', 'warning', array( 'wid' => $this->id, 'points' => $this->points, 'reason' => $this->reason, 'consequences' => $consequences ) );

		/* Moderator log */
		Session::i()->modLog( 'modlog__action_warn_user', array( $member->name => FALSE ) );

		/* Webhook */
		Webhook::fire( 'member_warned', $this );
		parent::processAfterCreate( $comment, $values );
	}

	/**
	 * Process the warning values
	 *
	 * @param array $values	Values from form submission
	 * @param Member $member	Moderator to process warning under
	 * @param bool $reset	Reset points based on reason
	 * @return	array
	 */
	public function processWarning (array $values, Member $member, bool $reset=FALSE ): array
	{
		/* Work out points and expiry date */
		$this->expire_date = NULL;
		$this->points = $values['warn_points'];
		if ( is_numeric( $values['warn_reason'] ) )
		{
			$reason = Reason::load( $values['warn_reason'] );
			if ( !$reason->points_override OR ( !$this->points AND $reset ) )
			{
				$this->points = $reason->points;
			}
			if ( !$reason->remove_override )
			{
				if ( $reason->remove_override == -1 )
				{
					$this->points = -1;
				}
				else
				{
					if ( $reason->remove and $reason->remove != -1 )
					{
						$expire = DateTime::create();
						if ( $reason->remove_unit == 'h' )
						{
							$expire->add( new DateInterval( "PT{$reason->remove}H" ) );
						}
						else
						{
							$expire->add( new DateInterval( "P{$reason->remove}D" ) );
						}
						$this->expire_date = $expire->getTimestamp();
					}
					else
					{
						$this->expire_date = -1;
					}
				}
			}
			else
			{
				if ( $values['warn_remove'] instanceof DateTime )
				{
					$this->expire_date = $values['warn_remove']->getTimestamp();
				}
				else
				{
					$this->expire_date = $values['warn_remove'];
				}
			}

			if ( ! $reason->cheev_override )
			{
				$this->cheev_point_reduction = $reason->cheev_point_reduction;
			}
			else
			{
				$this->cheev_point_reduction = $values['warn_cheeve_point_reduction'];
			}
			
			$this->reason = $values['warn_reason'];
		}
		else
		{
			$this->reason = 0;
			
			if ( $values['warn_remove'] instanceof DateTime )
			{
				$this->expire_date = $values['warn_remove']->getTimestamp();
			}
			else
			{
				$this->expire_date = $values['warn_remove'];
			}
		}
		if ( !$this->points )
		{
			$this->expire_date = -1;
		}
				
		/* If we can't override the action, change it back */
		try
		{
			$action = Db::i()->select( '*', 'core_members_warn_actions', array( 'wa_points<=?', ( Member::load( $this->member )->warn_level + $this->points ) ), 'wa_points DESC', 1 )->first();
			if ( !$action['wa_override'] )
			{
				foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
				{
					if( !in_array( $k, $values['warn_punishment'] ) )
					{
						$values['warn_punishment'][] = $k;
					}

					if ( $action[ 'wa_' . $k ] == -1 )
					{
						$values[ 'warn_' . $k ] = -1;
					}
					elseif ( $action[ 'wa_' . $k ] )
					{
						$values[ 'warn_' . $k ] = DateTime::create()->add( new DateInterval( $action[ 'wa_' . $k . '_unit' ] == 'h' ? "PT{$action[ 'wa_' . $k ]}H" : "P{$action[ 'wa_' . $k ]}D" ) );
					}
					else
					{
						$values[ 'warn_' . $k ] = 0;//NULL;
					}
				}
			}
		}
		catch ( UnderflowException $e )
		{
			if ( !$member->modPermission('warning_custom_noaction') )
			{
				foreach ( array( 'mq', 'rpa', 'suspend' ) as $k )
				{
					$values[ 'warn_' . $k ] = NULL;
				}
			}
		}
		
		/* We do this after the action is checked because the moderator may not be able to override the action, in which case the actual values won't have been submitted */
		foreach( $values['warn_punishment'] AS $p )
		{
			if ( $values['warn_' . $p ] === NULL )
			{
				Output::i()->error( 'no_warning_action_time', '1C150/2', 403, '' );
			}
		}
		
		/* Set notes */
		$this->note_member = $values['warn_member_note'];
		$this->note_mods = $values['warn_mod_note'];
		
		/* Set acknowledged */
		$this->acknowledged = !Settings::i()->warnings_acknowledge;
		
		/* Construct referrer */
		$ref = Request::i()->ref ? json_decode( base64_decode( Request::i()->ref ), TRUE ) : NULL;
		$this->content_app	= $ref['app'] ?? NULL;
		$this->content_module	= $ref['module'] ?? NULL;
		$this->content_id1	= $ref['id_1'] ?? NULL;
		$this->content_id2	= $ref['id_2'] ?? NULL;
		if ( $content = $this->contentObject() and !$content->canView() )
		{
			$this->content_app = NULL;
			$this->content_module = NULL;
			$this->content_id1 = NULL;
			$this->content_id2 = NULL;
		}
		
		/* Work out the timeframes for the penalties */
		if ( count( $values['warn_punishment'] ) )
		{
			foreach ( array( 'mq', 'rpa', 'suspend' ) as $f )
			{
				if ( !in_array( $f, $values['warn_punishment'] ) OR $values['warn_' . $f ] === 0 )
				{
					continue;
				}
				
				if ( $values[ 'warn_' . $f ] instanceof DateTime )
				{
					$difference = DateTime::create()->setTimezone( $values[ 'warn_' . $f ]->getTimezone() )->diff( $values[ 'warn_' . $f ] );

					/**
					 * Due to a bug in PHP 8.0, DateTime::diff() can return -1 for the hour, and an extra day. We need to look for this specifically and set hour to 23, and reduce days by 1. This bug is fixed in 8.1, so this should be removed when that version is required.
					 * @see <a href='https://bugs.php.net/bug.php?id=66545'>#66545</a>
					 */
					if ( version_compare( PHP_VERSION, '8.1.0', '<' ) )
					{
						if ( $difference->h === -1 )
						{
							$difference->h = 23;
							$difference->d = $difference->d - 1;
						}
					}

					$period = 'P';
					foreach ( array( 'y' => 'Y', 'm' => 'M', 'd' => 'D' ) as $k => $v )
					{
						if ( $difference->$k )
						{
							$period .= $difference->$k . $v;
						}
					}
					$time = '';
					foreach ( array( 'h' => 'H', 'i' => 'M', 's' => 'S' ) as $k => $v )
					{
						if ( $difference->$k )
						{
							$time .= $difference->$k . $v;
						}
					}
					if ( $time )
					{
						$period .= 'T' . $time;
					}
					
					$this->$f = $period;
				}
				else
				{
					$this->$f = $values[ 'warn_' . $f ];
				}
			}
		}

		return $values;
	}
	
	/**
	 * Get moderators to notify about warnings
	 *
	 * @return	array
	 */
	public function getModerators() : array
	{
		$moderators = array( 'm' => array(), 'g' => array() );
		foreach ( Db::i()->select( '*', 'core_moderators' ) as $mod )
		{
			$canView = FALSE;
			
			if ( $mod['perms'] == '*' )
			{
				$canView = TRUE;
			}
			
			if ( $canView === FALSE )
			{
				$perms = json_decode( $mod['perms'], TRUE );
				
				if ( isset( $perms['mod_see_warn'] ) AND $perms['mod_see_warn'] === TRUE )
				{
					$canView = TRUE;
				}
			}
			
			if ( $canView === TRUE )
			{
				$moderators[ $mod['type'] ][] = $mod['id'];
			}
		}

		return $moderators;
	}

	/**
	 * Build and return the WHERE statements for warning notifications
	 *
	 * @param array $moderators Moderator data
	 * @return  array
	 */
	public function buildNotificationsWhere( array $moderators ): array
	{
		$where = array();
		if ( !empty( $moderators['m'] ) )
		{
			$where[] = Db::i()->in( 'member_id', $moderators['m'] );
		}

		$where[] = Db::i()->in( 'member_group_id', $moderators['g'] );
		$where[] = Db::i()->findInSet( 'mgroup_others', $moderators['g'] );

		/* Don't annoy the acting moderator with a notification for the warning they /just/ issued */
		return array(
			sprintf(
				'( %s ) AND %s',
				implode( ' OR ', $where ),
				'member_id<>?'
			),
			$this->mapped( 'author' )
		);
	}

	/**
	 * Get notification count
	 *
	 * @param array $moderators	Moderator data
	 * @return	int
	 */
	public function notificationsCount( array $moderators ): int
	{
		return Db::i()->select(
			'COUNT(*)',
			'core_members',
			$this->buildNotificationsWhere( $moderators )
		)->first();
	}
	
	/**
	 * Send notifications
	 *
	 * @return	void
	 */
	public function sendNotifications(): void
	{
		/* Queue if there's lots, or just send them */
		if ( $this->notificationsCount( $this->getModerators() ) > NOTIFICATION_BACKGROUND_THRESHOLD )
		{
			Task::queue( 'core', 'WarnNotifications', array( 'class' => get_class( $this ), 'item' => $this->id, 'sentTo' => array() ), 1 );
		}
		else
		{
			$this->sendNotificationsBatch();
		}

		Email::buildFromTemplate( 'core', 'warning', array( $this ), Email::TYPE_TRANSACTIONAL )->send( Member::load( $this->member ) );
	}

	/**
	 * Send notifications batch
	 *
	 * @param int $offset		Current offset
	 * @param array $sentTo		Members who have already received a notification and how - e.g. array( 1 => array( 'inline', 'email' )
	 * @param string|null $extra		Additional data
	 * @return	int|null		New offset or NULL if complete
	 */
	public function sendNotificationsBatch( int $offset=0, array &$sentTo=array(), string $extra=null ): ?int
	{
		$moderators	= $this->getModerators();
		$notification = new Notification( Application::load('core'), 'warning_mods', $this, array( $this ) );

		$members = Db::i()->select(
			'*',
			'core_members',
			$this->buildNotificationsWhere( $moderators ),
			'member_id ASC',
			array( $offset, static::NOTIFICATIONS_PER_BATCH )
		);
		foreach ( $members as $member )
		{
			Log::debug( "Sent notification to {$member['name']}", 'warn_fix' );
			$notification->recipients->attach( Member::constructFromData( $member ) );
		}

		$sentTo = $notification->send( $sentTo );

		/* Update the queue */
		$newOffset = $offset + static::NOTIFICATIONS_PER_BATCH;
		if ( $newOffset > $this->notificationsCount( $moderators ) )
		{
			return NULL;
		}
		return $newOffset;
	}

	/**
	 * @brief	Cached URLs
	 */
	protected mixed $_url = array();

	/**
	 * Get URL
	 *
	 * @param	string|NULL		$action		Action
	 * @return	Url
	 */
	public function url( string|null $action=NULL ): Url
	{
		$_key	= $action ? md5( $action ) : NULL;

		if( !isset( $this->_url[ $_key ] ) )
		{
			$this->_url[ $_key ] = Url::internal( "app=core&module=system&controller=warnings&id={$this->member}&w={$this->id}", 'front', 'warn_view', Member::load( $this->member )->members_seo_name );
		
			if ( $action )
			{
				$this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
			}
		}
	
		return $this->_url[ $_key ];
	}
	
	/**
	 * Get mapped value
	 *
	 * @param string $key	date,content,ip_address,first
	 * @return	mixed
	 */
	public function mapped( string $key ): mixed
	{
		if ( $key === 'title' )
		{
			return Member::loggedIn()->language()->addToStack( "core_warn_reason_{$this->reason}" );
		}
		return parent::mapped( $key );
	}
	
	/* !Permissions */
	
	/**
	 * Can give warning?
	 *
	 * @param	Member	$member		The member
	 * @param	Model|NULL	$container	Container (e.g. forum), if appropriate
	 * @param bool $showError	If TRUE, rather than returning a boolean value, will display an error
	 * @return	bool
	 * @note	If we can't see warnings, we can't issue them either
	 */
	public static function canCreate( Member $member, Model $container=null, bool $showError=FALSE ): bool
	{
		$return = $member->modPermission('mod_can_warn') && $member->modPermission('mod_see_warn');
		if ( !$return and $showError )
		{
			Output::i()->error( 'no_module_permission', '2C150/1', 403, '' );
		}
		
		return $return;
	}
	
	/**
	 * Does a member have permission to access?
	 *
	 * @param Member|null $member	The member to check for
	 * @return	bool
	 */
	public function canView( Member $member=null ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $member->modPermission('mod_see_warn') or $this->member === $member->member_id;
	}
	
	/**
	 * Does a member have permission to view the details of the warning?
	 *
	 * @param Member|null $member	The member to check for
	 * @return	bool
	 */
	public function canViewDetails( Member $member=null ): bool
	{
		$member = $member ?: Member::loggedIn();
				
		if ( $member->modPermission('mod_see_warn') )
		{
			return TRUE;
		}
		
		if ( Settings::i()->warn_show_own and $this->member === $member->member_id )
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Can acknowledge?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canAcknowledge( Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return intval($this->member) === intval($member->member_id);
	}
				
	/**
	 * Can delete?
	 *
	 * @param Member|null $member	The member to check for (NULL for currently logged in member)
	 * @return	bool
	 */
	public function canDelete( Member $member=NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return ( $member->modPermission('mod_revoke_warn') and $member->modPermission('mod_see_warn') );
	}
	
	/* !\IPS\Helpers\Table */
	
	/**
	 * @brief	Enable table hover URL
	 */
	public ?bool $tableHoverUrl = TRUE;
	
	/**
	 * Icon for table view
	 *
	 * @return	array
	 */
	public function tableIcon(): array
	{
		return Theme::i()->getTemplate( 'modcp', 'core', 'front' )->warningRowPoints( $this->points );
	}
		
	/**
	 * Method to get description for table view
	 *
	 * @return	string
	 */
	public function tableDescription(): string
	{
		return $this->note_mods;
	}

	/**
	 * Get output for API
	 *
	 * @param	Member|NULL	$authorizedMember	The member making the API request or NULL for API Key / client_credentials
	 * @return    array
	 * @apiresponse	int						id				ID number
	 * @apiresponse	\IPS\Member				member			Member who was warned
	 * @apiresponse	\IPS\Member				moderator		Moderator who performed the warning
	 * @apiresponse	int						points			The points issued for the warning
	 * @apiresponse	\IPS\core\Warnings\Reason		reason		Warn reason if one was specified
	 * @apiresponse	datetime|int			expiration		Date the warning expires, or -1 if the warning does not expire
	 * @apiresponse	datetime				date			Date the warning was issued
	 * @apiresponse	bool					acknowledged	Whether or not the warning has been acknowledged
	 * @apiresponse	string					memberNotes		Warn notes left for the member
	 * @clientapiresponse	string					moderatorNotes	Warn notes visible to moderators
	 * @apiresponse	bool					modQueuePermanent		Member is permanently moderator queued
	 * @apiresponse	string|NULL				modQueue		DateTimeInterval (from date) when the member will be removed from the moderator queue or null
	 * @apiresponse	bool					restrictPostsPermanent		Member is permanently on post restriction
	 * @apiresponse	string|NULL				restrictPosts	DateTimeInterval (from date) when the member will be removed from post restriction or null
	 * @apiresponse	bool					suspendPermanent		Member is permanently on suspension
	 * @apiresponse	string|NULL				suspend			DateTimeInterval (from date) when the member will be unsuspended or null
	 */
	public function apiOutput( Member $authorizedMember = NULL ): array
	{
		$response = array(
			'id'				=> $this->id,
			'member'			=> Member::load( $this->member )->apiOutput( $authorizedMember ),
			'moderator'			=> Member::load( $this->moderator )->apiOutput( $authorizedMember ),
			'points'			=> $this->points,
			'reason'			=> $this->reason ? Reason::load( $this->reason )->apiOutput() : NULL,
			'expiration'		=> ( $this->expire_date == -1 ) ? $this->expire_date : DateTime::ts( $this->expire_date )->rfc3339(),
			'date'				=> DateTime::ts( $this->date )->rfc3339(),
			'acknowledged'		=> $this->acknowledged,
			'memberNotes'		=> $this->note_member,
			'modQueue'			=> ( $this->mq != -1 ) ? $this->mq : null,
			'restrictPosts'		=> ( $this->rpa != -1 ) ? $this->rpa : null,
			'suspend'			=> ( $this->suspend != -1 ) ? $this->suspend : null,
			'modQueuePermanent'	=> $this->mq == -1,
			'restrictPostsPermanent'	=> $this->restrictPosts == -1,
			'suspendPermanent'	=> $this->suspend == -1,
		);

		if( $authorizedMember === NULL )
		{
			$response['moderatorNotes']	= $this->note_mods;
		}

		return $response;
	}
}