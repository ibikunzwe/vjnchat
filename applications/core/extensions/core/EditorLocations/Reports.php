<?php
/**
 * @brief		Editor Extension: Reports
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @since		15 Jul 2013
 */

namespace IPS\core\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content;
use IPS\core\Reports\Report;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Text\Parser;
use LogicException;
use OutOfRangeException;
use function count;
use function is_array;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Reports
 */
class Reports extends EditorLocationsAbstract
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
		try
		{
			return Report::load( $id1 )->canView( $member );
		}
		catch( OutOfRangeException $e )
		{
			return FALSE;
		}
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
		return Report::load( $id1 );
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
		return $this->performRebuild( $offset, $max, array( 'IPS\Text\LegacyParser', 'parseStatic' ) );
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
        $did	= 0;

        foreach( Db::i()->select( '*', 'core_rc_reports', NULL, 'id ASC', array( $offset, $max ) ) as $report )
        {
            $did++;
            
            $update = array();

		    try
		    {
				if( is_array( $callback ) and $callback[1] == 'parseStatic' )
				{
					$update['report'] = $callback( $report['report'], Member::load( $report['report_by'] ), FALSE, 'core_Reports', $report['id'], NULL, NULL );
				}
				else
				{
					$update['report'] = $callback( $report['report'] );
				}
		    }
		    catch( InvalidArgumentException $e )
		    {
		        if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
		        {
		            $update['report'] = preg_replace( "#\[/?([^\]]+?)\]#", '', $report['report'] );
		        }
		        else
		        {
		            throw $e;
		        }
			}

            if( count( $update ) )
            {
                Db::i()->update( 'core_rc_reports', $update, array( 'id=?', $report['id'] ) );
            }

            /* Now rebuild any comments on this report */
            foreach( Db::i()->select( '*', 'core_rc_comments', array( 'rid=?', $report['id'] ) ) as $comment )
            {
            	$updateComment = NULL;

				try
				{
					if( is_array( $callback ) and $callback[1] == 'parseStatic' )
					{
						$updateComment = $callback( $comment['comment'], Member::load( $comment['comment_by'] ), FALSE, 'core_Reports', $comment['id'], NULL, NULL );
					}
					else
					{
						$updateComment = $callback( $comment['comment'] );
					}
				}
				catch( InvalidArgumentException $e )
				{
					if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
					{
					    $updateComment = preg_replace( "#\[/?([^\]]+?)\]#", '', $comment['comment'] );
					}
					else
					{
					    throw $e;
					}
				}

				if( $updateComment )
				{
				    Db::i()->update( 'core_rc_comments', array( 'comment' => $updateComment ), array( 'id=?', $comment['id'] ) );
				}
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
		return Db::i()->select( 'COUNT(*)', 'core_rc_reports' )->first();
	}
}