<?php
/**
 * @brief		Editor Extension: Forums
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Forums
 * @since		08 Jan 2014
 */

namespace IPS\forums\extensions\core\EditorLocations;

/* To prevent PHP errors (extending class does not exist) revealing path */

use InvalidArgumentException;
use IPS\Content;
use IPS\Db;
use IPS\Extensions\EditorLocationsAbstract;
use IPS\forums\Forum;
use IPS\forums\Topic;
use IPS\forums\Topic\ArchivedPost;
use IPS\forums\Topic\Post;
use IPS\Helpers\Form\Editor;
use IPS\Http\Url;
use IPS\Member;
use IPS\Node\Model;
use IPS\Text\Parser;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Editor Extension: Forums
 */
class Forums extends EditorLocationsAbstract
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
	 * Can whatever is posted in this editor be moderated?
	 * If this returns TRUE, we must ensure the content is ran through word, link and image filters
	 *
	 * @param	Member					$member	The member
	 * @param	Editor	$field	The editor field
	 * @return	bool
	 */
	public function canBeModerated( Member $member, Editor $field ): bool
	{
		/* Creating/editing a forum */
		if ( preg_match( '/^forums\-(?:forum|rules|permerror)\-\d+$/', $field->options['autoSaveKey'] ) or preg_match( '/^forums\-new\-(?:forum|rules|permerror)\d*$/', $field->options['autoSaveKey'] ) )
		{
			return FALSE;
		}
		/* Creating/editing a topic/post */
		elseif ( preg_match( '/^(?:newContentItem|contentEdit|editComment|reply)\-forums\/forums\-\d+/', $field->options['autoSaveKey'] ) )
		{
			return TRUE;
		}
		/* Merging multiple posts */
		elseif ( mb_substr( $field->options['autoSaveKey'], 0, 10 ) === 'mod-merge-' )
		{
			return TRUE;
		}
		/* Unknown */
		else
		{
			if ( \IPS\IN_DEV )
			{
				throw new RuntimeException( 'Unknown canBeModerated: ' . $field->options['autoSaveKey'] );
			}

			return parent::canBeModerated( $member, $field );
		}
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
			if ( $id3 )
			{
				return Forum::load( $id1 )->can( 'attachments', $member );
			}
			elseif ( $id2 )
			{
				$topic = Topic::load( $id1 );

				if( $topic->isArchived() )
				{
					$post = ArchivedPost::load( $id2 );
				}
				else
				{
					$post = Post::load( $id2 );
				}
				
				if ( !$post->canView( $member ) )
				{
					return FALSE;
				}
				
				return $viewOnly or $post->container()->can( 'attachments', $member );
			}
			else
			{
				$topic = Topic::load( $id1 );
				
				if ( !$topic->canView( $member ) )
				{
					return FALSE;
				}
				
				return $viewOnly or $topic->container()->can( 'attachments', $member );
			}
		}
		catch ( OutOfRangeException $e )
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
	 * @return	Url|Content|Model|Member|null
	 * @throws	LogicException
	 */
	public function attachmentLookup( int $id1=NULL, int $id2=NULL, string $id3=NULL ): Model|Content|Url|Member|null
	{
		if ( $id3 )
		{
			return Forum::load( $id1 );
		}
		elseif ( $id2 )
		{
			$topic = Topic::load( $id1 );

			if( $topic->isArchived() )
			{
				return ArchivedPost::load( $id2 );
			}
			else
			{
				return Post::load( $id2 );
			}
		}
		else
		{
			return Topic::load( $id1 );
		}
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

		/* Language bits */
		foreach( Db::i()->select( '*', 'core_sys_lang_words', "word_key LIKE 'forums_forum_%_desc' OR word_key LIKE 'forums_forum_%_rules' OR word_key LIKE 'forums_forum_%_permerror'", 'word_id ASC', array( $offset, $max ) ) as $word )
		{
			$did++;
			$rebuilt = FALSE;
			
			try
			{
				if( !empty( $word['word_custom'] ) )
				{
					$rebuilt = $callback( $word['word_custom'] );
				}
			}
			catch( InvalidArgumentException $e )
			{
				if( $callback[1] == 'parseStatic' AND $e->getcode() == 103014 )
				{
					$rebuilt = preg_replace( "#\[/?([^\]]+?)\]#", '', $word['word_custom'] );
				}
				else
				{
					throw $e;
				}
			}

			if( $rebuilt !== FALSE )
			{
				Db::i()->update( 'core_sys_lang_words', array( 'word_custom' => $rebuilt ), 'word_id=' . $word['word_id'] );
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
		return Db::i()->select( 'COUNT(*)', 'core_sys_lang_words', "word_key LIKE 'forums_forum_%_desc' OR word_key LIKE 'forums_forum_%_rules' OR word_key LIKE 'forums_forum_%_permerror'" )->first();
	}
}