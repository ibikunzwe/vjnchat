<?php
/**
 * @brief		Editor Extension: Signatures
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		07 Jun 2013
 */

namespace IPS\core\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Settings;
use IPS\Text\LegacyParser;
use IPS\Text\Parser;
use LogicException;
use function defined;
use function is_array;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Signatures
 */
class Signatures extends EditorLocationsAbstract
{
	/**
	 * Can we use attachments in this editor?
	 *
	 * @param	Member					$member	The member
	 * @param	Editor	$field	The editor field
	 * @return	bool|null	NULL will cause the default value (based on the member's permissions) to be used, and is recommended in most cases. A boolean value will override that.
	 */
	public function canAttach( Member $member, Editor $field ): ?bool
	{
		return NULL;
	}

	/**
	 * Permission check for attachments
	 *
	 * @param	Member	$member		The member
	 * @param	int|null	$id1		Primary ID
	 * @param	int|null	$id2		Secondary ID
	 * @param	string|null	$id3		Arbitrary data
	 * @param	array		$attachment	The attachment data
	 * @param	bool		$viewOnly	If true, just check if the user can see the attachment rather than download it
	 * @return	bool
	 */
	public function attachmentPermissionCheck( Member $member, ?int $id1, ?int $id2, ?string $id3, array $attachment, bool $viewOnly=FALSE ): bool
	{
		return (bool) Settings::i()->signatures_enabled;
	}
	
	/**
	 * Attachment lookup
	 *
	 * @param	int|null	$id1	Primary ID
	 * @param	int|null	$id2	Secondary ID
	 * @param	string|null	$id3	Arbitrary data
	 * @return    Content|Member|Model|Url|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( int $id1=NULL, int $id2=NULL, string $id3=NULL ): Model|Content|Url|Member|null
	{
		return Member::load( $id1 );
	}

	/**
	 * Rebuild content post-upgrade
	 *
	 * @param	int|null	$offset	Offset to start from
	 * @param	int|null	$max	Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildContent( ?int $offset, ?int $max ): int
	{
		return $this->performRebuild( $offset, $max, [ '\IPS\Text\LegacyParser', 'parseStatic' ] );
	}

	/**
	 * Rebuild content to add or remove image proxy
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @param	bool			$proxyUrl	Use the cached image URL instead of the original URL
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildImageProxy( ?int $offset, ?int $max, bool $proxyUrl = FALSE ): int
	{
		$callback = function( $value ) use ( $proxyUrl ) {
			return Parser::removeImageProxy( $value, $proxyUrl );
		};
		return $this->performRebuild( $offset, $max, $callback );
	}

	/**
	 * Rebuild content to add or remove lazy loading
	 *
	 * @param	int|null		$offset		Offset to start from
	 * @param	int|null		$max		Maximum to parse
	 * @return	int			Number completed
	 * @note	This method is optional and will only be called if it exists
	 */
	public function rebuildLazyLoad( ?int $offset, ?int $max ): int
	{
		return $this->performRebuild( $offset, $max, [ 'IPS\Text\Parser', 'parseLazyLoad' ] );
	}

	/**
	 * Perform rebuild - abstracted as the call for rebuildContent() and rebuildAttachmentImages() is nearly identical
	 *
	 * @param	int|null	$offset		Offset to start from
	 * @param	int|null	$max		Maximum to parse
	 * @param	callable	$callback	Method to call to rebuild content
	 * @return	int			Number completed
	 */
	protected function performRebuild( ?int $offset, ?int $max, callable $callback ): int
	{
		$did	 = 0;
		$rebuilt = FALSE;
		
		foreach( Db::i()->select( '*', 'core_members', "signature IS NOT NULL AND signature != ''", 'member_id ASC', array( $offset, $max ) ) as $member )
		{
			$did++;

			/* Update */
			try
			{
				if( is_array( $callback ) and $callback[1] == 'parseStatic' )
				{
					$rebuilt = LegacyParser::parseStatic( $member['signature'], Member::load( $member['member_id'] ), FALSE, 'core_Signatures', $member['member_id'] );
				}
				else
				{
					$rebuilt = $callback( $member['signature'] );
				}
			}
			catch( InvalidArgumentException $e )
			{
				if( $callback == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$rebuilt	= preg_replace( "#\[/?([^\]]+?)\]#", '', $member['signature'] );
				}
				else
				{
					throw $e;
				}
			}

			if( $rebuilt !== FALSE )
			{
				Db::i()->update( 'core_members', array( 'signature' => $rebuilt ), array( 'member_id=?', $member['member_id'] ) );
			}
		}
		
		return $did;
	}

	/**
	 * Total content count to be used in progress indicator
	 *
	 * @return	int			Total Count
	 */
	public function contentCount(): int
	{
		return Db::i()->select( 'COUNT(*) as count', 'core_members', "signature IS NOT NULL AND signature != ''" )->first();
	}
}