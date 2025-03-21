<?php
namespace IPS\Theme;
class class_calendar_front_view extends \IPS\Theme\Template
{	function attendees( $event ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="ipsPrint">
	<h1>
IPSCONTENT;

$return .= \IPS\Settings::i()->board_name;
$return .= <<<IPSCONTENT
</h1>
	<h2>
IPSCONTENT;

$sprintf = array($event->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</h2>
	<h4 class='date'>
		
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $event->_end_date ):
$return .= <<<IPSCONTENT
 - 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->dayName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->monthName, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->year, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</h4>
	<div></div>
	<div></div>

	<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rsvp_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
	<ul>
		
IPSCONTENT;

if ( \count( $event->attendees( \IPS\calendar\Event::RSVP_YES ) )  ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $event->attendees( \IPS\calendar\Event::RSVP_YES ) as $attendee  ):
$return .= <<<IPSCONTENT

				<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<li><em>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rsvps_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</em></li>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>

	
IPSCONTENT;

if ( \count( $event->attendees( \IPS\calendar\Event::RSVP_MAYBE ) )  ):
$return .= <<<IPSCONTENT

		<div></div>
		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_maybe_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul>
			
IPSCONTENT;

foreach ( $event->attendees( \IPS\calendar\Event::RSVP_MAYBE ) as $attendee  ):
$return .= <<<IPSCONTENT

				<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( \count( $event->attendees( \IPS\calendar\Event::RSVP_NO ) )  ):
$return .= <<<IPSCONTENT

		<div></div>
		<h3>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_notgoing_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
		<ul>
			
IPSCONTENT;

foreach ( $event->attendees( \IPS\calendar\Event::RSVP_NO ) as $attendee  ):
$return .= <<<IPSCONTENT

				<li>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function comments( $event ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='' data-controller='core.front.core.commentFeed, core.front.core.ignoredComments' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $event->isLastPage() ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->feedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='comments'>

	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->featuredComments( $event->featuredComments(), $event->url()->setQueryString('tab', 'comments')->setQueryString('recommended', 'comments') );
$return .= <<<IPSCONTENT

	<div class="ipsButtonBar ipsButtonBar--top">
		
IPSCONTENT;

if ( $event->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar__pagination">
				{$event->commentPagination( array( 'tab' ) )}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar__end">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $event, '#comments' );
$return .= <<<IPSCONTENT

		</div>
	</div>

	<div data-role='commentFeed' data-controller='core.front.core.moderation'>
		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->csrf()->setQueryString( 'do', 'multimodComment' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
		
IPSCONTENT;

if ( ( $comments = $event->comments( NULL, NULL, 'date', 'asc', NULL, NULL, NULL, NULL, FALSE, isset( \IPS\Widget\Request::i()->showDeleted ) ) and \count( $comments ) ) ):
$return .= <<<IPSCONTENT


				
IPSCONTENT;

$commentCount=0; $timeLastRead = $event->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $comment->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $commentCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$commentCount++;
$return .= <<<IPSCONTENT

					{$comment->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			<p class='ipsEmptyMessage' data-role='noComments'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_comments', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $event );
$return .= <<<IPSCONTENT

        </form>
	</div>
	
IPSCONTENT;

if ( $event->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

		<hr class='ipsHr'>
		{$event->commentPagination( array( 'tab' ) )}
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $event->commentForm() || $event->locked() || \IPS\Member::loggedIn()->restrict_post || \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] || !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

		<div data-role='replyArea' class='ipsComposeAreaWrapper' id='replyForm'>
			
IPSCONTENT;

if ( $event->commentForm() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $event->locked() ):
$return .= <<<IPSCONTENT

					<p class='ipsComposeArea_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_locked_can_comment', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				{$event->commentForm()}
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $event->locked() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'event_locked_cannot_comment' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->restrict_post ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

elseif ( !\IPS\Member::loggedIn()->checkPostsPerDay() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'member_exceeded_posts_per_day' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function coverPhotoOverlay( $event ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$sameDay = FALSE;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( $event->_end_date ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$sameDay = !( ($event->_start_date->mday != $event->_end_date->mday) or ($event->_start_date->mon != $event->_end_date->mon) or ($event->_start_date->year != $event->_end_date->year) );
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="ipsPageHeader__row">
	<div class="ipsPageHeader__primary">
		
IPSCONTENT;

if ( $event->_happening ):
$return .= <<<IPSCONTENT

			<div><span class="ipsBadge i-margin-bottom_1">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_happening, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='ipsPageHeader__title'>
			<h1>
				
IPSCONTENT;

if ( $event->canEdit() ):
$return .= <<<IPSCONTENT

					<span data-controller="core.front.core.moderation">
						<span data-role="editableTitle" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'click_hold_edit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</h1>
			<div class='ipsBadges'>
				
IPSCONTENT;

foreach ( $event->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</div>
		</div>
		
IPSCONTENT;

if ( $event->container()->allow_reviews ):
$return .= <<<IPSCONTENT

			<div class="ipsPageHeader__rating">
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'front' )->rating( 'large', $event->averageReviewRating(), \IPS\Settings::i()->reviews_rating_out_of, $event->memberReviewRating() );
$return .= <<<IPSCONTENT
 <span class='i-color_soft'>(
IPSCONTENT;

$pluralize = array( $event->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'num_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
)</span></div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( \count( $event->tags() ) OR ( $event->canEdit() AND $event::canTag( NULL, $event->container() ) ) ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->tagsWithPrefix( $event->tags(), $event->prefix(), FALSE, FALSE, ( $event->canEdit() AND ( \count( $event->tags() ) OR $event::canTag( NULL, $event->container() ) ) ) ? $event->url() : NULL );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>	
	<div class='ipsCoverPhoto__buttons'>
		<div class='ipsButtons'>
			
IPSCONTENT;

if ( \count( $event->shareLinks() ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $event );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->canRemind() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->reminder( $event, $event->getReminder() );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $event );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'calendar', 'event', $event->id, $event->followersCount() );
$return .= <<<IPSCONTENT

		</div>
	</div>
</div>
<div class="ipsPageHeader__row ipsPageHeader__row--footer">
	<div class="ipsPageHeader__primary">
		<div class="ipsPhotoPanel ipsPhotoPanel--inline">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $event->author(), 'tiny' );
$return .= <<<IPSCONTENT

			<div class="ipsPhotoPanel__text">
				<p class="ipsPhotoPanel__primary">
					
IPSCONTENT;

$htmlsprintf = array($event->author()->link( $event->warningRef(), NULL, $event->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_created_by', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $event->isAnonymous() and \IPS\Member::loggedIn()->modPermission('can_view_anonymous_posters') ):
$return .= <<<IPSCONTENT

						<a data-ipsHover data-ipsHover-width="370" data-ipsHover-onClick href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( 'reveal' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"><span class="cAuthorPane_badge cAuthorPane_badge_small cAuthorPane_badge--anon" data-ipsTooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'post_anonymously_reveal', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"></span></a>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</p>
			</div>
		</div>
	</div>
	<div>
		<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='cEvents_event cEvents_eventSmall cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function eventBlock( $event, $date, $truncate=FALSE, $map=array( 300, 200 ), $revertToFirst=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $event->coverPhoto();
$return .= <<<IPSCONTENT

<li class="ipsData__item  
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->ui('css'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-eventID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( 'getPrefComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	<div class="ipsData__image">
		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( 'getPrefComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class='ipsCoverPhoto' data-controller='core.global.core.coverPhoto' data-coverOffset='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' aria-hidden="true" tabindex="-1">
			
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

				<div class='ipsCoverPhoto__container'>
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto__image' alt='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' loading='lazy'>
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</a>
	</div>
	<div class="ipsData__content">
		<div class="ipsData__main">
			<div class="ipsPhotoPanel i-margin-bottom_2">
				<div class='ipsUserPhoto ipsUserPhoto--mini'>
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
				</div>
				<div class="ipsPhotoPanel__text">
					<p class="i-color_primary i-font-weight_500 i-font-size_-2 i-text-transform_uppercase i-margin-bottom_1">
						
IPSCONTENT;

$startDate = $event->nextOccurrence( $date, 'startDate' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $revertToFirst AND $startDate AND $startDate->mon > $date->mon ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$startDate = $event->nextOccurrence( \IPS\calendar\Date::getDate( $date->year, $date->mon, 1 ), 'startDate' );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $startDate ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$nextEndDate = $event->nextOccurrence( $startDate ?: $date, 'endDate' );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$sameDay = ( $nextEndDate AND $startDate->calendarDate() == $nextEndDate->calendarDate() );
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $startDate->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $event->_end_date AND $event->nextOccurrence( $startDate ?: $date, 'endDate' )  ):
$return .= <<<IPSCONTENT

								<span class="i-opacity_4"><i class="fa-solid fa-arrow-right-long" style="margin-inline:.5em;"></i></span>
								
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $startDate ?: $date, 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $startDate ?: $date, 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $event->_end_date AND $event->lastOccurrence( 'endDate' )  ):
$return .= <<<IPSCONTENT

								<span class="i-opacity_4"><i class="fa-solid fa-arrow-right-long" style="margin-inline:.5em;"></i></span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</p>
					<h2 class='ipsData__title'>
						<div class="ipsBadges">
							
IPSCONTENT;

if ( $event->hidden() === 1 ):
$return .= <<<IPSCONTENT
<span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					</h2>
				</div>
			</div>
			<div>
				<div class="ipsColumns">
					<div class="ipsColumns__primary">
						
IPSCONTENT;

if ( $event->recurring ):
$return .= <<<IPSCONTENT
<p class='i-color_soft i-margin-bottom_2'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_recurring_text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class='ipsRichText 
IPSCONTENT;

if ( ( \IPS\Widget\Request::i()->isAjax() or $truncate ) && $event->content ):
$return .= <<<IPSCONTENT
 ipsTruncate_3
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

if ( $event->content ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() or $truncate ):
$return .= <<<IPSCONTENT

									{$event->truncated()}
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									{$event->content}
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<p class='ipsRichText i-margin-block_2'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_details', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></p>
						</div>
						
IPSCONTENT;

if ( $event->rsvp  ):
$return .= <<<IPSCONTENT

							<h4 class="i-font-weight_500 i-margin-top_3">
IPSCONTENT;

$pluralize = array( $event->attendeeCount( \IPS\calendar\Event::RSVP_YES ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rsvp_attendees_list', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</h4>
							
IPSCONTENT;

if ( \count( $event->attendees( \IPS\calendar\Event::RSVP_YES, 5 ) ) ):
$return .= <<<IPSCONTENT

								<ul class='ipsCaterpillar i-margin-top_2'>
									
IPSCONTENT;

foreach ( $event->attendees( \IPS\calendar\Event::RSVP_YES, 5 ) as $attendee ):
$return .= <<<IPSCONTENT

										<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $attendee, 'tiny' );
$return .= <<<IPSCONTENT
</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( $map !== FALSE AND $event->map( $map[0], $map[1] ) ):
$return .= <<<IPSCONTENT

						<div class='ipsColumns__secondary i-basis_300 cCalendarBlock_map'>
							{$event->map( $map[0], $map[1] )}
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="i-flex i-flex-wrap_wrap i-gap_2 i-align-items_center i-margin-top_3">
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='cEvents_event cEvents_eventSmall cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 i-margin-end_auto'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					<div class="i-flex i-flex-wrap_wrap i-gap_3 i-align-items_center">
						
IPSCONTENT;

if ( $event->container()->allow_comments ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->setQueryString( 'tab', 'comments' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="i-color_soft">
IPSCONTENT;

$pluralize = array( $event->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_comment_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $event->container()->allow_reviews ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->setQueryString( 'tab', 'reviews' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="i-color_soft">
IPSCONTENT;

$pluralize = array( $event->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_review_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		</div>
	</div>
    
IPSCONTENT;

if ( \IPS\Settings::i()->core_datalayer_enabled ):
$return .= <<<IPSCONTENT

    <script>
        $('body').trigger( 'ipsDataLayer', {
            _key: 'content_view',
            _properties: {
                
IPSCONTENT;

foreach ( $event->getDataLayerProperties() as $key => $value ):
$return .= <<<IPSCONTENT

                
IPSCONTENT;

$return .= json_encode( $value );
$return .= <<<IPSCONTENT
,
                
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

                'hovercard',
            }
        } )
    </script>
    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</li>
IPSCONTENT;

		return $return;
}

	function eventBlocks( $events ) {
		$return = '';
		$return .= <<<IPSCONTENT


<ul class="ipsGrid ipsGrid--auto-fit i-basis_340 i-gap_4">
	
IPSCONTENT;

foreach ( $events as $event ):
$return .= <<<IPSCONTENT

		<li class='i-flex i-gap_3'>
			<div class='i-basis_50 i-flex_00'>
				
IPSCONTENT;

$nextOccurrence = $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: $event->lastOccurrence();
$return .= <<<IPSCONTENT

				<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
					<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
					<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $nextOccurrence->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
				</time>
			</div>
			<div class='i-flex_11'>
				<div class='ipsTitle ipsTitle--h6'><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($event->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_event', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></div>
				<span class='i-color_soft i-font-size_-1'>
IPSCONTENT;

$sprintf = array($event->author()->name); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_nodate', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</span>
			</div>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function eventHovercard( $event, $date ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--grid ipsData--eventHovercard">
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->eventBlock( $event, $date, true );
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function eventSidebar( $event, $attendees, $tabId='', $address=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class="ipsBlockSpacer">
	
IPSCONTENT;

if ( $address || $event->map( 500, 500 ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "mapWrapper:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="mapWrapper" class="ipsBox ipsPull">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "mapWrapper:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->map( 500, 500 ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "map:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="map" class="cEvents__sidebarMap">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "map:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					{$event->map( 500, 500 )}
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "map:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "map:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->venue || $address ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venueWrapper:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="venueWrapper" class="i-padding_3">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venueWrapper:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $event->venue ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venue:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="venue" class="">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venue:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

							<h3 class="ipsMinorTitle">
								<strong>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_venue_name', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
							</h3>
							
IPSCONTENT;

$val = "calendar_venue_{$event->venue()->id}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'escape' => TRUE ) );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venue:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venue:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $address ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "address:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="address">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "address:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
{$address}
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "address:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "address:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venueWrapper:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "venueWrapper:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "mapWrapper:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "mapWrapper:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $event->online and $event->url ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( ( ( $event->rsvp and isset( $attendees[1][ \IPS\Member::loggedIn()->member_id ] ) ) or !$event->rsvp ) and !$event->hasPassed()  ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvped:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvped" class="ipsBox ipsPull i-padding_3">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvped:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedEventUrl:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<a data-ips-hook="rsvpedEventUrl" href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" class="ipsButton ipsButton--primary ipsButton--wide i-margin-bottom_block">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedEventUrl:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'open_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedEventUrl:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedEventUrl:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->online_type ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedOnline:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvpedOnline" class="cEvents__sidebarOnlineLogo">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedOnline:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					<span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'powered_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
					
IPSCONTENT;

if ( $event->online_type === "zoom" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 1000 224"><path d="M814.1856 67.3887c3.8225 6.595 5.073 14.0983 5.486 22.5337l.5427 11.2433v78.62l.5544 11.2551c1.109 18.3809 14.6646 31.9719 33.187 33.128l11.1961.5546V101.1657l.5545-11.2433c.4601-8.341 1.699-15.986 5.5804-22.628a44.9022 44.9022 0 0 1 77.747.1415c3.8225 6.595 5.0141 14.2399 5.4742 22.4865l.5545 11.2079v78.6555l.5545 11.255c1.1562 18.4753 14.6056 32.0663 33.187 33.128l11.1961.5546V89.9224A89.8988 89.8988 0 0 0 910.1366.0236a89.6628 89.6628 0 0 0-67.424 30.45A89.7808 89.7808 0 0 0 775.2884.0118c-18.664 0-35.9831 5.663-50.3292 15.4433C716.2053 5.6865 696.6211.0118 685.3779.0118v224.7116l11.2432-.5545c18.8056-1.2388 32.3966-14.464 33.128-33.128l.6018-11.2551v-78.6202l.5545-11.2432c.4719-8.4826 1.6516-15.9387 5.486-22.5809a45.0202 45.0202 0 0 1 38.897-22.392 44.9494 44.9494 0 0 1 38.8972 22.4392zm-769.248 156.792 11.2432.5427h168.5307l-.5545-11.2079c-1.5219-18.4752-14.6056-31.9719-33.1398-33.1752l-11.2432-.5545H78.6673l134.801-134.8482-.5545-11.196c-.873-18.664-14.5112-32.1489-33.1398-33.1753L168.5307.059 0 .0118l.5545 11.2433c1.4747 18.2983 14.7472 32.078 33.128 33.1398l11.255.5545h101.1067L11.2432 179.7976l.5545 11.2432c1.109 18.5225 14.4759 31.9365 33.1399 33.128zM641.266 32.9039a112.3499 112.3499 0 0 1 0 158.9038 112.4325 112.4325 0 0 1-158.9391 0c-43.8758-43.8758-43.8758-115.028 0-158.9038A112.2909 112.2909 0 0 1 561.7258 0a112.3735 112.3735 0 0 1 79.5403 32.9157Zm-31.7949 31.8185a67.4477 67.4477 0 0 1 0 95.3494 67.4477 67.4477 0 0 1-95.3493 0 67.4477 67.4477 0 0 1 0-95.3494 67.4477 67.4477 0 0 1 95.3493 0zM325.9126 0a112.2909 112.2909 0 0 1 79.3987 32.9157c43.8876 43.864 43.8876 115.028 0 158.892a112.4325 112.4325 0 0 1-158.9391 0c-43.8758-43.8758-43.8758-115.028 0-158.9038A112.2909 112.2909 0 0 1 325.771 0Zm47.6038 64.6988a67.4477 67.4477 0 0 1 0 95.3612 67.4477 67.4477 0 0 1-95.3493 0 67.4477 67.4477 0 0 1 0-95.3494 67.4477 67.4477 0 0 1 95.3493 0z" fill="#2d8cff" fill-rule="evenodd"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "eventbrite" ):
$return .= <<<IPSCONTENT

						<svg viewbox="0 0 200 36" xmlns="http://www.w3.org/2000/svg"><path d="M186.292 17.513a6.657 6.657 0 0 1 6.878 2.584l-11.905 2.693c.411-2.52 2.333-4.668 5.027-5.277zm6.945 9.91a6.57 6.57 0 0 1-3.98 2.679c-2.711.614-5.417-.51-6.907-2.626l11.941-2.702 1.945-.44 3.72-.841a11.77 11.77 0 0 0-.31-2.372c-1.514-6.426-8.056-10.432-14.612-8.949-6.556 1.484-10.644 7.896-9.13 14.321 1.513 6.426 8.055 10.433 14.611 8.95 3.863-.875 6.868-3.46 8.376-6.751l-5.654-1.269zm-28.102 7.695V18.082h-3.677v-5.804h3.677V4.289h6.244v7.989h4.69v5.804h-4.69v17.036h-6.244zm-11.928 0h6.03v-22.84h-6.03v22.84zm-.784-30.853c0-2.114 1.667-3.7 3.824-3.7s3.775 1.586 3.775 3.7c0 2.115-1.618 3.748-3.775 3.748s-3.824-1.633-3.824-3.748zm-1.315 8.077c-3.083.16-4.901.633-6.75 1.973v-2.037h-6.027v22.84h6.026v-11.2c0-3.524.86-5.529 6.751-5.726v-5.85zm-33.601 11.715c.15 3.333 3.051 6.128 6.602 6.128 3.602 0 6.553-2.942 6.553-6.422 0-3.432-2.951-6.373-6.553-6.373-3.55 0-6.452 2.843-6.602 6.128v.539zm-5.88 11.061V1.38l6.03-1.364v13.962c1.863-1.49 4.07-2.115 6.472-2.115 6.864 0 12.355 5.286 12.355 11.918 0 6.583-5.49 11.965-12.355 11.965-2.402 0-4.609-.624-6.472-2.114v1.487h-6.03v-.001zm-12.835 0V17.965h-3.677v-5.687h3.677V4.283l6.244-1.413v9.408h4.69v5.687h-4.69v17.153h-6.244zm-11.05 0V22.915c0-4.421-2.403-5.382-4.806-5.382-2.402 0-4.804.913-4.804 5.286v12.299h-6.03v-22.84h6.03v1.699c1.323-.961 2.941-2.115 6.129-2.115 5.098 0 9.511 2.932 9.511 10.092v13.164h-6.03zM56.831 17.513c2.694-.61 5.382.495 6.878 2.584L51.805 22.79c.41-2.52 2.333-4.668 5.026-5.277zm6.945 9.91a6.57 6.57 0 0 1-3.98 2.679 6.656 6.656 0 0 1-6.907-2.626l11.942-2.702 1.945-.44 3.719-.841a11.77 11.77 0 0 0-.31-2.372c-1.514-6.426-8.056-10.432-14.612-8.949-6.556 1.484-10.644 7.896-9.13 14.321 1.514 6.426 8.055 10.433 14.612 8.95 3.863-.875 6.868-3.46 8.375-6.751l-5.654-1.269zm-31.538 7.695-9.365-22.84h6.57l5.933 15.49 5.981-15.49h6.57l-9.364 22.84h-6.325zM11.05 17.507a6.658 6.658 0 0 1 6.879 2.584L6.024 22.785c.41-2.52 2.333-4.668 5.026-5.278zm6.945 9.91a6.57 6.57 0 0 1-3.98 2.68c-2.71.613-5.416-.51-6.907-2.626l11.942-2.702 1.945-.44 3.719-.842a11.782 11.782 0 0 0-.31-2.371c-1.514-6.426-8.055-10.433-14.612-8.95C3.236 13.65-.85 20.063.662 26.489c1.514 6.426 8.056 10.432 14.612 8.949 3.863-.874 6.868-3.46 8.376-6.75l-5.655-1.27v-.001z" fill="#F05537"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "on24" ):
$return .= <<<IPSCONTENT

						<svg viewbox="-0.0374 -5.1555 205.0374 63.5155" xmlns="http://www.w3.org/2000/svg"><path d="m122.92 12.87-.76-.55c5.08-7.59 10.41-12 20.34-12 10.15 0 17.51 6.78 17.51 16v.17c0 8.24-4.35 13.31-14.28 21.94l-15.1 13.41h29.94v5.83h-35.23a4.21 4.21 0 0 1-4.21-4.21v-.73L141.6 34.6c8.63-7.76 11.79-12.11 11.79-17.75C153.4 10.21 148.16 6 142 6c-5 0-8.65 2-12.15 5.9l-.71.76a4.21 4.21 0 0 1-5.95.35z" fill="#232323"></path><path d="M194.25 57.61A4.21 4.21 0 0 1 190 53.4v-9.21h-26.83a4.21 4.21 0 0 1-4-3l-.46-1.68 32-38.83h5.66v38.18H205v5.33h-8.7v13.42zm-4.17-18.75V9.73l-23.74 29.13z" fill="#232323"></path><path d="M76.67 18.72v38.89h-6.38a3.35 3.35 0 0 1-3.68-3.69V.86h7.23a4.24 4.24 0 0 1 3.25 1.5l27.34 36.25V.86l6.4.05a3.35 3.35 0 0 1 3.68 3.68v53h-6.28a4.25 4.25 0 0 1-3.32-1.6zM50.22 6.26l.07.11a21.59 21.59 0 0 1 4 15.63C53 33.17 43.91 41.21 34.06 40c-7.83-1-13.78-7.52-15.21-15.75-3.12 7.25-.85 15.32 5.28 20.59a15.36 15.36 0 0 0 6.15 3.45H29.2a20.7 20.7 0 1 1 18.42-32l.13.14C45.09 6.94 37.93 0 26.09 0a22.37 22.37 0 0 0-10.82 2.77A29.63 29.63 0 0 0 0 28.62C0 45 13.56 58.36 30.27 58.36S60.55 45 60.55 28.62A29.42 29.42 0 0 0 50.26 6.29z" fill="#3040e8"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "facebook" ):
$return .= <<<IPSCONTENT

						<svg viewbox="0 0 62.488 12.094" xmlns="http://www.w3.org/2000/svg"><g><path d="M4.8891 1.8945c-.7357 0-.9484.3263-.9484 1.046v1.194h1.9624l-.1967 1.9287H3.9415v5.8536h-2.355V6.0632H0V4.1344h1.5865v-1.161C1.5865 1.0276 2.371 0 4.5598 0c.4586-.001.917.0262 1.3723.0816v1.8152ZM6.0663 7.7801c0-2.1743 1.03-3.8119 3.1882-3.8119 1.1771 0 1.896.6053 2.2399 1.357V4.1345h2.2559v7.7824h-2.2559v-1.1771c-.327.7524-1.0628 1.341-2.2399 1.341-2.1583 0-3.1882-1.6353-3.1882-3.8119Zm2.3542.5238c0 1.1603.4246 1.9288 1.5202 1.9288.9651 0 1.4554-.703 1.4554-1.8145v-.7814c0-1.1116-.4903-1.8145-1.4554-1.8145-1.0956 0-1.5202.7685-1.5202 1.9288ZM18.6554 3.9705c.9149 0 1.7817.1967 2.256.523l-.523 1.668a3.7043 3.7043 0 0 0-1.5698-.3598c-1.2755 0-1.8297.7357-1.8297 1.9952v.4574c0 1.2595.5557 1.9952 1.8297 1.9952a3.7055 3.7055 0 0 0 1.5697-.3599l.523 1.6673c-.4742.3271-1.3402.5238-2.2559.5238-2.7636 0-4.0223-1.4882-4.0223-3.8752v-.3598c0-2.387 1.2587-3.8752 4.0223-3.8752ZM21.2216 8.323v-.6862c0-2.2071 1.2587-3.6594 3.8264-3.6594 2.4198 0 3.4825 1.4714 3.4825 3.6297v1.2426h-4.9554c.0495 1.0628.5237 1.537 1.8297 1.537.8836 0 1.8152-.18 2.5021-.4742l.4308 1.6124c-.6214.3278-1.8968.5726-3.0244.5726-2.9801-.0008-4.0917-1.4874-4.0917-3.7746Zm2.3542-1.014h2.8452v-.196c0-.85-.343-1.5247-1.3722-1.5247-1.0636.0007-1.473.6716-1.473 1.7176ZM37.2575 8.271c0 2.1744-1.046 3.812-3.202 3.812-1.1771 0-1.9944-.5886-2.3214-1.341v1.177H29.511V.2266L31.8652.013v5.1994c.343-.6861 1.0955-1.2427 2.191-1.2427 2.1584 0 3.202 1.6353 3.202 3.812Zm-2.3542-.5397c0-1.0955-.4247-1.9128-1.553-1.9128-.9652 0-1.4882.6862-1.4882 1.7985v.8172c0 1.1116.523 1.7985 1.4882 1.7985 1.1283 0 1.553-.8173 1.553-1.9128ZM38.045 8.2215v-.3918c0-2.2407 1.2755-3.8592 3.8752-3.8592s3.876 1.6185 3.876 3.8592v.3918c0 2.2399-1.2755 3.8592-3.8752 3.8592s-3.876-1.6193-3.876-3.8592Zm5.3962-.5557c0-1.03-.4254-1.8473-1.5248-1.8473-1.0993 0-1.521.8173-1.521 1.8473v.7196c0 1.03.4255 1.8473 1.521 1.8473 1.0956 0 1.5248-.8173 1.5248-1.8473ZM46.5791 8.2215v-.3918c0-2.2407 1.2755-3.8592 3.8752-3.8592s3.8752 1.6185 3.8752 3.8592v.3918c0 2.2399-1.2755 3.8592-3.8752 3.8592s-3.8752-1.6193-3.8752-3.8592Zm5.3962-.5557c0-1.03-.4255-1.8473-1.521-1.8473s-1.5202.8173-1.5202 1.8473v.7196c0 1.03.4247 1.8473 1.5202 1.8473s1.521-.8173 1.521-1.8473ZM57.5665 7.8457l2.3215-3.7113h2.5014l-2.4358 3.8424 2.5341 3.94h-2.5014l-2.4198-3.812v3.812h-2.355V.2265l2.355-.2135Z" fill="#1877f2"></path></g></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "google" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 272 92"><path fill="#EA4335" d="M115.75 47.18c0 12.77-9.99 22.18-22.25 22.18s-22.25-9.41-22.25-22.18C71.25 34.32 81.24 25 93.5 25s22.25 9.32 22.25 22.18zm-9.74 0c0-7.98-5.79-13.44-12.51-13.44S80.99 39.2 80.99 47.18c0 7.9 5.79 13.44 12.51 13.44s12.51-5.55 12.51-13.44z"></path><path fill="#FBBC05" d="M163.75 47.18c0 12.77-9.99 22.18-22.25 22.18s-22.25-9.41-22.25-22.18c0-12.85 9.99-22.18 22.25-22.18s22.25 9.32 22.25 22.18zm-9.74 0c0-7.98-5.79-13.44-12.51-13.44s-12.51 5.46-12.51 13.44c0 7.9 5.79 13.44 12.51 13.44s12.51-5.55 12.51-13.44z"></path><path fill="#4285F4" d="M209.75 26.34v39.82c0 16.38-9.66 23.07-21.08 23.07-10.75 0-17.22-7.19-19.66-13.07l8.48-3.53c1.51 3.61 5.21 7.87 11.17 7.87 7.31 0 11.84-4.51 11.84-13v-3.19h-.34c-2.18 2.69-6.38 5.04-11.68 5.04-11.09 0-21.25-9.66-21.25-22.09 0-12.52 10.16-22.26 21.25-22.26 5.29 0 9.49 2.35 11.68 4.96h.34v-3.61h9.25zm-8.56 20.92c0-7.81-5.21-13.52-11.84-13.52-6.72 0-12.35 5.71-12.35 13.52 0 7.73 5.63 13.36 12.35 13.36 6.63 0 11.84-5.63 11.84-13.36z"></path><path fill="#34A853" d="M225 3v65h-9.5V3h9.5z"></path><path fill="#EA4335" d="m262.02 54.48 7.56 5.04c-2.44 3.61-8.32 9.83-18.48 9.83-12.6 0-22.01-9.74-22.01-22.18 0-13.19 9.49-22.18 20.92-22.18 11.51 0 17.14 9.16 18.98 14.11l1.01 2.52-29.65 12.28c2.27 4.45 5.8 6.72 10.75 6.72 4.96 0 8.4-2.44 10.92-6.14zm-23.27-7.98 19.82-8.23c-1.09-2.77-4.37-4.7-8.23-4.7-4.95 0-11.84 4.37-11.59 12.93z"></path><path fill="#4285F4" d="M35.29 41.41V32H67c.31 1.64.47 3.58.47 5.68 0 7.06-1.93 15.79-8.15 22.01-6.05 6.3-13.78 9.66-24.02 9.66C16.32 69.35.36 53.89.36 34.91.36 15.93 16.32.47 35.3.47c10.5 0 17.98 4.12 23.6 9.49l-6.64 6.64c-4.03-3.78-9.49-6.72-16.97-6.72-13.86 0-24.7 11.17-24.7 25.03 0 13.86 10.84 25.03 24.7 25.03 8.99 0 14.11-3.61 17.39-6.89 2.66-2.66 4.41-6.46 5.1-11.65l-22.49.01z"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "webex" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 195.4 77.5"><defs><style>.svg__webex-logo{fill:#010101}</style></defs><path class="svg__webex-logo" d="M47.5 13.9H55L44.1 46.7h-8.6L27.4 22l-8.1 24.8h-8.4L0 13.9h7.6l7.8 25 8-25h8.1L39.7 39l7.8-25.1ZM87 33H62.8c.2 1.7.9 3.3 1.8 4.7.9 1.2 2.1 2.2 3.4 2.8 1.4.6 2.9.9 4.4.9 1.7 0 3.4-.3 5-.9 1.6-.6 3.2-1.5 4.5-2.6l3.6 5.1c-1.8 1.6-3.9 2.8-6.2 3.5-2.4.7-4.8 1.1-7.3 1.1-3 .1-6-.7-8.7-2.1-2.5-1.4-4.6-3.5-5.9-6.1-1.4-2.6-2.1-5.6-2.1-9.1-.1-3.1.7-6.2 2.1-9 1.3-2.5 3.3-4.6 5.8-6.1 2.6-1.5 5.5-2.2 8.5-2.2 2.8-.1 5.6.7 8.1 2.2 2.4 1.5 4.3 3.6 5.5 6.1 1.4 2.8 2 5.9 2 9-.2.7-.2 1.6-.3 2.7Zm-7.2-5.5c-.4-2.6-1.3-4.5-2.8-6-1.4-1.4-3.3-2.1-5.5-2.1-2.4 0-4.4.7-5.9 2.2s-2.4 3.4-2.8 5.9h17ZM117.6 15.3c2.5 1.5 4.5 3.6 5.9 6.2 1.5 2.7 2.2 5.8 2.2 8.9s-.7 6.2-2.2 8.9c-1.4 2.6-3.4 4.7-5.9 6.2s-5.4 2.3-8.3 2.2c-2 0-4.1-.4-5.9-1.2-1.7-.8-3.2-2-4.4-3.5v3.9h-7.2V0H99v17.9c1.2-1.5 2.7-2.7 4.4-3.5 1.9-.9 3.9-1.3 5.9-1.2 2.9-.1 5.8.6 8.3 2.1Zm-3.9 24.2c1.5-.9 2.7-2.2 3.5-3.8.8-1.7 1.2-3.5 1.2-5.3s-.4-3.7-1.2-5.3c-.8-1.6-2-2.9-3.5-3.8-1.6-1-3.4-1.4-5.2-1.4s-3.6.4-5.2 1.4c-1.5.9-2.7 2.2-3.5 3.8-.8 1.7-1.3 3.5-1.2 5.4 0 1.9.4 3.7 1.2 5.4.8 1.6 2 2.9 3.5 3.8 1.6.9 3.3 1.4 5.2 1.4 1.8-.2 3.6-.7 5.2-1.6ZM161.2 33H137c.2 1.7.9 3.3 1.8 4.7.9 1.2 2.1 2.2 3.4 2.8 1.4.6 2.9.9 4.4.9 1.7 0 3.4-.3 5-.9 1.6-.6 3.2-1.5 4.5-2.6l3.6 5.1c-1.8 1.6-3.9 2.8-6.2 3.5-2.4.7-4.8 1.1-7.3 1.1-3 .1-6-.7-8.7-2.1-2.5-1.4-4.6-3.5-5.9-6.1-1.4-2.6-2.1-5.6-2.1-9.1-.1-3.1.7-6.2 2.1-9 1.3-2.5 3.3-4.6 5.8-6.1 2.6-1.5 5.5-2.2 8.5-2.2 2.8-.1 5.6.7 8.1 2.2 2.4 1.5 4.3 3.6 5.5 6.1 1.4 2.8 2 5.9 2 9-.1.7-.2 1.6-.3 2.7Zm-7.2-5.5c-.4-2.6-1.3-4.5-2.8-6-1.4-1.4-3.3-2.1-5.6-2.1s-4.4.7-5.9 2.2c-1.5 1.4-2.4 3.4-2.8 5.9H154ZM195.4 46.8h-8.8l-8.3-11.4-8.2 11.4h-8.4L174.1 30l-12.3-16.1h8.8l8 11 8-11h8.5l-12.1 16 12.4 16.9ZM109.1 73.9h-1.5V60.7h1.8v5c.4-.5.8-.9 1.4-1.2s1.2-.4 1.8-.4 1.2.1 1.8.3c.6.3 1 .6 1.4 1.1.7.9 1 2.1 1 3.5 0 1.7-.5 3-1.4 4-.4.4-.9.7-1.4.9-.5.2-1.1.3-1.6.3-.6 0-1.2-.1-1.8-.4-.5-.3-1-.7-1.4-1.2l-.1 1.3ZM115 69c0-1.2-.3-2.1-.9-2.7-.2-.2-.5-.4-.9-.6-.3-.1-.7-.2-1-.2-.4 0-.8.1-1.2.2-.4.2-.7.4-.9.7-.5.7-.8 1.6-.7 2.5 0 1.2.3 2.2.9 2.8.2.3.6.5.9.6.3.1.7.2 1.1.2s.8-.1 1.2-.2c.4-.2.7-.4.9-.8.4-.4.6-1.3.6-2.5ZM126.3 64.4l-3.3 9.7c-.4 1.3-.9 2.2-1.3 2.6-.2.3-.5.5-.8.6s-.7.2-1 .2c-.4 0-.8-.1-1.1-.2v-1.4c.3.1.6.1.9.1.2 0 .3 0 .5-.1s.3-.2.4-.3c.3-.5.6-1.1.8-1.7h-.6l-3.5-9.6h1.9l2.7 8.1 2.7-8.1 1.7.1ZM150.5 60.7h-3.3V74h3.3V60.7ZM177.5 64.5c-.9-.5-1.8-.7-2.8-.7-2.2 0-3.7 1.5-3.7 3.5s1.4 3.5 3.7 3.5c1 0 1.9-.2 2.8-.7v3.6c-1 .3-2 .5-3 .5-3.8 0-7.1-2.6-7.1-6.9 0-4 3-6.9 7.1-6.9 1 0 2.1.2 3 .5v3.6ZM142.6 64.5c-.9-.5-1.8-.7-2.8-.7-2.2 0-3.7 1.5-3.7 3.5s1.4 3.5 3.7 3.5c1 0 1.9-.2 2.8-.7v3.6c-1 .3-2 .5-3.1.5-3.8 0-7.1-2.6-7.1-6.9 0-4 3-6.9 7.1-6.9 1 0 2.1.2 3.1.5v3.6ZM188 63.9c-.5 0-.9.1-1.3.3-.4.2-.8.4-1.1.8-.3.3-.6.7-.7 1.1-.2.4-.3.9-.2 1.3 0 .5.1.9.2 1.3.2.4.4.8.7 1.1.3.3.7.6 1.1.8.4.2.9.3 1.3.3.5 0 .9-.1 1.3-.3.4-.2.8-.4 1.1-.8.3-.3.6-.7.7-1.1.2-.4.3-.9.2-1.3 0-.5-.1-.9-.2-1.3-.2-.4-.4-.8-.7-1.1-.3-.3-.7-.6-1.1-.8-.4-.3-.8-.3-1.3-.3Zm7 3.4c0 3.8-2.9 6.9-7 6.9s-7-3.1-7-6.9 2.9-6.9 7-6.9c4.1.1 7 3.2 7 6.9ZM163.2 63.7c-.9-.2-1.7-.4-2.6-.4-1.3 0-2.1.4-2.1 1.1 0 .8 1 1.1 1.5 1.3l.9.3c2.2.7 3.2 2.2 3.2 3.8 0 3.3-2.9 4.4-5.5 4.4-1.2 0-2.4-.1-3.6-.4v-3.1c1 .3 2.1.5 3.2.5 1.7 0 2.4-.5 2.4-1.2s-.7-1.1-1.5-1.3l-.7-.2c-1.8-.6-3.4-1.7-3.4-3.9 0-2.5 1.8-4.1 4.9-4.1 1.1 0 2.2.2 3.3.4v2.8Z"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "slack" ):
$return .= <<<IPSCONTENT

						<svg viewbox="0 0 498 127" xmlns="http://www.w3.org/2000/svg"><g fill="none"><path d="m159.5 99.5 6.2-14.4c6.7 5 15.6 7.6 24.4 7.6 6.5 0 10.6-2.5 10.6-6.3-.1-10.6-38.9-2.3-39.2-28.9-.1-13.5 11.9-23.9 28.9-23.9 10.1 0 20.2 2.5 27.4 8.2L212 56.5c-6.6-4.2-14.8-7.2-22.6-7.2-5.3 0-8.8 2.5-8.8 5.7.1 10.4 39.2 4.7 39.6 30.1 0 13.8-11.7 23.5-28.5 23.5-12.3 0-23.6-2.9-32.2-9.1m237.9-19.6c-3.1 5.4-8.9 9.1-15.6 9.1-9.9 0-17.9-8-17.9-17.9 0-9.9 8-17.9 17.9-17.9 6.7 0 12.5 3.7 15.6 9.1l17.1-9.5c-6.4-11.4-18.7-19.2-32.7-19.2-20.7 0-37.5 16.8-37.5 37.5s16.8 37.5 37.5 37.5c14.1 0 26.3-7.7 32.7-19.2l-17.1-9.5zM228.8 2.5h21.4v104.7h-21.4zm194.1 0v104.7h21.4V75.8l25.4 31.4h27.4l-32.3-37.3 29.9-34.8h-26.2L444.3 64V2.5zM313.8 80.1c-3.1 5.1-9.5 8.9-16.7 8.9-9.9 0-17.9-8-17.9-17.9 0-9.9 8-17.9 17.9-17.9 7.2 0 13.6 4 16.7 9.2v17.7zm0-45v8.5c-3.5-5.9-12.2-10-21.3-10-18.8 0-33.6 16.6-33.6 37.4 0 20.8 14.8 37.6 33.6 37.6 9.1 0 17.8-4.1 21.3-10v8.5h21.4v-72h-21.4z" fill="#000"></path><path d="M27.2 80c0 7.3-5.9 13.2-13.2 13.2C6.7 93.2.8 87.3.8 80c0-7.3 5.9-13.2 13.2-13.2h13.2V80zm6.6 0c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2v33c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V80z" fill="#E01E5A"></path><path d="M47 27c-7.3 0-13.2-5.9-13.2-13.2C33.8 6.5 39.7.6 47 .6c7.3 0 13.2 5.9 13.2 13.2V27H47zm0 6.7c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H13.9C6.6 60.1.7 54.2.7 46.9c0-7.3 5.9-13.2 13.2-13.2H47z" fill="#36C5F0"></path><path d="M99.9 46.9c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H99.9V46.9zm-6.6 0c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V13.8C66.9 6.5 72.8.6 80.1.6c7.3 0 13.2 5.9 13.2 13.2v33.1z" fill="#2EB67D"></path><path d="M80.1 99.8c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V99.8h13.2zm0-6.6c-7.3 0-13.2-5.9-13.2-13.2 0-7.3 5.9-13.2 13.2-13.2h33.1c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H80.1z" fill="#ECB22E"></path></g></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "discord" ):
$return .= <<<IPSCONTENT

						<svg id="svg__discord-a" xmlns="http://www.w3.org/2000/svg" viewbox="0 0 292 56.47"><defs><clippath id="svg__discord-b"><path class="svg__discord-d" d="M0 0h292v56.47H0z"></path></clippath><clippath id="svg__discord-c"><path class="svg__discord-d" d="M0 0h292v56.47H0z"></path></clippath><style>.svg__discord-d{fill:none}.svg__discord-e{fill:#5865f2}</style></defs><g clip-path="url(#svg__discord-b)"><g clip-path="url(#svg__discord-c)"><path class="svg__discord-e" d="M61.8 4.73C57.08 2.52 52.03.91 46.75 0c-.65 1.17-1.41 2.75-1.93 4-5.61-.84-11.17-.84-16.68 0-.52-1.25-1.3-2.83-1.95-4-5.28.91-10.34 2.53-15.06 4.74C1.6 19.13-.98 33.17.31 47.01c6.32 4.72 12.44 7.58 18.46 9.46 1.49-2.05 2.81-4.22 3.95-6.51-2.17-.83-4.26-1.85-6.23-3.03.52-.39 1.03-.79 1.53-1.21 12 5.61 25.05 5.61 36.91 0 .5.42 1.01.82 1.53 1.21-1.97 1.19-4.06 2.21-6.24 3.04 1.14 2.29 2.46 4.47 3.95 6.51 6.02-1.88 12.15-4.74 18.47-9.46 1.51-16.04-2.59-29.95-10.84-42.28ZM24.36 38.5c-3.6 0-6.56-3.36-6.56-7.46s2.89-7.47 6.56-7.47 6.62 3.36 6.56 7.47c0 4.1-2.89 7.46-6.56 7.46Zm24.24 0c-3.6 0-6.56-3.36-6.56-7.46s2.89-7.47 6.56-7.47 6.62 3.36 6.56 7.47c0 4.1-2.89 7.46-6.56 7.46ZM98.03 14.41h15.66c3.78 0 6.97.6 9.58 1.81 2.61 1.2 4.57 2.88 5.86 5.02 1.3 2.14 1.95 4.6 1.95 7.37s-.68 5.16-2.03 7.36c-1.35 2.2-3.41 3.94-6.19 5.23-2.77 1.28-6.2 1.93-10.31 1.93H98.01V14.41Zm14.38 21.41c2.54 0 4.5-.65 5.86-1.95 1.37-1.3 2.05-3.07 2.05-5.32 0-2.08-.61-3.74-1.82-4.98-1.22-1.24-3.06-1.87-5.52-1.87h-4.9v14.11h4.33ZM154.54 43.08c-2.17-.57-4.13-1.41-5.86-2.5v-6.81c1.31 1.04 3.07 1.89 5.28 2.57 2.21.67 4.34 1 6.41 1 .96 0 1.69-.13 2.19-.39.49-.26.74-.57.74-.93 0-.41-.13-.75-.4-1.03s-.79-.5-1.57-.7l-4.82-1.11c-2.76-.66-4.72-1.56-5.88-2.73-1.17-1.16-1.75-2.68-1.75-4.57 0-1.59.51-2.97 1.53-4.14 1.01-1.18 2.46-2.09 4.34-2.73 1.88-.64 4.07-.97 6.59-.97 2.25 0 4.31.25 6.19.74 1.88.49 3.43 1.12 4.66 1.89v6.44c-1.26-.77-2.71-1.37-4.36-1.83-1.65-.45-3.34-.67-5.08-.67-2.52 0-3.77.44-3.77 1.31 0 .41.2.72.59.92.39.21 1.11.42 2.15.64l4.02.74c2.62.46 4.58 1.28 5.86 2.44 1.29 1.16 1.93 2.88 1.93 5.15 0 2.49-1.06 4.47-3.19 5.93s-5.15 2.2-9.06 2.2c-2.3 0-4.54-.29-6.71-.87ZM182.98 42.22c-2.3-1.15-4.04-2.71-5.2-4.68s-1.74-4.18-1.74-6.65.6-4.66 1.81-6.6 2.97-3.46 5.3-4.57c2.33-1.11 5.11-1.66 8.35-1.66 4.02 0 7.35.86 10 2.58v7.51c-.93-.66-2.03-1.19-3.27-1.6-1.25-.41-2.58-.62-4-.62-2.49 0-4.43.46-5.84 1.39-1.41.93-2.11 2.14-2.11 3.65s.68 2.68 2.05 3.63c1.37.94 3.35 1.42 5.94 1.42 1.34 0 2.66-.2 3.96-.59 1.3-.4 2.42-.88 3.35-1.46v7.26c-2.94 1.81-6.36 2.71-10.24 2.71-3.27-.01-6.06-.59-8.36-1.73ZM211.52 42.22c-2.32-1.15-4.09-2.72-5.3-4.72-1.22-2-1.83-4.23-1.83-6.69s.61-4.66 1.83-6.59c1.22-1.93 2.98-3.44 5.29-4.54 2.3-1.1 5.05-1.64 8.23-1.64s5.93.55 8.23 1.64 4.06 2.6 5.26 4.51c1.21 1.92 1.81 4.11 1.81 6.6s-.6 4.69-1.81 6.69-2.97 3.57-5.28 4.72c-2.32 1.15-5.06 1.72-8.22 1.72s-5.9-.57-8.21-1.72Zm12.2-7.28c.98-1 1.47-2.31 1.47-3.96s-.49-2.95-1.47-3.91c-.98-.97-2.31-1.46-3.99-1.46s-3.06.49-4.04 1.46c-.97.97-1.46 2.27-1.46 3.91s.49 2.96 1.46 3.96c.98 1 2.32 1.5 4.04 1.5 1.69 0 3.02-.5 3.99-1.5ZM259.17 19.57v8.86c-1.02-.69-2.34-1.03-3.98-1.03-2.14 0-3.79.66-4.94 1.99-1.15 1.32-1.73 3.39-1.73 6.18v7.55h-9.84v-24h9.64v7.63c.53-2.79 1.4-4.85 2.59-6.18 1.19-1.32 2.72-1.99 4.6-1.99 1.42 0 2.63.33 3.66.98ZM291.86 13.59v29.54h-9.84v-5.37c-.83 2.02-2.09 3.56-3.79 4.62-1.7 1.05-3.8 1.58-6.29 1.58-2.23 0-4.16-.55-5.82-1.66-1.66-1.11-2.94-2.63-3.84-4.55-.89-1.93-1.35-4.11-1.35-6.55-.03-2.51.45-4.77 1.43-6.77s2.36-3.56 4.14-4.68c1.78-1.12 3.81-1.68 6.09-1.68 4.69 0 7.83 2.08 9.44 6.24V13.59h9.84Zm-11.31 21.19c1-1 1.5-2.29 1.5-3.87s-.49-2.78-1.46-3.73c-.98-.96-2.31-1.44-3.99-1.44s-2.98.49-3.98 1.46c-.99.97-1.49 2.23-1.49 3.79s.49 2.83 1.49 3.82 2.3 1.48 3.94 1.48c1.66 0 2.99-.5 3.99-1.5ZM139.38 21.68c2.71 0 4.91-2.02 4.91-4.5s-2.2-4.5-4.91-4.5-4.91 2.01-4.91 4.5 2.2 4.5 4.91 4.5ZM134.47 24.78c3.01 1.32 6.74 1.38 9.81 0v18.47h-9.81V24.78Z"></path></g></g></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "teams" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 2228.833 2073.333"><path fill="#5059C9" d="M1554.637 777.5h575.713c54.391 0 98.483 44.092 98.483 98.483v524.398c0 199.901-162.051 361.952-361.952 361.952h-1.711c-199.901.028-361.975-162-362.004-361.901V828.971c.001-28.427 23.045-51.471 51.471-51.471z"></path><circle fill="#5059C9" cx="1943.75" cy="440.583" r="233.25"></circle><circle fill="#7B83EB" cx="1218.083" cy="336.917" r="336.917"></circle><path fill="#7B83EB" d="M1667.323 777.5H717.01c-53.743 1.33-96.257 45.931-95.01 99.676v598.105c-7.505 322.519 247.657 590.16 570.167 598.053 322.51-7.893 577.671-275.534 570.167-598.053V877.176c1.245-53.745-41.268-98.346-95.011-99.676z"></path><path opacity=".1" d="M1244 777.5v838.145c-.258 38.435-23.549 72.964-59.09 87.598a91.8564 91.8564 0 0 1-35.765 7.257H667.613c-6.738-17.105-12.958-34.21-18.142-51.833a631.2871 631.2871 0 0 1-27.472-183.49V877.02c-1.246-53.659 41.198-98.19 94.855-99.52H1244z"></path><path opacity=".2" d="M1192.167 777.5v889.978a91.8383 91.8383 0 0 1-7.257 35.765c-14.634 35.541-49.163 58.833-87.598 59.09H691.975c-8.812-17.105-17.105-34.21-24.362-51.833-7.257-17.623-12.958-34.21-18.142-51.833a631.282 631.282 0 0 1-27.472-183.49V877.02c-1.246-53.659 41.198-98.19 94.855-99.52h475.313z"></path><path opacity=".2" d="M1192.167 777.5v786.312c-.395 52.223-42.632 94.46-94.855 94.855h-447.84A631.282 631.282 0 0 1 622 1475.177V877.02c-1.246-53.659 41.198-98.19 94.855-99.52h475.312z"></path><path opacity=".2" d="M1140.333 777.5v786.312c-.395 52.223-42.632 94.46-94.855 94.855H649.472A631.282 631.282 0 0 1 622 1475.177V877.02c-1.246-53.659 41.198-98.19 94.855-99.52h423.478z"></path><path opacity=".1" d="M1244 509.522v163.275c-8.812.518-17.105 1.037-25.917 1.037-8.812 0-17.105-.518-25.917-1.037a284.4725 284.4725 0 0 1-51.833-8.293c-104.963-24.857-191.679-98.469-233.25-198.003a288.0208 288.0208 0 0 1-16.587-51.833h258.648c52.305.198 94.657 42.549 94.856 94.854z"></path><path opacity=".2" d="M1192.167 561.355v111.442a284.4725 284.4725 0 0 1-51.833-8.293c-104.963-24.857-191.679-98.469-233.25-198.003h190.228c52.304.198 94.656 42.55 94.855 94.854z"></path><path opacity=".2" d="M1192.167 561.355v111.442a284.4725 284.4725 0 0 1-51.833-8.293c-104.963-24.857-191.679-98.469-233.25-198.003h190.228c52.304.198 94.656 42.55 94.855 94.854z"></path><path opacity=".2" d="M1140.333 561.355v103.148c-104.963-24.857-191.679-98.469-233.25-198.003h138.395c52.305.199 94.656 42.551 94.855 94.855z"></path><lineargradient id="svg__teams-a" gradientunits="userSpaceOnUse" x1="198.099" y1="1683.0726" x2="942.2344" y2="394.2607" gradienttransform="matrix(1 0 0 -1 0 2075.3333)"><stop offset="0" stop-color="#5a62c3"></stop><stop offset=".5" stop-color="#4d55bd"></stop><stop offset="1" stop-color="#3940ab"></stop></lineargradient><path fill="url(#svg__teams-a)" d="M95.01 466.5h950.312c52.473 0 95.01 42.538 95.01 95.01v950.312c0 52.473-42.538 95.01-95.01 95.01H95.01c-52.473 0-95.01-42.538-95.01-95.01V561.51c0-52.472 42.538-95.01 95.01-95.01z"></path><path fill="#FFF" d="M820.211 828.193h-189.97v517.297h-121.03V828.193H320.123V727.844h500.088v100.349z"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "tiktok" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 1000 291.379" xml:space="preserve"><path fill="#FF004F" d="M191.102 105.182c18.814 13.442 41.862 21.351 66.755 21.351V78.656c-4.711.001-9.41-.49-14.019-1.466v37.686c-24.891 0-47.936-7.909-66.755-21.35v97.703c0 48.876-39.642 88.495-88.54 88.495-18.245 0-35.203-5.513-49.29-14.968 16.078 16.431 38.5 26.624 63.306 26.624 48.901 0 88.545-39.619 88.545-88.497v-97.701h-.002zm17.294-48.302c-9.615-10.499-15.928-24.067-17.294-39.067v-6.158h-13.285c3.344 19.065 14.75 35.353 30.579 45.225zM70.181 227.25a40.2992 40.2992 0 0 1-8.262-24.507c0-22.354 18.132-40.479 40.502-40.479 4.169-.001 8.313.637 12.286 1.897v-48.947a89.3489 89.3489 0 0 0-14.013-.807v38.098a40.561 40.561 0 0 0-12.292-1.896c-22.37 0-40.501 18.123-40.501 40.48 0 15.808 9.063 29.494 22.28 36.161z"></path><path d="M177.083 93.525c18.819 13.441 41.864 21.35 66.755 21.35V77.189c-13.894-2.958-26.194-10.215-35.442-20.309-15.83-9.873-27.235-26.161-30.579-45.225h-34.896v191.226c-.079 22.293-18.18 40.344-40.502 40.344-13.154 0-24.84-6.267-32.241-15.975-13.216-6.667-22.279-20.354-22.279-36.16 0-22.355 18.131-40.48 40.501-40.48 4.286 0 8.417.667 12.292 1.896v-38.098c-48.039.992-86.674 40.224-86.674 88.474 0 24.086 9.621 45.921 25.236 61.875 14.087 9.454 31.045 14.968 49.29 14.968 48.899 0 88.54-39.621 88.54-88.496V93.525h-.001z"></path><path fill="#00F2EA" d="M243.838 77.189v-10.19a66.768 66.768 0 0 1-35.442-10.12 66.9532 66.9532 0 0 0 35.442 20.31zm-66.021-65.534a68.2815 68.2815 0 0 1-.734-5.497V0h-48.182v191.228c-.077 22.29-18.177 40.341-40.501 40.341-6.554 0-12.742-1.555-18.222-4.318 7.401 9.707 19.087 15.973 32.241 15.973 22.32 0 40.424-18.049 40.502-40.342V11.655h34.896zm-77.123 102.753V103.56c-4.026-.55-8.085-.826-12.149-.824C39.642 102.735 0 142.356 0 191.228c0 30.64 15.58 57.643 39.255 73.527-15.615-15.953-25.236-37.789-25.236-61.874 0-48.249 38.634-87.481 86.675-88.473z"></path><path fill="#FF004F" d="M802.126 239.659c34.989 0 63.354-28.136 63.354-62.84 0-34.703-28.365-62.844-63.354-62.844h-9.545c34.99 0 63.355 28.14 63.355 62.844s-28.365 62.84-63.355 62.84h9.545z"></path><path fill="#00F2EA" d="M791.716 113.975h-9.544c-34.988 0-63.358 28.14-63.358 62.844s28.37 62.84 63.358 62.84h9.544c-34.993 0-63.358-28.136-63.358-62.84-.001-34.703 28.365-62.844 63.358-62.844z"></path><path d="M310.062 85.572v31.853h37.311v121.374h37.326V118.285h30.372l10.414-32.712H310.062zm305.482 0v31.853h37.311v121.374h37.326V118.285h30.371l10.413-32.712H615.544zm-183.11 18.076c0-9.981 8.146-18.076 18.21-18.076 10.073 0 18.228 8.095 18.228 18.076 0 9.982-8.15 18.077-18.228 18.077-10.064-.005-18.21-8.095-18.21-18.077zm0 30.993h36.438v104.158h-36.438V134.641zm52.062-49.069v153.226h36.452v-39.594l11.283-10.339 35.577 50.793h39.05l-51.207-74.03 45.997-44.768H557.39l-36.442 36.153V85.572h-36.452zm393.127 0v153.226h36.457v-39.594l11.278-10.339 35.587 50.793H1000l-51.207-74.03 45.995-44.768h-44.256l-36.452 36.153V85.572h-36.457zM792.578 239.659c34.988 0 63.358-28.136 63.358-62.84 0-34.703-28.37-62.844-63.358-62.844h-.865c-34.99 0-63.355 28.14-63.355 62.844s28.365 62.84 63.355 62.84h.865zm-31.242-62.84c0-16.881 13.8-30.555 30.817-30.555 17.005 0 30.804 13.674 30.804 30.555s-13.799 30.563-30.804 30.563c-17.017-.003-30.817-13.682-30.817-30.563z"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "twitch" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" xml:space="preserve" viewbox="0 0 454.9307 150.776"><path d="M444.54 51.9893 426.3453 33.796h-33.8v-23.4H363.944v106.5867h28.6013v-54.596h23.3934v54.596H444.54V51.9893ZM353.552 33.796h-44.1947l-18.1986 18.1933v46.792l18.1986 18.2014h44.1947V88.3893h-33.8V62.3867h33.8V33.796zm-72.792 0h-23.3933v-23.4h-28.6014v88.3853l18.1987 18.2014h33.796V88.3893h-23.3933V62.3867H280.76V33.796zm-62.3933-23.4h-28.5934v13.0027h28.5934V10.396zm0 23.4h-28.5934v83.1853h28.5934V33.796zm-38.992 0h-28.596v54.5933h-10.396V33.796h-28.5974v54.5933h-10.3946V33.796H72.7893v83.1867H161.18l18.1947-18.2014V33.796zm-116.984 0H38.996v-23.4H10.4v88.3853l18.1973 18.2014h33.7934V88.3893H38.996V62.3867h23.3947V33.796zm392.54 12.9933v77.988l-38.992 25.9987H389.944v-13.0027l-18.1973 13.0027h-23.3934v-13.0027l-12.9946 13.0027h-41.596L280.76 137.7733l-2.6027 13.0027H241.768l-14.8813-13.0027-.8334 13.0027h-41.2466l-1.4587-13.0027-11.1653 13.0027h-63l-13-5.2v5.2h-33.792l-38.996-23.4027L0 103.988V0h49.396l23.3933 23.3987h106.5854V0h88.3866v23.3987h23.3974v12.9946l13.0026-12.9946h25.9907L353.552 0h49.392v23.3987h28.5973l23.3894 23.3906Z" fill="#6441a5" fill-rule="evenodd"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "vimeo" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 70 20" fill="#1ab7ea"><path d="M15.72 9.431c-.069 1.514-1.127 3.588-3.172 6.22-2.114 2.749-3.903 4.124-5.367 4.124-.906 0-1.673-.837-2.3-2.512-.418-1.535-.837-3.069-1.255-4.604-.465-1.674-.964-2.512-1.498-2.512-.116 0-.524.245-1.221.733l-.731-.943C.943 9.263 1.7 8.588 2.445 7.912c1.024-.884 1.792-1.35 2.305-1.397 1.21-.116 1.955.712 2.235 2.483.302 1.912.511 3.101.628 3.566.349 1.586.733 2.378 1.152 2.378.326 0 .815-.515 1.467-1.543.651-1.029 1-1.812 1.047-2.349.093-.888-.256-1.333-1.047-1.333-.373 0-.757.085-1.151.255.764-2.504 2.224-3.721 4.38-3.652 1.598.047 2.351 1.084 2.259 3.111"></path><path d="M22.281 1.918c-.023.58-.314 1.136-.874 1.669-.628.602-1.373.903-2.234.903-1.327 0-1.968-.579-1.921-1.737.022-.602.378-1.182 1.064-1.738.687-.555 1.449-.834 2.288-.834.489 0 .896.192 1.223.574.325.382.477.77.454 1.163zm3.038 12.419c-.652 1.232-1.548 2.349-2.689 3.349-1.56 1.349-3.119 2.024-4.679 2.024-.723 0-1.275-.233-1.659-.699-.384-.465-.565-1.069-.541-1.814.022-.767.261-1.954.715-3.56.454-1.605.682-2.466.682-2.582 0-.605-.21-.908-.629-.908-.139 0-.536.245-1.188.733l-.803-.943c.745-.674 1.49-1.349 2.235-2.025 1.001-.884 1.746-1.35 2.236-1.397.768-.069 1.332.157 1.693.679.36.523.494 1.2.402 2.035-.303 1.415-.629 3.212-.978 5.392-.024.998.337 1.496 1.082 1.496.326 0 .908-.344 1.746-1.033.699-.574 1.269-1.114 1.712-1.62l.663.873"></path><path d="M47.127 14.336c-.652 1.233-1.548 2.349-2.689 3.349-1.56 1.349-3.12 2.024-4.679 2.024-1.514 0-2.247-.837-2.2-2.513.022-.745.168-1.639.436-2.686.267-1.048.413-1.862.436-2.444.024-.883-.245-1.326-.806-1.326-.607 0-1.331.722-2.172 2.165-.887 1.514-1.367 2.98-1.436 4.4-.05 1.002.05 1.77.293 2.305-1.624.047-2.762-.221-3.411-.803-.582-.512-.848-1.361-.801-2.549.02-.745.136-1.49.343-2.235.205-.745.319-1.408.342-1.991.05-.861-.268-1.292-.944-1.292-.583 0-1.213.664-1.888 1.991-.676 1.326-1.049 2.712-1.119 4.155-.05 1.305.04 2.212.25 2.724-1.598.047-2.733-.29-3.404-1.01-.558-.603-.812-1.52-.765-2.751.02-.603.129-1.445.321-2.524.192-1.08.299-1.921.321-2.525.05-.417-.06-.627-.314-.627-.14 0-.536.236-1.188.707l-.838-.943c.117-.092.849-.768 2.2-2.025.978-.907 1.641-1.373 1.99-1.396.606-.047 1.094.203 1.467.75.372.547.559 1.182.559 1.903 0 .233-.02.454-.07.664.349-.535.756-1.002 1.222-1.398 1.071-.93 2.27-1.455 3.597-1.571 1.141-.093 1.955.174 2.445.803.395.512.581 1.246.558 2.2.163-.139.338-.291.525-.454.534-.628 1.058-1.128 1.57-1.501.861-.629 1.759-.978 2.689-1.048 1.118-.093 1.921.173 2.41.8.418.51.605 1.241.559 2.191-.024.65-.181 1.595-.472 2.836-.292 1.241-.436 1.953-.436 2.139-.024.488.023.824.139 1.009.117.186.395.278.838.278.326 0 .907-.344 1.746-1.034.698-.573 1.269-1.113 1.712-1.619l.664.872"></path><path d="M52.295 10.654c.022-.625-.233-.938-.767-.938-.698 0-1.407.481-2.127 1.442-.721.961-1.093 1.882-1.116 2.762-.013 0-.013.151 0 .452 1.139-.417 2.127-1.053 2.964-1.911.674-.741 1.022-1.344 1.046-1.807zm7.927 3.646c-.675 1.117-2.002 2.232-3.981 3.348-2.467 1.418-4.971 2.126-7.508 2.126-1.885 0-3.237-.628-4.051-1.885-.582-.861-.861-1.885-.838-3.072.023-1.885.862-3.677 2.515-5.377 1.815-1.862 3.957-2.794 6.425-2.794 2.282 0 3.492.93 3.632 2.787.093 1.184-.559 2.404-1.956 3.658-1.49 1.371-3.365 2.241-5.622 2.612.418.581 1.046.871 1.885.871 1.676 0 3.504-.426 5.483-1.279 1.42-.599 2.538-1.221 3.353-1.866l.663.871"></path><path d="M65.755 11.828c.023-.63-.064-1.207-.262-1.732-.198-.524-.484-.788-.855-.788-1.188 0-2.166.642-2.933 1.925-.653 1.05-1.003 2.17-1.048 3.358-.024.584.081 1.098.314 1.54.255.514.616.77 1.083.77 1.047 0 1.944-.617 2.689-1.854.628-1.027.965-2.1 1.012-3.219zm3.946.132c-.093 2.139-.884 3.987-2.374 5.544-1.49 1.557-3.342 2.336-5.553 2.336-1.839 0-3.236-.593-4.19-1.779-.698-.883-1.083-1.987-1.152-3.311-.118-2 .604-3.836 2.165-5.51 1.676-1.859 3.782-2.789 6.32-2.789 1.629 0 2.863.547 3.702 1.639.792 1 1.152 2.29 1.082 3.87"></path></svg>
					
IPSCONTENT;

elseif ( $event->online_type === "spotme" ):
$return .= <<<IPSCONTENT

						<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewbox="0 0 131 35"><defs><path id="spotme-a" d="M96.11 34.27h10.4v11.1h-10.4z"></path><path id="spotme-e" d="M106.51 39.82c0 3.07-2.33 5.56-5.2 5.56s-5.2-2.49-5.2-5.56c0-3.06 2.33-5.55 5.2-5.55s5.2 2.49 5.2 5.55"></path><path id="spotme-b" d="M127.4 68.4h-13.83V46.21h13.84z"></path><path id="spotme-g" d="M113.72 64.98c.28-.4.52-.87.8-1.27s.71-.52 1.05-.24c.18.15 2.56 2.12 4.93 2.12 2.13 0 3.49-1.3 3.49-2.86 0-1.85-1.6-3.02-4.66-4.28-3.14-1.33-5.6-2.96-5.6-6.53 0-2.4 1.84-5.7 6.74-5.7 3.09 0 5.4 1.6 5.7 1.82.25.15.5.58.19 1.04l-.77 1.17c-.25.4-.65.59-1.08.31-.22-.12-2.37-1.54-4.16-1.54-2.6 0-3.48 1.63-3.48 2.77 0 1.76 1.35 2.84 3.91 3.89 3.58 1.44 6.63 3.14 6.63 6.9 0 3.2-2.87 5.81-6.88 5.81a10 10 0 0 1-6.62-2.43c-.28-.24-.5-.46-.19-.98"></path><path id="spotme-h" d="M133.72 57.47v-7.7h4.2c2.1 0 3.86 1.62 3.86 3.73a3.91 3.91 0 0 1-3.86 3.97zM130.6 67.8c0 .31.24.6.58.6h1.97a.6.6 0 0 0 .58-.6v-7.29h4.4c3.72 0 6.79-3.13 6.79-6.98a6.87 6.87 0 0 0-6.81-6.86h-6.93a.58.58 0 0 0-.58.59z"></path><path id="spotme-i" d="M157.71 65.9a8.01 8.01 0 0 1-7.95-8.03c0-4.4 3.6-8.1 7.95-8.1a8.1 8.1 0 0 1 7.98 8.1c0 4.43-3.59 8.03-7.98 8.03zm0-19.23c-6.14 0-11.02 5-11.02 11.2a11.03 11.03 0 1 0 22.07 0c0-6.2-4.9-11.2-11.05-11.2z"></path><path id="spotme-j" d="M174.93 49.59h-4.99a.59.59 0 0 1-.58-.6v-1.73c0-.31.24-.59.58-.59h13.16c.33 0 .58.28.58.59V49c0 .3-.25.59-.58.59h-4.99V67.8a.6.6 0 0 1-.58.58h-2.02a.6.6 0 0 1-.58-.58z"></path><path id="spotme-k" d="M187.43 47.13c.06-.24.3-.46.55-.46h.48c.18 0 .45.15.51.34l6.3 15.85h.13L201.67 47c.07-.19.3-.34.52-.34h.48c.24 0 .48.22.54.46l3.72 20.84c.09.44-.12.72-.55.72h-1.96a.63.63 0 0 1-.57-.44L201.6 54.4h-.08l-5.44 14.27c-.06.18-.24.34-.51.34h-.54c-.24 0-.46-.16-.52-.34l-5.46-14.27h-.12l-2.17 13.86c-.03.22-.3.44-.55.44h-1.96c-.42 0-.63-.28-.57-.72z"></path><path id="spotme-c" d="M223.93 68.69v-21.9h-13.26v21.9h13.26z"></path><path id="spotme-m" d="M210.67 47.38c0-.3.24-.59.58-.59h12.1c.34 0 .58.28.58.6v1.75c0 .3-.24.59-.57.59h-9.57v6.38h8.09c.3 0 .57.28.57.6v1.75c0 .34-.27.6-.57.6h-8.1v6.72h9.58c.33 0 .57.28.57.6v1.72c0 .3-.24.59-.57.59h-12.11a.58.58 0 0 1-.58-.6z"></path><path id="spotme-n" d="M106.88 50.86c.08.1.09.22 0 .31-.3.36-1.08 1.24-1.57 1.78-.14.16-.4.05-.4-.16l.1-3.27c.01-.2.25-.3.4-.16l1.47 1.5zm-8.98-1.5a.23.23 0 0 1 .39.16l.1 3.27c0 .21-.26.32-.4.16-.5-.54-1.26-1.42-1.58-1.78a.23.23 0 0 1 .01-.3c.3-.33 1.03-1.06 1.48-1.5zm7.26 7.4c-.1-.35 0-.73.27-.97l4.66-4.16a.63.63 0 0 0 .02-.92c-.89-.87-2.7-2.6-3.7-3.6a1.53 1.53 0 0 0-1.08-.44h-7.36c-.4 0-.78.16-1.07.44l-3.71 3.6a.63.63 0 0 0 .02.92l4.66 4.16c.27.24.38.62.27.97l-2.8 9.29c-.08.23-1.65 1.58-2.27 2.12a.13.13 0 0 0 .09.22h5.55c.12 0 .22-.09.24-.21.95-5.7 1.71-8.42 2.24-9.74a.5.5 0 0 1 .92 0c.53 1.32 1.29 4.03 2.24 9.74.02.12.12.21.25.21h5.55c.11 0 .17-.14.08-.22-.62-.54-2.19-1.9-2.27-2.12z"></path><clippath id="spotme-d"><use xlink:href="#spotme-a"></use></clippath><clippath id="spotme-f"><use xlink:href="#spotme-b"></use></clippath><clippath id="spotme-l"><use xlink:href="#spotme-c"></use></clippath></defs><g clip-path="url(#spotme-d)" transform="translate(-93 -34)"><use fill="#e85224" xlink:href="#spotme-e"></use></g><g clip-path="url(#spotme-f)" transform="translate(-93 -34)"><use fill="#e85224" xlink:href="#spotme-g"></use></g><use fill="#e85224" xlink:href="#spotme-h" transform="translate(-93 -34)"></use><use fill="#e85224" xlink:href="#spotme-i" transform="translate(-93 -34)"></use><use fill="#e85224" xlink:href="#spotme-j" transform="translate(-93 -34)"></use><use fill="#e85224" xlink:href="#spotme-k" transform="translate(-93 -34)"></use><g clip-path="url(#spotme-l)" transform="translate(-93 -34)"><use fill="#e85224" xlink:href="#spotme-m"></use></g><use fill="#353431" xlink:href="#spotme-n" transform="translate(-93 -34)"></use></svg>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedOnline:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpedOnline:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvped:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvped:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $livetopic = $event->_livetopic_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "livetopic:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="livetopic" class="ipsBox ipsPull i-padding_3">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "livetopic:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

		<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $livetopic->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" class="ipsButton ipsButton--primary ipsButton--wide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'open_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
	
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "livetopic:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "livetopic:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	
IPSCONTENT;

if ( $event->rsvp  ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpWrapper:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvpWrapper" class="ipsBox ipsPull">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpWrapper:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", \IPS\Request::i()->app )->rsvpControls( $event, $attendees );
$return .= <<<IPSCONTENT

			<h3 class="ipsTitle ipsTitle--h3 ipsHide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rsvp_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
			<i-tabs class="ipsTabs ipsTabs--small ipsTabs--stretch" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipstabbar data-ipstabbar-contentarea="#ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpTabs:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvpTabs" role="tablist">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpTabs:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					<button type="button" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elGoing" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elGoing_panel" aria-selected="true">
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attendees_past', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count($attendees[1]) ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count($attendees[1]), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>
					
IPSCONTENT;

if ( $event->rsvp_limit == -1 ):
$return .= <<<IPSCONTENT

						<button type="button" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elMaybe" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elMaybe_panel" aria-selected="false">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_maybe_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count($attendees[2]) ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count($attendees[2]), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>	
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<button type="button" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elNotGoing" class="ipsTabs__tab" role="tab" aria-controls="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elNotGoing_panel" aria-selected="false">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_notgoing_attendees', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( \count($attendees[0]) ):
$return .= <<<IPSCONTENT
(
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count($attendees[0]), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
)
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</button>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpTabs:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpTabs:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->tabScrollers(  );
$return .= <<<IPSCONTENT

			</i-tabs>
			<div id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_content" class="ipsTabs__panels ipsTabs__panels--padded">
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpAttendees:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvpAttendees" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elGoing_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elGoing">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpAttendees:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count($attendees[1])  ):
$return .= <<<IPSCONTENT

						<ul class="ipsGrid ipsGrid--event-attendees i-basis_40">
							
IPSCONTENT;

foreach ( $attendees[1] as $attendee ):
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipstooltip class="ipsUserPhoto ipsUserPhoto--mini" aria-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span class="ipsUserPhoto ipsUserPhoto--mini" aria-label="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

										</span></a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						</ul>
					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<p class="i-color_soft i-padding_3 i-text-align_center">
							
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rsvps_past', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rsvps_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpAttendees:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpAttendees:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $event->rsvp_limit == -1 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybeWrapper:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvpMaybeWrapper" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elMaybe_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elMaybe" hidden>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybeWrapper:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( \count($attendees[2])  ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybe:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="rsvpMaybe" class="ipsGrid ipsGrid--event-attendees i-basis_40">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybe:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

foreach ( $attendees[2] as $attendee ):
$return .= <<<IPSCONTENT

									<li>
										
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

											<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipstooltip class="ipsUserPhoto ipsUserPhoto--mini">
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											<span class="ipsUserPhoto ipsUserPhoto--mini">
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
										
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

											</span></a>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</li>
								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybe:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybe:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<p class="i-color_soft i-padding_3 i-text-align_center">
								
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_maybe_rsvps_past', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_maybe_rsvps_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</p>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybeWrapper:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpMaybeWrapper:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoingWrapper:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="rsvpNotGoingWrapper" id="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elNotGoing_panel" class="ipsTabs__panel" role="tabpanel" aria-labelledby="ipsTabs_elAttendees
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $tabId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_elNotGoing" hidden>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoingWrapper:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( \count($attendees[0])  ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoing:before", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="rsvpNotGoing" class="ipsGrid ipsGrid--event-attendees i-basis_40">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoing:inside-start", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

foreach ( $attendees[0] as $attendee ):
$return .= <<<IPSCONTENT

								<li>
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

										<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipstooltip class="ipsUserPhoto ipsUserPhoto--mini">
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<span class="ipsUserPhoto ipsUserPhoto--mini">
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $attendee->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="" loading="lazy">
									
IPSCONTENT;

if ( \IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'members' ) )  ):
$return .= <<<IPSCONTENT

										</span></a>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</li>
							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoing:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoing:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						<p class="i-color_soft i-padding_3 i-text-align_center">
							
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_decline_rsvps_past', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_decline_rsvps_yet', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoingWrapper:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpNotGoingWrapper:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT


				
IPSCONTENT;

if ( \count($attendees[0]) OR \count($attendees[1]) OR \count($attendees[2])  ):
$return .= <<<IPSCONTENT

					<div class="i-padding_2 i-text-align_center">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('downloadRsvp'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--small ipsButton--inherit ipsButton--wide" rel="noindex nofollow"><i class="fa-regular fa-circle-down"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_download', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpWrapper:inside-end", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/eventSidebar", "rsvpWrapper:after", [ $event,$attendees,$tabId,$address ] );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function eventStreamBlock( $event, $date, $truncate=FALSE, $map=array( 300, 200 ), $revertToFirst=FALSE ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$coverPhoto = $event->coverPhoto();
$return .= <<<IPSCONTENT


<div class='cCalendarBlock i-background_1 ipsInnerBox i-margin-bottom_block 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->ui( 'css' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->ui( 'dataAttributes' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
>
	<div class='ipsCoverPhoto' data-controller='core.global.core.coverPhoto' data-coverOffset='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' style='--offset:
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->offset, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
		
IPSCONTENT;

if ( $coverPhoto->file ):
$return .= <<<IPSCONTENT

			<div class='ipsCoverPhoto__container'>
				<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $coverPhoto->file->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCoverPhoto__image' alt='' loading='lazy'>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class='i-flex i-align-items_center i-gap_4'>
			<div class='i-flex_00'>
				<div class="ipsUserPhoto ipsUserPhoto--mini">
					<img src='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->author()->photo, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' alt='' loading='lazy'>
				</div>
			</div>
			<div class='i-flex_11'>
				<h2 class='ipsTitle ipsTitle--h3 ipsPageHead_barText ipsPageHead_barText_small'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( $event->hidden() === 1 ):
$return .= <<<IPSCONTENT
 <span class="ipsBadge ipsBadge--icon ipsBadge--warning" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-triangle-exclamation'></i></span>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a></h2>
			</div>
		</div>
	</div>
	<div class='i-flex i-gap_2 i-flex-wrap_wrap i-padding_3'>
		<div class='i-flex_91 i-basis_600 i-flex i-gap_4'>
			<div class='i-flex_00'>
				<div class='eventStreamDate'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

if ( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ):
$return .= <<<IPSCONTENT

							<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
							<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<time datetime='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->mysqlDatetime(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->mysqlDatetime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' class='ipsCalendarDate'>
							<span class='ipsCalendarDate__month'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->monthNameShort, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
							<span class='ipsCalendarDate__date'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->mday, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</time>
					</a>
				</div>
			</div>
			<div class='i-flex_11'>
				<div class='i-flex i-flex-wrap_wrap'>
					<div class='i-flex_11' style='flex-basis: 450px'>
						<h3>
							<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

if ( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</time>
							
IPSCONTENT;

if ( $event->_end_date ):
$return .= <<<IPSCONTENT

							&nbsp;&nbsp;<i class='fa-solid fa-circle-arrow-right i-font-size_2 i-color_soft'></i>&nbsp;&nbsp;
							<time datetime='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

$sameDay = !( ($event->_start_date->mday != $event->_end_date->mday) or ($event->_start_date->mon != $event->_end_date->mon) or ($event->_start_date->year != $event->_end_date->year) );
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( $event->nextOccurrence( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' ) ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</time>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</h3>
						
IPSCONTENT;

if ( $event->recurring ):
$return .= <<<IPSCONTENT
<p class='i-color_soft'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_recurring_text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p><br>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class='ipsRichText 
IPSCONTENT;

if ( ( \IPS\Widget\Request::i()->isAjax() or $truncate ) && $event->content ):
$return .= <<<IPSCONTENT
 ipsTruncate_5
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
							
IPSCONTENT;

if ( $event->content ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax() or $truncate ):
$return .= <<<IPSCONTENT

									{$event->truncated()}
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									{$event->content}
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</div>
						
IPSCONTENT;

if ( $event->container()->allow_comments OR $event->container()->allow_reviews OR $event->rsvp ):
$return .= <<<IPSCONTENT

							<div class='i-margin-top_3'>
								
IPSCONTENT;

if ( $event->container()->allow_comments ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->setQueryString( 'tab', 'comments' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( $event->comments ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_comment_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $event->container()->allow_comments AND $event->container()->allow_reviews ):
$return .= <<<IPSCONTENT
&middot;
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $event->container()->allow_reviews ):
$return .= <<<IPSCONTENT
<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->setQueryString( 'tab', 'reviews' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$pluralize = array( $event->reviews ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_review_count', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $event->rsvp  ):
$return .= <<<IPSCONTENT

									<hr class='ipsHr'>
									<h4><strong>
IPSCONTENT;

$pluralize = array( $event->attendeeCount( \IPS\calendar\Event::RSVP_YES ) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_rsvp_attendees_list', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</strong></h4>
									
IPSCONTENT;

if ( \count( $event->attendees( \IPS\calendar\Event::RSVP_YES, 5 ) ) ):
$return .= <<<IPSCONTENT

										<ul class='ipsList ipsList--inline i-gap_0 i-margin-top_2'>
											
IPSCONTENT;

foreach ( $event->attendees( \IPS\calendar\Event::RSVP_YES, 5 ) as $attendee ):
$return .= <<<IPSCONTENT

											<li>
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $attendee, 'tiny' );
$return .= <<<IPSCONTENT
</li>
											
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

										</ul>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class='i-flex_00'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='cEvents_event cEvents_eventSmall cEvents_style
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->container()->_title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
					</div>
				</div>
			</div>
		</div>
		
IPSCONTENT;

if ( $map !== FALSE AND $event->map( $map[0], $map[1] ) ):
$return .= <<<IPSCONTENT

			<div class='i-flex_11 cCalendarBlock_map'>
				{$event->map( $map[0], $map[1] )}
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</div>
IPSCONTENT;

		return $return;
}

	function reminder( $event, $reminder, $buttonClass='' ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='calendar.front.view.reminderButton' data-reminderID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->reminderButton( $event, $reminder, $buttonClass );
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function reminderButton( $event, $reminder=NULL, $buttonClass='' ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $reminder ):
$return .= <<<IPSCONTENT

		<a class="ipsButton ipsButton--primary ipsButton--small 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $buttonClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="reminderButton" data-reminder="true" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=event&id={$event->id}&do=setReminder" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_edit_reminder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick><i class='fa-solid fa-bell'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_edit_reminder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<a class="ipsButton ipsButton--soft ipsButton--small 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $buttonClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-role="reminderButton" data-reminder="false" href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=event&id={$event->id}&do=setReminder" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_set_reminder_tip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipsTooltip data-ipsHover data-ipsHover-cache='false' data-ipsHover-onClick>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_set_reminder', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function reminderForm( $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form 
IPSCONTENT;

if ( \IPS\Widget\Request::i()->isAjax()  ):
$return .= <<<IPSCONTENT
data-controller='calendar.front.view.reminderForm'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--reminder" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
 data-ipsForm >
	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
	
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

	<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

		<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="plupload" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( md5( mt_rand() ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="i-padding_3">
		<h2 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h2>
		<br><br>
		<ul>
			
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \is_string( $input ) ):
$return .= <<<IPSCONTENT

			{$input}
			<hr class='ipsHr'>
			
IPSCONTENT;

elseif ( $input instanceof \IPS\Helpers\Form\Checkbox ):
$return .= <<<IPSCONTENT

			{$input->html($form)}
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			{$input->rowHtml($form)}
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</div>
	<div class="i-background_3 i-padding_3">
		{$actionButtons[0]} 
IPSCONTENT;

if ( isset( $actionButtons[1] ) ):
$return .= <<<IPSCONTENT
{$actionButtons[1]}
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>
</form>
IPSCONTENT;

		return $return;
}

	function reviews( $event ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.front.core.commentFeed' 
IPSCONTENT;

if ( \IPS\Settings::i()->auto_polling_enabled ):
$return .= <<<IPSCONTENT
data-autoPoll
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-commentsType='reviews' data-baseURL='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $event->isLastPage('reviews') ):
$return .= <<<IPSCONTENT
data-lastPage
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-feedID='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->reviewFeedId, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' id='reviews'>
	
IPSCONTENT;

if ( $event->reviewForm() ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $event->locked() ):
$return .= <<<IPSCONTENT

			<strong class='i-color_warning'><i class='fa-solid fa-circle-info'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'item_locked_can_review', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div id='elEventReviewForm'>
			{$event->reviewForm()}
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $event->hasReviewed() ):
$return .= <<<IPSCONTENT

			<!-- Already reviewed -->
		
IPSCONTENT;

elseif ( $event->locked() ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->commentUnavailable( 'item_locked_cannot_review' );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedin()->restrict_post ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->restrict_post == -1 ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'restricted_cannot_comment', \IPS\Member::loggedIn()->warnings(5,NULL,'rpa'), \IPS\Member::loggedIn()->restrict_post );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

elseif ( \IPS\Member::loggedIn()->members_bitoptions['unacknowledged_warnings'] ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'front' )->reviewUnavailable( 'unacknowledged_warning_cannot_post', \IPS\Member::loggedIn()->warnings( 1, FALSE ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \count( $event->reviews( NULL, NULL, NULL, 'desc', NULL, NULL, NULL, NULL, isset( \IPS\Widget\Request::i()->showDeleted ) ) ) ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !$event->hasReviewed() ):
$return .= <<<IPSCONTENT
<hr class='ipsHr'>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsButtonBar ipsButtonBar--top">
			
IPSCONTENT;

if ( $event->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

				<div class="ipsButtonBar__pagination">
					{$event->reviewPagination( array( 'tab', 'sort' ) )}
				</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtonBar__end'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimodHeader( $event, '#reviews', 'review' );
$return .= <<<IPSCONTENT

				<ul class='ipsDataFilters'>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->setQueryString( array( 'tab' => 'reviews', 'sort' => 'helpful' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->sort ) or \IPS\Widget\Request::i()->sort != 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'most_helpful', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
					<li data-action="tableFilter">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->setQueryString( array( 'tab' => 'reviews','sort' => 'newest' ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsDataFilters__button 
IPSCONTENT;

if ( isset( \IPS\Widget\Request::i()->sort ) and \IPS\Widget\Request::i()->sort == 'newest' ):
$return .= <<<IPSCONTENT
ipsDataFilters__button--active
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" data-action="filterClick">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'newest', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
			</div>
		</div>
		<div data-role='commentFeed' data-controller='core.front.core.moderation'>
			<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->csrf()->setQueryString( 'do', 'multimodReview' )->setPage('page',\IPS\Request::i()->page), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsPageAction data-role='moderationTools'>
				
IPSCONTENT;

$reviewCount=0; $timeLastRead = $event->timeLastRead(); $lined = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $event->reviews() as $review ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$lined and $timeLastRead and $timeLastRead->getTimestamp() < $review->mapped('date') ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $lined = TRUE and $reviewCount ):
$return .= <<<IPSCONTENT

							<hr class="ipsUnreadBar">
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$reviewCount++;
$return .= <<<IPSCONTENT

					{$review->html()}
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->commentMultimod( $event, 'review' );
$return .= <<<IPSCONTENT

			</form>
		</div>
		
IPSCONTENT;

if ( $event->reviewPageCount() > 1 ):
$return .= <<<IPSCONTENT

			<div class="ipsButtonBar ipsButtonBar--bottom">
				<div class="ipsButtonBar__pagination">
					{$event->reviewPagination( array( 'tab', 'sort' ) )}
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

elseif ( !$event->canReview() ):
$return .= <<<IPSCONTENT

		<p class="ipsEmptyMessage" data-role="noReviews">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_reviews', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function rsvpControls( $event, $attendees ) {
		$return = '';
		$return .= <<<IPSCONTENT

<!-- Don't use IDs here, this template is called twice in view -->

IPSCONTENT;

if ( \IPS\Member::loggedIn()->member_id AND ( isset( $attendees[0][ \IPS\Member::loggedIn()->member_id ] ) OR isset( $attendees[1][ \IPS\Member::loggedIn()->member_id ] ) OR ( isset( $attendees[2][ \IPS\Member::loggedIn()->member_id ] ) ) AND \count( $attendees[1] ) < $event->rsvp_limit ) ):
$return .= <<<IPSCONTENT

	<div class='
IPSCONTENT;

if ( isset( $attendees[1][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT
i-background_positive
IPSCONTENT;

elseif ( isset( $attendees[0][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT
i-background_negative
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
i-background_1
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 i-padding_2 i-margin_1 i-border-radius_box'>
		<p class='i-font-size_2 i-text-align_center'>
			<strong>
				
IPSCONTENT;

if ( isset( $attendees[1][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT

					<span><i class='fa-solid fa-check-circle'></i> 
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_were_going', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_are_going', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

elseif ( isset( $attendees[0][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT

					<span>
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_werent_going', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'you_arent_going', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</span>
				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( !$event->hasPassed() OR !\IPS\Settings::i()->calendar_block_past_changes ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'confirm_attendance', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rsvp_past_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</strong>
		</p>
		
IPSCONTENT;

if ( !$event->hasPassed() OR !\IPS\Settings::i()->calendar_block_past_changes ):
$return .= <<<IPSCONTENT

		<ul class="ipsButtons ipsButtons--small i-margin-top_2">
			
IPSCONTENT;

if ( isset( $attendees[2][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT

				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'yes' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--positive'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attend_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
				<li>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'no' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--negative'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_notgoing_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

elseif ( isset( $attendees[0][ \IPS\Member::loggedIn()->member_id ] ) ):
$return .= <<<IPSCONTENT

				<li class='i-text-align_center'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'leave' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_change', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<li class='i-text-align_center'>
					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'leave' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_leave_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</li>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</ul>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

elseif ( $event->can('rsvp') ):
$return .= <<<IPSCONTENT

	<div class='i-padding_2 i-border-bottom_2'>
		
IPSCONTENT;

if ( $event->hasPassed() AND \IPS\Settings::i()->calendar_block_past_changes ):
$return .= <<<IPSCONTENT

			<div class='ipsMessage ipsMessage--info'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_rsvp_past_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

elseif ( $event->rsvp_limit > 0 AND \count($attendees[1]) >= $event->rsvp_limit ):
$return .= <<<IPSCONTENT

			<div class='ipsMessage ipsMessage--info'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_limit_reached', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->rsvp_limit > 0 ):
$return .= <<<IPSCONTENT
<div class='ipsMessage ipsMessage--info i-margin-bottom_3'>
IPSCONTENT;

$sprintf = array($event->rsvp_limit); $pluralize = array( \count($attendees[1]) ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_limit_info', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class='ipsButtons ipsButtons--fill'>
				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'yes' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary ipsButton--small ipsButton--wide'><i class="fa-solid fa-check" aria-hidden="true"></i> 
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attended_past_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_attend_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

if ( $event->rsvp_limit == -1 AND !$event->hasPassed() ):
$return .= <<<IPSCONTENT

					<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'maybe' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small'><i class="fa-solid fa-question" aria-hidden="true"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_maybe_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('rsvp')->setQueryString( 'action', 'no' )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit ipsButton--small'><i class="fa-solid fa-xmark" aria-hidden="true"></i> 
IPSCONTENT;

if ( $event->hasPassed() ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_notattended_past_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'rsvp_notgoing_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
</a>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function view( $event, $commentsAndReviews, $attendees, $address=NULL, $reminder=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT



IPSCONTENT;

if ( $club = $event->container()->club() ):
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( \IPS\Settings::i()->clubs and \IPS\Settings::i()->clubs_header == 'full' ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "clubs", "core" )->header( $club, $event->container() );
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div id="elClubContainer">

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


<div class="ipsBlockSpacer">
	<header id="elEventHeader" class="ipsPageHeader ipsBox ipsPull">{$event->coverPhoto()}</header>

	<section>
		
IPSCONTENT;

if ( $event->hidden() === 1 and $event->canUnhide() ):
$return .= <<<IPSCONTENT

		<div class="ipsMessage ipsMessage--warning i-margin-top_3">
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_pending_approval', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			<ul class="ipsList ipsList--inline i-margin-top_3">
				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'unhide' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--positive ipsButton--small" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve_title_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"><i class="fa-solid fa-check"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'approve', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

if ( $event->canDelete() ):
$return .= <<<IPSCONTENT

				<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url()->csrf()->setQueryString( array( 'do' => 'moderate', 'action' => 'delete' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-confirm title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'calendar_delete_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" class="ipsButton ipsButton--negative ipsButton--small"><i class="fa-solid fa-xmark"></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</ul>
		</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->contentItemMessages( $event->getMessages(), $event );
$return .= <<<IPSCONTENT

        <div id="sidebarWrapper">
		
IPSCONTENT;

if ( $event->rsvp || $address || $event->map( 270, 270 ) || $event->online || $event->_livetopic_id  ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebarWrapper:before", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="sidebarWrapper" class="ipsColumns">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebarWrapper:inside-start", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT

				<div class="ipsColumns__primary">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsBox ipsPull">
				<div class="i-padding_3">

					<div class="ipsTitle ipsTitle--h4">
						<time datetime="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $event->_start_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ):
$return .= <<<IPSCONTENT

								<span data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</span>
							
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

								<span data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
									
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'startDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</span>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</time>
						
IPSCONTENT;

if ( $event->_end_date ):
$return .= <<<IPSCONTENT

							<i class="fa-solid fa-arrow-right-long i-margin-start_icon i-margin-end_icon"></i>
							<time datetime="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( $event->_end_date->format( 'Y-m-d' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

$sameDay = !( ($event->_start_date->mday != $event->_end_date->mday) or ($event->_start_date->mon != $event->_end_date->mon) or ($event->_start_date->year != $event->_end_date->year) );
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $endDate = $event->nextOccurrence( $event->nextOccurrence( \IPS\calendar\Date::getDate(), 'startDate' ) ?: \IPS\calendar\Date::getDate(), 'endDate' ) ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( !$sameDay ):
$return .= <<<IPSCONTENT

										<span data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
, 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT

										<span data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
											
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $endDate->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

										</span>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<span data-controller="core.global.core.datetime" data-time="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->format('c'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-format="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::calendarDateFormat(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\calendar\Date::localeTimeFormat( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
										
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->calendarDate(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !$event->all_day ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->lastOccurrence( 'endDate' )->localeTime( FALSE ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</span>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</time>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					
IPSCONTENT;

if ( $event->recurring ):
$return .= <<<IPSCONTENT

						<p class="i-color_soft i-font-size_2 i-font-weight_500 i-margin-top_1"><i class="fa-solid fa-repeat"></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->_recurring_text, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</p>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
			
					
					<div class="ipsEntry__content js-ipsEntry__content i-margin-top_3">
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->richText( $event->content() );
$return .= <<<IPSCONTENT

					</div>

					
IPSCONTENT;

if ( $event->_album ):
$return .= <<<IPSCONTENT

						<div class="i-background_2 i-padding_3 i-margin-top_3">
							<h3 class="ipsTitle ipsTitle--h4 i-margin-bottom_2">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'event_images', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
							{$event->_album}
						</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


					
IPSCONTENT;

if ( $event->editLine() ):
$return .= <<<IPSCONTENT

						<div class="i-margin-top_3 i-color_soft i-font-size_-1 ipsEdited">
                        	{$event->editLine()}
                        </div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
				<div class="ipsEntry__footer">
					<menu class="ipsEntry__controls">
						<li>
							{$event->menu()}
						</li>
						<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->url('download'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" rel="noindex nofollow" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_ical_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'download_ical', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

if ( $event->venue() and $event->venue()->can( 'view' ) ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $event->venue()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$sprintf = array($event->venue()->_title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'calendar_more_events_at_x', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						
IPSCONTENT;

if ( $event->canCopyEvent() ):
$return .= <<<IPSCONTENT

							<li><a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=calendar&module=calendar&controller=submit&do=copy&event_id={$event->id}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
" rel="noindex nofollow">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'add_similar_event', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</menu>
					
IPSCONTENT;

if ( \IPS\IPS::classUsesTrait( $event, 'IPS\Content\Reactable' ) and \IPS\Settings::i()->reputation_enabled ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->reputation( $event );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</div>
			</div>
		
IPSCONTENT;

if ( $event->rsvp || $address || $event->map( 270, 270 ) || ( $event->online and $event->url and !$event->hasPassed() ) || $event->_livetopic_id ):
$return .= <<<IPSCONTENT

				</div>
				<aside class="ipsColumns__secondary i-basis_360">
					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebar:before", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="sidebar">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebar:inside-start", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", \IPS\Request::i()->app )->eventSidebar( $event, $attendees, 'Mob', $address );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebar:inside-end", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebar:after", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT

				</aside>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebarWrapper:inside-end", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "calendar/front/view/view", "sidebarWrapper:after", [ $event,$commentsAndReviews,$attendees,$address,$reminder ] );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

        </div>
	</section>

	<div class="ipsBox i-padding_2 ipsPull ipsResponsive_showPhone">
		<div class="ipsPageActions">
			
IPSCONTENT;

if ( \count( $event->shareLinks() ) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "sharelinks", "core" )->shareButton( $event, 'verySmall', 'light' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $event->canRemind() ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "view", "calendar" )->reminder( $event, $reminder, 'ipsButton--wide' );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Application::appIsEnabled('cloud') ):
$return .= <<<IPSCONTENT

            	
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "spam", "cloud" )->spam( $event );
$return .= <<<IPSCONTENT

            
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->follow( 'calendar', 'event', $event->id, $event->followersCount() );
$return .= <<<IPSCONTENT

		</div>
	</div>

	
IPSCONTENT;

if ( $commentsAndReviews ):
$return .= <<<IPSCONTENT

		<br>
		
IPSCONTENT;

if ( $event->container()->allow_reviews && $event->container()->allow_comments ):
$return .= <<<IPSCONTENT

			<a id="replies"></a>
			<h2 class="ipsHide">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'user_feedback', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		<div class="ipsBox ipsPull">
			{$commentsAndReviews}
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>


IPSCONTENT;

if ( $event->container()->club() ):
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}