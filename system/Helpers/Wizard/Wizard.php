<?php
/**
 * @brief		Wizard Helper
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		25 Jul 2013
 */

namespace IPS\Helpers;

/* To prevent PHP errors (extending class does not exist) revealing path */

use Exception;
use IPS\Http\Url;
use IPS\IPS;
use IPS\Output;
use IPS\Request;
use IPS\Theme;
use Throwable;
use function defined;
use function in_array;
use function is_array;
use function is_null;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Wizard Helper
 *
 * @code
	\IPS\Output::i()->output = new \IPS\Helpers\Wizard(
		array(
			'first_step'	=> function( $data )
			{
				$form = new \IPS\Helpers\Form;
				$form->add( ... );
				
				if ( $values = $form->values() )
				{
					return $values;
				}
				
				return $form;
			},
			'second_step'	=> function ( $data )
			{
				// $data contains the form values from the previous step
			}
		),
		\IPS\Http\Url::internal( 'app=example&module=example&controller=example&do=wizard' )
	);
 * @endcode
 */
class Wizard
{
	/**
	 * @brief	Steps
	 */
	protected array $steps = array();
	
	/**
	 * @brief	Base URL
	 */
	protected Url $baseUrl;
	
	/**
	 * @brief	Show steps?
	 */
	protected bool $showSteps = TRUE;
	
	/**
	 * @brief	Key used for \IPS\Data\Store
	 */
	protected bool $dataKey = TRUE;
	
	/**
	 * @brief	Flag to reset session data when wizard completes
	 */
	public bool $resetWhenDone = FALSE;
	
	/**
	 * @brief	Template
	 */
	public ?array $template = NULL;
	
	/**
	 * Constructor
	 *
	 * @param array $steps			An array of callback functions. Each function should return either a string to output or (if the step is done) an array (which can be blank) of arbitrary data to retain between steps (which will be passed to each callback function). The keys should be langauge keys for the title of the step.
	 * @param Url $baseUrl		The base URL (used when moving between steps)
	 * @param bool $showSteps		Whether or not to show the step bar
	 * @param array|null $initialData	The initial data, if any
	 * @param bool $resetWhenDone	Whether or not to reset the session data when the wizard completes
	 * @param array|string|null $ignoreQueryParams	String or array of URL params to ignore. For example, the base URL may be /submit/ but your userland code adds params /submit/?foo=bar, this changes the baseUrl and creates a new wizard session
	 * @return	void
	 */
	public function __construct(array $steps, Url $baseUrl, bool $showSteps=TRUE, array $initialData=NULL, bool $resetWhenDone=FALSE, array|string $ignoreQueryParams=NULL )
	{
		$this->steps = $steps;
		$this->baseUrl = ( $ignoreQueryParams != NULL ) ? $baseUrl->stripQueryString( $ignoreQueryParams ) : $baseUrl;
		$this->showSteps = $showSteps;
		$this->resetWhenDone = $resetWhenDone;
		$this->template = array( Theme::i()->getTemplate( 'global', 'core', 'global' ), 'wizard' );
		
		if ( isset( Request::i()->_new ) )
		{
			unset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] );
            unset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] );
            
            if ( !is_null( $initialData ) )
            {
	            $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] = $initialData;
            }
			
			if ( !Request::i()->isAjax() )
			{
				Output::i()->redirect( $baseUrl );
			}
		}
	}

	/**
	 * Render
	 *
	 * @return	string
	 */
	public function __toString()
	{
		/* Don't cache for a short while to ensure sessions work */
		Output::setCacheTime( false );
		
		try
		{
			$stepKeys = array_keys( $this->steps );

			/* Get our data */
			$data = array();
			if ( isset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] ) )
			{
				$data = $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ];
			}

			/* What step are we on? */
			$activeStep = NULL;
			if ( isset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] ) )
			{
				$activeStep = $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ];

				if ( isset( Request::i()->_step ) and in_array( Request::i()->_step, $stepKeys ) )
				{
					foreach ( $stepKeys as $key )
					{
						if ( $key == $activeStep )
						{
							break;
						}
						elseif ( $key == Request::i()->_step )
						{
							$activeStep = $key;
							break;
						}
					}
				}
			}
			else
			{
				foreach ( $stepKeys as $key )
				{
					$activeStep = $key;
					break;
				}
			}

			if ( isset( Request::i()->_moveToStep ) and in_array( Request::i()->_moveToStep, $stepKeys ) )
			{
				foreach ( $stepKeys as $key )
				{
					if ( $key == Request::i()->_moveToStep )
					{
						$activeStep = $key;
						break;
					}
				}
			}

			/* Get it's output */
			$activeStepFunction = $this->steps[ $activeStep ];
			$output = $activeStepFunction( $data );
			while ( is_array( $output ) )
			{
				$data = array_merge( $data, $output );

				$nextStep = NULL;
				$foundJustDone = FALSE;

				foreach ( $stepKeys as $key )
				{
					if ( $foundJustDone )
					{
						$activeStep = $key;
						break;
					}
					elseif ( $key == $activeStep )
					{
						$foundJustDone = TRUE;
					}
				}

				/* Update the Wizard session data */
				$_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] = $activeStep;
				$_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] = $data;
				
				$copyOfStepKeys = $stepKeys;
				$lastStep = array_pop( $copyOfStepKeys );
				
				/* Last step? */
				if ( $this->resetWhenDone and ( $lastStep === $activeStep ) )
				{
					/* Wipe session data */
					unset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-step' ] );
					unset( $_SESSION[ 'wizard-' . md5( $this->baseUrl ) . '-data' ] );
				}
			
				$activeStepFunction = $this->steps[ $activeStep ];
				$output = $activeStepFunction( $data );
			}
			
			/* Display */
			$htmlTemplate = $this->template;
			return $htmlTemplate( $stepKeys, $activeStep, $output, $this->baseUrl, $this->showSteps );
		}
		catch ( Exception | Throwable $e )
		{
			IPS::exceptionHandler( $e );
		}
		return '';
	}
}