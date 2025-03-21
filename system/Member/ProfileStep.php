<?php
/**
 * @brief		Profile Step Model
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		10 Mar 2017
 */

namespace IPS\Member;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use InvalidArgumentException;
use IPS\Application;
use IPS\Data\Store;
use IPS\Db;
use IPS\Extensions\ProfileStepsAbstract;
use IPS\Helpers\Form;
use IPS\Helpers\Form\Radio;
use IPS\Helpers\Form\Translatable;
use IPS\Helpers\Form\YesNo;
use IPS\Lang;
use IPS\Member;
use IPS\Node\Model;
use IPS\Patterns\ActiveRecordIterator;
use IPS\Settings;
use OutOfBoundsException;
use function count;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Profile Step Model
 */
class ProfileStep extends Model
{
	/**
	 * @brief	[ActiveRecord] Multiton Store
	 */
	protected static array $multitons;

	/**
	 * @brief	[ActiveRecord] Multiton Map
	 */
	protected static array $multitonMap	= array();
	
	/**
	 * @brief	[ActiveRecord] Database Table
	 */
	public static ?string $databaseTable = 'core_profile_steps';
		
	/**
	 * @brief	[ActiveRecord] ID Database Column
	 */
	public static string $databaseColumnId = 'id';
	
	/**
	 * @brief	[ActiveRecord] Database Prefix
	 */
	public static string $databasePrefix = 'step_';

	/**
	 * @brief	[Node] Node Title
	 */
	public static string $nodeTitle = 'profile_completion';
	
	/**
	 * @brief	[Node] Sortable
	 */
	public static bool $nodeSortable = TRUE;
	
	/**
	 * @brief	[Node] Positon Column
	 */
	public static ?string $databaseColumnOrder = 'position';

	/**
	 * @brief	[Node] Title prefix.  If specified, will look for a language key with "{$key}_title" as the key
	 */
	public static ?string $titleLangPrefix = 'profile_step_title_';

	/**
	 * [Node] Return the custom badge for each row
	 *
	 * @return	NULL|array		Null for no badge, or an array of badge data (0 => CSS class type, 1 => language string, 2 => optional raw HTML to show instead of language string)
	 */
	protected function get__badge(): ?array
	{
		if( $this->required )
		{
			return array( 0 => 'positive i-float_end', 1 => 'required' );
		}

		return parent::get__badge();
	}

	/**
	 * Get data store
	 *
	 * @return	array
	 * @note	Note that all records are returned, even disabled promotion rules. Enable status needs to be checked in userland code when appropriate.
	 */
	public static function getStore(): array
	{
		if ( !isset( Store::i()->profileSteps ) )
		{
			Store::i()->profileSteps = iterator_to_array( Db::i()->select( '*', 'core_profile_steps', NULL, "step_position ASC" )->setKeyField( 'step_id' ) );
		}
		
		return Store::i()->profileSteps;
	}

	/**
	 * Fetch All Root Nodes
	 *
	 * @param	string|NULL			$permissionCheck	The permission key to check for or NULl to not check permissions
	 * @param	Member|NULL	$member				The member to check permissions for or NULL for the currently logged in member
	 * @param	mixed				$where				Additional WHERE clause
	 * @param	array|NULL			$limit				Limit/offset to use, or NULL for no limit (default)
	 * @return	array
	 */
	public static function roots( ?string $permissionCheck='view', Member $member=NULL, mixed $where=array(), array $limit=NULL ): array
	{
		if ( !count( $where ) )
		{
			$return = array();
			foreach( static::getStore() AS $node )
			{
				$return[ $node['step_id'] ] = static::constructFromData( $node );
			}
			
			return $return;
		}
		else
		{
			return parent::roots( $permissionCheck, $member, $where, $limit );
		}
	}
	
	/**
	 * @brief	[ActiveRecord] Caches
	 * @note	Defined cache keys will be cleared automatically as needed
	 */
	protected array $caches = array( 'profileSteps' );

	/**
	 * @brief	[ActiveRecord] Attempt to load from cache
	 * @note	If this is set to TRUE you MUST define a getStore() method to return the objects from cache
	 */
	protected static bool $loadFromCache = TRUE;

	/**
	 * [ActiveRecord] Delete Record
	 *
	 * @return    void
	 */
	public function delete(): void
	{
		if ( method_exists( $this->extension, 'onDelete' ) )
		{
			$this->extension->onDelete( $this );
		}

		Lang::deleteCustom( 'core', 'profile_step_title_' . $this->id );
		Lang::deleteCustom( 'core', 'profile_step_text_' . $this->id );

		parent::delete();

		Db::i()->delete( 'core_profile_completion', array( 'step_id=?', $this->id ) );
	}
	
	/**
	 * Get the "subcompletion_act" field
	 *
	 * @return array
	 */
	public function get_subcompletion_act(): array
	{
		$return = ! empty( $this->_data['subcompletion_act'] ) ? json_decode( $this->_data['subcompletion_act'], TRUE ) : array();
		if ( ! is_array( $return ) and ! empty( $this->_data['subcompletion_act'] ) )
		{
			$return = array( $this->_data['subcompletion_act'] );
		}
		
		return $return;
	}
	
	/**
	 * Set the "subcompletion_act" field
	 *
	 * @param array|string $value	Value of field
	 * @return	void
	 */
	public function set_subcompletion_act( array|string $value ) : void
	{
		$this->_data['subcompletion_act'] = ( is_array( $value ) ? json_encode( $value ) : $value );
	}
	
	/**
	 * Used sub actions by other items
	 *
	 * @return	array	array( 'key1', 'key2', 'key3' )
	 */
	public function usedSubActions(): array
	{
		$return = array();
		
		foreach( static::loadAll() as $id => $object )
		{
			if ( $object->id !== $this->id and count( $object->subcompletion_act ) )
			{
				foreach( $object->subcompletion_act as $item )
				{
					$return[ $object->completion_act ][] = $item;
				}
			}
		}
		
		return $return;
	}
	
	/**
	 * Return the next step
	 *
	 * @return int|string
	 */
	public function getNextStep(): int|string
	{
		$nextOneIsIt = false;
		$steps = array();
		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $extension )
		{
			if ( is_array( $extension::wizard() ) AND count( $extension::wizard() ) )
			{
				$steps = array_merge( $steps, $extension::wizard() );
			}
		}

		$steps = static::setOrder( $steps, FALSE );
		
		foreach( $steps as $key => $object )
		{
			if ( $nextOneIsIt )
			{
				return $key;
			}
			
			if ( $key === $this->key )
			{
				$nextOneIsIt = true;
			}
		}
		
		/* Still here? */
		return 'profile_done';
	}

	/**
	 * Load All Steps
	 *
	 * @return	array
	 */
	public static function loadAll(): array
	{
		$return = array();

		foreach( static::getStore() AS $id => $data )
		{
			$return[ $id ] = static::constructFromData( $data );
		}
		
		return $return;
	}
	
	/**
	 * @brief	Actions Cache
	 */
	protected static ?array $_actions = NULL;
	
	/**
	 * @brief	Sub actions cache
	 */
	protected static ?array $_subActions = NULL;
	
	/**
	 * Available parent actions to complete steps
	 *
	 * @return	array|null	array( 'key' => 'lang_string' )
	 */
	public static function actions(): ?array
	{
		if ( static::$_actions === NULL )
		{
			static::$_actions = array();
			foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $key => $extension )
			{
				foreach( $extension::actions() AS $action => $lang )
				{
					static::$_actions[ $action ] = $lang;
				}
			}
		}
		
		return static::$_actions;
	}

	/**
	 * Available sub actions to complete steps
	 *
	 * @return array|null array( 'key' => 'lang_string' )
	 */
	public static function subActions(): ?array
	{
		if ( static::$_subActions === NULL )
		{
			static::$_subActions = array();
			foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $key => $extension )
			{
				foreach( $extension::subActions() as $parent => $row )
				{
					foreach( $row as $action => $lang )
					{
						static::$_subActions[ $parent ][ $action ] = $lang;
					}
				}
			}
		}
		
		return static::$_subActions;
	}
	
	
	/**
	 * Can the actions have multiple choices?
	 *
	 * @param string $action		Action key (basic_profile, etc)
	 * @return	boolean|null
	 */
	public static function actionMultipleChoice( string $action ): ?bool
	{
		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $key => $extension )
		{
			foreach( $extension::actions() AS $extensionAction => $lang )
			{
				if ( $action == $extensionAction )
				{
					return $extension::actionMultipleChoice( $action );
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Can be set as required?
	 *
	 * @return	array
	 * @note	This is intended for items which have their own independent settings and dedicated enable pages, such as MFA and Social Login integration
	 */
	public static function canBeRequired(): array
	{
		$return = array();
		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $key => $extension )
		{
			foreach( $extension::canBeRequired() AS $action )
			{
				$return[] = $action;
			}
		}
		
		return $return;
	}

	/**
	 * Return all actions that can be used multiple times
	 *
	 * @return array
	 */
	public static function allowMultiple() : array
	{
		$return = array();
		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) as $key => $extension )
		{
			foreach( $extension::allowMultiple() as $action )
			{
				$return[] = $action;
			}
		}

		return $return;
	}
	
	/**
	 * Get the wizard step key
	 *
	 * @return string
	 */
	public function get_key(): string
	{
		return "profile_step_title_" . $this->id;
	}
	
	/**
	 * Get automated step title in the ACP
	 * @note It needs to fetch the parsed langauge so it does not end up storing the word hashes on save.
	 *
	 * @return string
	 */
	public function get_acpTitle(): string
	{
		if ( empty( $this->subcompletion_act ) )
		{
			return Member::loggedIn()->language()->get( 'complete_profile_' . $this->completion_act );
		}
		
		$extension = $this->extension;
		$subActions = $extension::subActions()[ $this->completion_act ];
		$lang = array();
		
		foreach( $this->subcompletion_act as $item )
		{
			if ( isset( $subActions[ $item ] ) )
			{
				$lang[] = Member::loggedIn()->language()->addToStack( $subActions[ $item ] );
			}
		}
		
		if ( count( $lang ) )
		{
			$result = Member::loggedIn()->language()->get( 'complete_profile_' . $this->completion_act ) . ' (' . Member::loggedIn()->language()->formatList( $lang ) . ')';
			Member::loggedIn()->language()->parseOutputForDisplay( $result );

			return $result;
		}
		else
		{
			return Member::loggedIn()->language()->get( 'complete_profile_' . $this->completion_act );
		}
	}
	
	/**
	 * Get Extension
	 *
	 * @return mixed
	 */
	public function get_extension(): mixed
	{
		if( !isset( $this->_data['extension'] ) )
		{
			$this->_data['extension'] = static::findExtensionFromAction( $this->completion_act );
			$this->save();
		}

		list( $app, $extension ) = explode( '_', $this->_data['extension'] );
		$class = "\\IPS\\{$app}\\extensions\\core\\ProfileSteps\\{$extension}";
		return new $class;
	}
	
	/**
	 * Has a specific step been completed?
	 *
	 * @param	Member|NULL	$member	The member to check, or NULL for currently logged in
	 * @return	bool
	 */
	public function completed( Member $member = NULL ): bool
	{
		$member = $member ?: Member::loggedIn();
		return $this->extension->completed( $this, $member );
	}

	/**
	 * Checks if the member has permission to complete this profile step
	 * Some steps might be skipped for some members
	 *
	 * @param Member|NULL $member
	 * @return bool
	 */
	public function canComplete( Member $member = NULL ) : bool
	{
		$member = $member ?: Member::loggedIn();

		/* @var ProfileStepsAbstract $extension */
		$extension = $this->extension;
		return $extension->canComplete( $member );
	}
	
	/**
	 * @brief	Can add cache
	 */
	protected static mixed $canAdd = NULL;

	/**
	 * Can add new steps
	 *
	 * @return bool
	 */
	public function canAdd(): bool
	{
		/* We can't add children, so just return now */
		if( $this->id )
		{
			return FALSE;
		}

		if ( static::$canAdd === NULL )
		{
			$actions = static::actions();
			$subActions = static::subActions();
			$usedSubActions = $this->usedSubActions();
			
			foreach( $actions as $key => $lang )
			{
				if ( isset( $subActions[ $key ] ) )
				{
					if ( isset( $usedSubActions[ $key ] ) )
					{
						if ( count( $usedSubActions[ $key ] ) == count( $subActions[ $key ] ) )
						{
							/* Nothing left to select, so skip this */
							unset( $actions[ $key ] );
						}
					}
				}
			}
			
			static::$canAdd = (bool) count( $actions );
		}
		
		return static::$canAdd;
	}
	
	/**
	 * Form
	 *
	 * @param	Form	$form	Form
	 * @return	void
	 */
	public function form( Form &$form ) : void
	{
		$form->add( new Translatable( 'profile_step_title', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ) ? "profile_step_title_{$this->id}" : NULL ) ) );
		$form->add( new Translatable( 'profile_step_text', NULL, FALSE, array( 'app' => 'core', 'key' => ( $this->id ) ? "profile_step_text_{$this->id}" : NULL ) ) );
		
		$actions = static::actions();
		$subActions = static::subActions();
		$reusableActions = static::allowMultiple();
		$toggles = array();
		$subActionFormElements = array();
		$usedSubActions = $this->usedSubActions();
		
		foreach( $actions as $key => $lang )
		{
			if ( isset( $subActions[ $key ] ) )
			{
				$disabled = NULL;
				
				if ( isset( $usedSubActions[ $key ] ) and !in_array( $key, $reusableActions ) )
				{
					if ( count( $usedSubActions[ $key ] ) >= count( $subActions[ $key ] ) )
					{
						/* Nothing left to select, so skip this */
						unset( $actions[ $key ] );
						continue;
					}
				
					$disabled = $usedSubActions[ $key ];
				}
				
				$choiceType = static::actionMultipleChoice( $key );

				if( $choiceType === NULL )
				{
					$subActionFormElements[] = new Translatable( 'profile_step_subaction_' . $key, $this->subcompletion_act, TRUE, array( 'app' => 'core', 'editor' => array( 'app' => 'core', 'key' => 'ProfileSteps', 'autoSaveKey' => 'core-step-' . $key . ( $this->id ?: 0 ) )  ), NULL, NULL, NULL, 'profile_step_subaction_' . $key );
				}
				else
				{
					$formClass = $choiceType ? '\IPS\Helpers\Form\CheckboxSet' : '\IPS\Helpers\Form\Select';
					$subActionFormElements[] = new $formClass( 'profile_step_subaction_' . $key, $this->subcompletion_act, TRUE, array( 'options' => $subActions[ $key ], 'disabled' => $disabled ), NULL, NULL, NULL, 'profile_step_subaction_' . $key );
				}

				$toggles[ $key ][] = 'profile_step_subaction_' . $key;
			}
		}

		foreach( static::canBeRequired() AS $action )
		{
			$toggles[ $action ][] = 'step_required';
		}
		
		$form->add( new Radio( 'profile_step_completion_act', $this->completion_act, FALSE, array( 'options' => $actions, 'toggles' => $toggles ) ) );
		
		foreach( $subActionFormElements as $element )
		{
			$form->add( $element );
		}
		
		$form->add( new YesNo( 'profile_step_required', $this->required, FALSE, array(), NULL, NULL, NULL, 'step_required' ) );
		$form->add( new YesNo( 'profile_step_registration', $this->registration, false, array(), null, null, null, 'step_registration' ) );
	}
	
	/**
	 * Format Form Values
	 * @param	array	$values	Values
	 * @return	array
	 */
	public function formatFormValues( array $values ): array
	{
		$return = array();
		
		if ( !$this->id )
		{
			/* Set initial position to the end of the list and save initial data */
			try
			{
				$this->position = Db::i()->select( 'MAX(step_position)', 'core_profile_steps' )->first() + 1;
			}
			catch( Exception $e )
			{
				$this->position = 1;
			}
			
			$this->save();
		}
		
		if ( isset( $values[ 'profile_step_subaction_' . $values['profile_step_completion_act'] ] ) )
		{
			$values['profile_step_subcompletion_act'] = $values[ 'profile_step_subaction_' . $values['profile_step_completion_act'] ];
			unset( $values[ 'profile_step_subaction_' . $values['profile_step_completion_act'] ] );
		}
		
		foreach( $values AS $key => $value )
		{
			$return[ str_replace( 'profile_', '', $key ) ] = $value;
		}

		try
		{
			$ext		= static::findExtensionFromAction( $return['step_completion_act'] );
			$this->extension = $ext;
			$ext		= explode( '_', $ext );
			$class		= "\\IPS\\{$ext[0]}\\extensions\\core\\ProfileSteps\\{$ext[1]}";
			$extension	= new $class;
		}
		catch( OutOfBoundsException $e )
		{
			throw new InvalidArgumentException;
		}
	
		return $return;
	}
	
	/**
	 * Perform actions after saving the form
	 *
	 * @param	array	$values	Values from the form
	 * @return	void
	 */
	public function postSaveForm( array $values ) : void
	{
		$return = array();
		foreach( $values AS $key => $value )
		{
			$return[ str_replace( 'profile_', '', $key ) ] = $value;
		}
		
		if ( array_key_exists( 'step_title', $return ) )
		{
			if ( is_array( $return['step_title'] ) )
			{
				foreach( $return['step_title'] AS $lang => $text )
				{
					if ( empty( $text ) )
					{
						$return['step_title'][ $lang ] = $this->acpTitle;
					}
				}
			}
			else
			{
				$return['step_title'] = array();
				foreach( Lang::languages() AS $lang )
				{
					$return['step_title'][ $lang->_id ] = $this->acpTitle;
				}
			}
		}
		else
		{
			$return['step_title'] = array();
			foreach( Lang::languages() AS $lang )
			{
				$return['step_title'][ $lang->_id ] = $this->acpTitle;
			}
		}
		
		Lang::saveCustom( 'core', "profile_step_title_{$this->id}", $return['step_title'] );
		Lang::saveCustom( 'core', "profile_step_text_{$this->id}", $return['step_text'] );

		/* If we have a profile step that is used on registration, switch registration type to full */
		if( $this->registration )
		{
			Settings::i()->changeValues( [ 'allow_reg' => 'full' ] );
		}
	}
	
	/**
	 * Find extension key from action key
	 *
	 * @param string $key	The action key
	 * @return	string	The extension key
	 * @throws	OutOfBoundsException
	 */
	public static function findExtensionFromAction( string $key ): string
	{
		foreach( Application::allExtensions( 'core', 'ProfileSteps' ) AS $extkey => $extension )
		{
			foreach( $extension::actions() AS $action => $lang )
			{
				if ( $key == $action )
				{
					return $extkey;
				}
			}
		}
		
		throw new OutOfBoundsException;
	}

	/**
	 * Load an instance of the extension
	 *
	 * @param string $key
	 * @return ProfileStepsAbstract|null
	 */
	public static function loadExtensionFromAction( string $key ) : ?ProfileStepsAbstract
	{
		if( $extensionKey = static::findExtensionFromAction( $key ) )
		{
			list( $app, $extension ) = explode( '_', $extensionKey );
			$class = "\\IPS\\{$app}\\extensions\\core\\ProfileSteps\\{$extension}";
			return new $class;
		}

		return null;
	}
	
	/**
	 * Resync
	 * Triggered when something happens externally that may affect these fields (such as a field being deleted, etc)
	 *
	 * @return void
	 */
	 public static function resync() : void
	 {
		 $iterator = new ActiveRecordIterator( Db::i()->select( '*', 'core_profile_steps' ), '\IPS\Member\ProfileStep' );
		
		 foreach( $iterator as $step )
		 {
			 $class = $step->extension;
			 
			 if ( method_exists( $class, 'resync' ) )
			 {
			 	$class->resync( $step );
			 }
		 }
	 }
	 
	 /**
	  * Delete by application
	  * Deletes all steps tied to an application
	  *
	  * @param	Application	$app	The application being deleted
	  * @return void
	  */
	public static function deleteByApplication( Application $app ) : void
	{
		$iterator = new ActiveRecordIterator( Db::i()->select( '*', 'core_profile_steps' ), '\IPS\Member\ProfileStep' );
		
		foreach( $iterator as $step )
		{
			list( $application, $extension ) = explode( '_', $step->_data['extension'] );
			
			if ( $application == $app->directory )
			{
				$step->delete();
			}
		}
	}

	/**
	 * Given an array of wizard steps, reorder them based on AdminCP order
	 *
	 * @param array $steps	Wizard steps
	 * @param bool $countExtra	Include extra steps
	 * @return	array
	 */
	public static function setOrder( array $steps, bool $countExtra = TRUE ): array
	{
		$finalSteps = array();

		$profileSteps = static::loadAll();

		foreach( $profileSteps as $id => $profileStep )
		{
			if( isset( $steps['profile_step_title_' . $id ] ) )
			{
				$finalSteps['profile_step_title_' . $id ] = $steps['profile_step_title_' . $id ];
			}
			if ( $countExtra and method_exists( $profileStep->extension, 'extraStep') )
			{
				$extension = $profileStep->extension;
				if( count( $extension::extraStep() ) and isset( $steps[ $extension::extraStepTitle() ] ) )
				{
					$finalSteps[ $extension::extraStepTitle() ] = $steps[ $extension::extraStepTitle() ];
				}

			}
		}

		return $finalSteps;
	}

	/**
	 * [Node] Does the currently logged in user have permission to copy this node?
	 *
	 * @return	bool
	 */
	public function canCopy(): bool
	{
		return FALSE;
	}
}