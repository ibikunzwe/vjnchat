<?php
/**
 * @brief		Form helper class for linked screenshots
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Downloads
 * @since		13 Nov 2015
 */

namespace IPS\downloads\Form;

/* To prevent PHP errors (extending class does not exist) revealing path */

use DomainException;
use InvalidArgumentException;
use IPS\Helpers\Form\FormAbstract;
use IPS\Http\Request\Exception;
use IPS\Http\Url;
use IPS\Request;
use IPS\Theme;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Form helper class for linked screenshots
 */
class LinkedScreenshots extends FormAbstract
{
	/**
	 * Validate
	 *
	 * @throws	InvalidArgumentException
	 * @throws	DomainException
	 * @return	TRUE
	 */
	public function validate(): bool
	{
		parent::validate();

		if ( $this->value )
		{
			foreach( $this->formatValue() as $value )
			{
				$value = Url::createFromString( $value );

				try
				{
					$response = $value->request()->get();

					/* Check MIME */
					$contentType = ( isset( $response->httpHeaders['Content-Type'] ) ) ? $response->httpHeaders['Content-Type'] : ( ( isset( $response->httpHeaders['content-type'] ) ) ? $response->httpHeaders['content-type'] : NULL );
					if( $contentType )
					{
						if ( !preg_match( '/^image\/.+$/i', $contentType ) )
						{
							throw new DomainException( 'form_url_bad_mime' );
						}
					}
				}
				catch ( Exception $e )
				{
					throw new DomainException( 'form_url_error' );
				}
			}
		}

		return TRUE;
	}

	/**
	 * Get HTML
	 *
	 * @return	string
	 */
	public function html(): string
	{
		if ( is_array( $this->value ) and !isset( $this->value['values'] ) )
		{
			$value = array( 'values' => $this->value, 'default' => Request::i()->screenshots_primary_screenshot );
		}
		else
		{
			$value = $this->value;
		}
		return Theme::i()->getTemplate( 'submit', 'downloads', 'front' )->linkedScreenshotField( $this->name, $value );
	}
}