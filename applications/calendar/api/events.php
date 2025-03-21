<?php
/**
 * @brief		Calendar Events API
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		8 Dec 2015
 */

namespace IPS\calendar\api;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Api\Exception;
use IPS\Api\PaginatedResponse;
use IPS\Api\Response;
use IPS\Api\Webhook;
use IPS\calendar\Calendar;
use IPS\calendar\Date;
use IPS\calendar\Event;
use IPS\calendar\Event\Comment;
use IPS\calendar\Event\Review;
use IPS\Content\Api\ItemController;
use IPS\Content\Item;
use IPS\DateTime;
use IPS\Db;
use IPS\GeoLocation;
use IPS\IPS;
use IPS\Member;
use IPS\Request;
use IPS\Text\Parser;
use OutOfRangeException;
use function count;
use function defined;
use function in_array;
use function intval;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Calendar Events API
 */
class events extends ItemController
{
	/**
	 * Class
	 */
	protected string $class = 'IPS\calendar\Event';
	
	/**
	 * GET /calendar/events
	 * Get list of events
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, only events the authorized user can view will be included
	 * @apiparam	string	ids			    Comma-delimited list of event IDs
	 * @apiparam	string	calendars		Comma-delimited list of calendar IDs
	 * @apiparam	string	authors			Comma-delimited list of member IDs - if provided, only events started by those members are returned
	 * @apiparam	int		locked			If 1, only events which are locked are returned, if 0 only unlocked
	 * @apiparam	int		hidden			If 1, only events which are hidden are returned, if 0 only not hidden
	 * @apiparam	int		featured		If 1, only events which are featured are returned, if 0 only not featured
	 * @apiparam	string 	rangeStart		YYYY-MM-DD. Only events occurring on or after this date will be returned.
	 * @apiparam	string 	rangeEnd		YYYY-MM-DD. Only events occurring on or before this date will be returned.
	 * @apiparam	string	sortBy			What to sort by. Can be 'date' for creation date, 'start' for event start date, 'end' for event end date, 'title' or leave unspecified for ID
	 * @apiparam	string	sortDir			Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page			Page number
	 * @apiparam	int		perPage			Number of results per page - defaults to 25
	 * @apireturn		PaginatedResponse<IPS\calendar\Event>
	 * @throws		1L296/M	INVALID_DATE	The rangeStart and/or rangeEnd value was not a valid date in YYYY-MM-DD format
	 * @return PaginatedResponse<Event>
	 */
	public function GETindex(): PaginatedResponse
	{
		/* Where clause */
		$where = array();

		/* Are we limiting to a date range? */
		if( isset( Request::i()->rangeStart ) OR isset( Request::i()->rangeEnd ) )
		{
			$startDate = $endDate = NULL;

			foreach( array( 'start', 'end' ) as $limiter )
			{
				$inputKey = 'range' . IPS::mb_ucfirst( $limiter );

				if( Request::i()->$inputKey )
				{
					$datePieces = explode( '-', Request::i()->$inputKey );

					/* Let's make sure the date is valid... */
					if( @checkdate( $datePieces[1], $datePieces[2], $datePieces[0] ) )
					{
						if( $limiter === 'start' )
						{
							$startDate	= Date::getDate( $datePieces[0], $datePieces[1], $datePieces[2] );
						}
						else
						{
							$endDate	= Date::getDate( $datePieces[0], $datePieces[1], $datePieces[2], 23, 59, 59 );
						}
					}
					else
					{
						throw new Exception( 'INVALID_DATE', '1L296/M', 403 );
					}
				}
			}

			/* Get the events within this range */
			$events		= Event::retrieveEvents( $startDate, $endDate, NULL, NULL, FALSE, new Member, NULL, TRUE );

			if( !count( $events ) )
			{
				/* Force no results */
				$where[] = array( '0=1' );
			}
			else
			{
				$where[] = array( 'event_id IN(' . implode( ',', array_map( function( $event ) { return $event->id; }, $events ) ) . ')' );
			}
		}

		/* Sort by */
		$sortBy = NULL;

		if ( isset( Request::i()->sortBy ) and in_array( Request::i()->sortBy, array( 'start', 'end' ) ) )
		{
			$sortBy = 'event_' . Request::i()->sortBy . '_date';
		}
				
		/* Return */
		return $this->_list( $where, 'calendars', FALSE, $sortBy );
	}
	
	/**
	 * GET /calendar/events/{id}
	 * View information about a specific event
	 *
	 * @param		int		$id			ID Number
	 * @throws		1F294/1	INVALID_ID	The event ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		\IPS\calendar\Event
	 * @return Response
	 */
	public function GETitem( int $id ): Response
	{
		try
		{
			return $this->_view( $id );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2L296/1', 404 );
		}
	}
	
	/**
	 * POST /calendar/events
	 * Create an event
	 *
	 * @note	For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock events).
	 * @reqapiparam	int					calendar		The ID number of the calendar the event should be created in
	 * @apiparam	int					author			The ID number of the member creating the event (0 for guest). Required for requests made using an API Key or the Client Credentials Grant Type. For requests using an OAuth Access Token for a particular member, that member will always be the author
	 * @reqapiparam	string				title			The event title
	 * @reqapiparam	string				description		The description as HTML (e.g. "<p>This is an event.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @reqapiparam	datetime			start			The event start date/time
	 * @apiparam	datetime			end				The event end date/time
	 * @apiparam	string				recurrence		If this event recurs, the ICS recurrence definition
	 * @apiparam	bool				rsvp			If this event accepts RSVPs
	 * @apiparam	int					rsvpLimit		The number of RSVPs the event is limited to
	 * @apiparam	\IPS\GeoLocation	location		The location where the event is taking place
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	datetime			date			The date/time that should be used for the event/post post date. If not provided, will use the current date/time. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	string				ip_address		The IP address that should be stored for the event/post. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked			1/0 indicating if the event should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					featured		1/0 indicating if the event should be featured
	 * @apiparam	bool				anonymous		If 1, the item will be posted anonymously.
	 * @throws		1L296/6				NO_CALENDAR		The calendar ID does not exist
	 * @throws		1L296/7				NO_AUTHOR		The author ID does not exist
	 * @throws		1L296/8				NO_TITLE		No title was supplied
	 * @throws		1L296/9				NO_DESC			No description was supplied
	 * @throws		1L296/A				INVALID_START	The start date is invalid
	 * @throws		1L296/B				INVALID_END		The end date is invalid
	 * @throws		2L296/C				NO_PERMISSION	The authorized user does not have permission to create an event in that calendar
	 * @apireturn		\IPS\calendar\Event
	 * @return Response
	 */
	public function POSTindex(): Response
	{
		/* Get calendar */
		try
		{
			$calendar = Calendar::load( Request::i()->calendar );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'NO_CALENDAR', '1L296/6', 400 );
		}
		
		/* Get author */
		if ( $this->member )
		{
			if ( !$calendar->can( 'add', $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2L296/C', 403 );
			}
			$author = $this->member;
		}
		else
		{
			if ( Request::i()->author )
			{
				$author = Member::load( Request::i()->author );
				if ( !$author->member_id )
				{
					throw new Exception( 'NO_AUTHOR', '1L296/7', 400 );
				}
			}
			else
			{
				if ( (int) Request::i()->author === 0 )
				{
					$author = new Member;
				}
				else 
				{
					throw new Exception( 'NO_AUTHOR', '1L296/7', 400 );
				}
			}
		}
		
		/* Check we have a title and a description */
		if ( !Request::i()->title )
		{
			throw new Exception( 'NO_TITLE', '1L296/8', 400 );
		}
		if ( !Request::i()->description )
		{
			throw new Exception( 'NO_DESC', '1L296/9', 400 );
		}
		
		/* Validate dates */
		try
		{
			new DateTime( Request::i()->start );
		}
		catch ( \Exception $e )
		{
			throw new Exception( 'INVALID_START', '1L296/A', 400 );
		}
		if ( isset( Request::i()->end ) )
		{
			try
			{
				new DateTime( Request::i()->end );
			}
			catch ( \Exception $e )
			{
				throw new Exception( 'INVALID_END', '1L296/B', 400 );
			}
		}
		
		/* Do it */
		return new Response( 201, $this->_create( $calendar, $author )->apiOutput( $this->member ) );
	}
	
	/**
	 * POST /calendar/events/{id}
	 * Edit an event
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, any parameters the user doesn't have permission to use are ignored (for example, locked will only be honoured if the authenticated user has permission to lock topics).
	 * @reqapiparam	int					calendar		The ID number of the calendar the event should be created in
	 * @reqapiparam	int					author			The ID number of the member creating the event (0 for guest). Ignored for requests using an OAuth Access Token for a particular member.
	 * @reqapiparam	string				title			The event title
	 * @reqapiparam	string				description		The description as HTML (e.g. "<p>This is an event.</p>"). Will be sanatized for requests using an OAuth Access Token for a particular member; will be saved unaltered for requests made using an API Key or the Client Credentials Grant Type.
	 * @reqapiparam	datetime			start			The event start date/time
	 * @apiparam	datetime			end				The event end date/time
	 * @apiparam	string				recurrence		If this event recurs, the ICS recurrence definition
	 * @apiparam	bool				rsvp			If this event accepts RSVPs
	 * @apiparam	int					rsvpLimit		The number of RSVPs the event is limited to
	 * @apiparam	\IPS\GeoLocation	location		The location where the event is taking place
	 * @apiparam	string				prefix			Prefix tag
	 * @apiparam	string				tags			Comma-separated list of tags (do not include prefix)
	 * @apiparam	string				ip_address		The IP address that should be stored for the event/post. If not provided, will use the IP address from the API request. Ignored for requests using an OAuth Access Token for a particular member.
	 * @apiparam	int					locked			1/0 indicating if the event should be locked
	 * @apiparam	int					hidden			0 = unhidden; 1 = hidden, pending moderator approval; -1 = hidden (as if hidden by a moderator)
	 * @apiparam	int					featured		1/0 indicating if the event should be featured
	 * @apiparam	bool				anonymous		If 1, the item will be posted anonymously.
	 * @param int $id
	 * @throws		1L296/I				INVALID_ID		The event ID is invalid or the authorized user does not have permission to view it
	 * @throws		1L296/D				NO_CALENDAR		The calendar ID does not exist or the authorized user does not have permission to post in it
	 * @throws		1L296/E				NO_AUTHOR		The author ID does not exist
	 * @throws		1L296/G				INVALID_START	The start date is invalid
	 * @throws		1L296/H				INVALID_END		The end date is invalid
	 * @throws		2L296/D				NO_PERMISSION	The authorized user does not have permission to edit the topic
	 * @apireturn		\IPS\calendar\Event
	 * @return Response
	 */
	public function POSTitem( int $id ): Response
	{
		try
		{
			$event = Event::load( $id );
			if ( $this->member and !$event->can( 'read', $this->member ) )
			{
				throw new OutOfRangeException;
			}
			if ( $this->member and !$event->canEdit( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2L296/D', 403 );
			}
			
			/* New calendar */
			if ( isset( Request::i()->calendar ) and Request::i()->calendar != $event->calendar_id and ( !$this->member or $event->canMove( $this->member ) ) )
			{
				try
				{
					$newCalendar = Calendar::load( Request::i()->calendar );
					if ( $this->member and !$newCalendar->can( 'add', $this->member ) )
					{
						throw new OutOfRangeException;
					}
					
					$event->move( $newCalendar );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_CALENDAR', '1L296/D', 400 );
				}
			}
			
			/* New author */
			if ( !$this->member and isset( Request::i()->author ) )
			{				
				try
				{
					$member = Member::load( Request::i()->author );
					if ( !$member->member_id )
					{
						throw new OutOfRangeException;
					}
					
					$event->changeAuthor( $member );
				}
				catch ( OutOfRangeException $e )
				{
					throw new Exception( 'NO_AUTHOR', '1L296/E', 400 );
				}
			}
			
			/* Validate dates */
			if ( isset( Request::i()->start ) )
			{
				try
				{
					new DateTime( Request::i()->start );
				}
				catch ( \Exception $e )
				{
					throw new Exception( 'INVALID_START', '1L296/G', 400 );
				}
			}
			if ( isset( Request::i()->end ) )
			{
				try
				{
					new DateTime( Request::i()->end );
				}
				catch ( \Exception $e )
				{
					throw new Exception( 'INVALID_END', '1L296/H', 400 );
				}
			}
			
			/* Everything else */
			$this->_createOrUpdate( $event, 'edit' );
			
			/* Save and return */
			$event->save();
			return new Response( 200, $event->apiOutput( $this->member ) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '1L296/D', 404 );
		}
	}
	
	/**
	 * GET /calendar/events/{id}/comments
	 * Get comments on an event
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2L296/2	INVALID_ID	The event ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		PaginatedResponse<IPS\calendar\Event\Comment>
	 * @return PaginatedResponse<Comment>
	 */
	public function GETitem_comments( int $id ): PaginatedResponse
	{
		try
		{
			return $this->_comments( $id, 'IPS\calendar\Event\Comment' );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2L296/2', 404 );
		}
	}
	
	/**
	 * GET /calendar/events/{id}/reviews
	 * Get reviews on an event
	 *
	 * @param		int		$id			ID Number
	 * @apiparam	int		hidden		If 1, only comments which are hidden are returned, if 0 only not hidden
	 * @apiparam	string	sortDir		Sort direction. Can be 'asc' or 'desc' - defaults to 'asc'
	 * @apiparam	int		page		Page number
	 * @apiparam	int		perPage		Number of results per page - defaults to 25
	 * @throws		2L296/3	INVALID_ID	The event ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		PaginatedResponse<IPS\calendar\Event\Review>
	 * @return PaginatedResponse<Review>
	 */
	public function GETitem_reviews( int $id ): PaginatedResponse
	{
		try
		{
			return $this->_comments( $id, 'IPS\calendar\Event\Review' );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2L296/3', 404 );
		}
	}
	
	/**
	 * GET /calendar/events/{id}/rsvps
	 * Get RSVPs on an event
	 *
	 * @param		int				$id				ID Number
	 * @throws		2L296/3			INVALID_ID		The event ID does not exist or the authorized user does not have permission to view it
	 * @apireturn		array
	 * @apiresponse	[\IPS\Member]	attending		Members that have confirmed they are attending the event
	 * @apiresponse	[\IPS\Member]	notAttending	Members that have confirmed they are not attending the event
	 * @apiresponse	[\IPS\Member]	maybeAttending	Members that have said they may attend the event
	 * @return Response
	 */
	public function GETitem_rsvps( int $id ): Response
	{
		try
		{
			$event = Event::load( $id );
			if ( $this->member and !$event->can( 'read', $this->member ) )
			{
				throw new OutOfRangeException;
			}
			
			$attendees = $event->attendees();
			return new Response( 200, array(
				'attending'			=> array_values( array_map( function( $member ) {
					return $member->apiOutput( $this->member );
				}, $attendees[1] ) ),
				'notAttending'		=> array_values( array_map( function( $member ) {
					return $member->apiOutput( $this->member );
				}, $attendees[0] ) ),
				'maybeAttending'	=> array_values( array_map( function( $member ) {
					return $member->apiOutput( $this->member );
				}, $attendees[2] ) ),
			) );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2L296/4', 404 );
		}
	}
	
	/**
	 * PUT /calendar/events/{id}/rsvps/{member_id}
	 * RSVP a member to an event
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, the member ID must be the authorized member's ID
	 * @reqapiparam	int				response		0 = Not attending; 1 = attending; 2 = maybe attending
	 * @param		int				$id				Event ID NUmber
	 * @param		int				$memberId		Member ID NUmber
	 * @throws		2L296/J			INVALID_ID		The event ID does not exist
	 * @throws		2L296/K			INVALID_MEMBER	The member ID was not valid
	 * @apireturn		void
	 * @return Response
	 */
	public function PUTitem_rsvps( int $id, int $memberId ): Response
	{
		if ( !isset( Request::i()->response ) or !in_array( (int) Request::i()->response, range( 0, 2 ) ) )
		{
			throw new Exception( 'INVALID_RESPONSE', '1L296/L', 400 );
		}
		
		if ( $this->member and $memberId != $this->member->member_id )
		{
			throw new Exception( 'INVALID_MEMBER', '2L296/K', 404 );
		}
		
		try
		{
			$event = Event::load( $id );
			
			try
			{
				$member = Member::load( $memberId );
				if ( !$member->member_id )
				{
					throw new OutOfRangeException;
				}
			}
			catch ( OutOfRangeException $e )
			{
				throw new Exception( 'INVALID_MEMBER', '2L296/K', 404 );
			}
			
			Db::i()->delete( 'calendar_event_rsvp', array( 'rsvp_event_id=? AND rsvp_member_id=?', $event->id, $member->member_id ) );
			
			Db::i()->insert( 'calendar_event_rsvp', array(
				'rsvp_event_id'		=> $event->id,
				'rsvp_member_id'	=> $member->member_id,
				'rsvp_date'			=> time(),
				'rsvp_response'		=> (int) Request::i()->response
			) );

			$webhookData = [
				'event' => $event->apiOutput(),
				'action' => (int) Request::i()->response,
				'attendee' => $member->apiOutput(),
			];
			

			Webhook::fire( 'calendarEvent_rsvp', $webhookData );

			$member->achievementAction( 'calendar', 'Rsvp', $event );
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_EVENT', '2L296/J', 404 );
		}
	}
	
	/**
	 * DELETE /calendar/events/{id}/rsvps/{member_id}
	 * Remove a member from RSVP list
	 *
	 * @note		For requests using an OAuth Access Token for a particular member, the member ID must be the authorized member's ID
	 * @param		int		$id				Event ID NUmber
	 * @param		int		$memberId		Member ID NUmber
	 * @apireturn		void
	 * @throws		2L296/K			INVALID_MEMBER	The member ID was not valid
	 * @return Response
	 */
	public function DELETEitem_rsvps( int $id, int $memberId ): Response
	{
		if ( $this->member and $memberId != $this->member->member_id )
		{
			throw new Exception( 'INVALID_MEMBER', '2L296/K', 404 );
		}
		
		Db::i()->delete( 'calendar_event_rsvp', array( 'rsvp_event_id=? AND rsvp_member_id=?', $id, $memberId ) );
		return new Response( 200, NULL );
	}
	
	/**
	 * Create or update event
	 *
	 * @param	Item	$item	The item
	 * @param	string				$type	add or edit
	 * @return	Item
	 */
	protected function _createOrUpdate( Item $item, string $type='add' ): Item
	{
		/* Start/End date */
		$startDate = new DateTime( Request::i()->start );
		$item->start_date = $startDate->format( 'Y-m-d H:i' );
		$item->end_date = NULL;
		if ( isset( Request::i()->end ) )
		{
			$endDate = new DateTime( Request::i()->end );
			$item->end_date = $endDate->format( 'Y-m-d H:i' );
		}
		else
		{
			$item->all_day = 1;
		}
		
		/* Recurrence */
		if ( isset( Request::i()->recurrence ) )
		{
			$item->recurring = Request::i()->recurrence;
		}
		
		/* Description */
		$descriptionContents = Request::i()->description;
		if ( $this->member )
		{
			$descriptionContents = Parser::parseStatic( $descriptionContents, NULL, $this->member, 'calendar_Calendar' );
		}
		$item->content = $descriptionContents;
		
		/* RSVP */
		if ( isset( Request::i()->rsvp ) and ( !$this->member or $item->container()->can( 'askrsvp', $this->member ) ) )
		{
			$item->rsvp = intval( Request::i()->rsvp );
			if ( $item->rsvp and isset( Request::i()->rsvpLimit ) and Request::i()->rsvpLimit )
			{
				$item->rsvp_limit = Request::i()->rsvpLimit;
			}
			else
			{
				$item->rsvp_limit = -1;
			}
		}
		
		/* Location */
		if ( isset( Request::i()->location ) )
		{
			if ( Request::i()->location )
			{
				$location = GeoLocation::buildFromJson( json_encode( Request::i()->location ) );
				if ( !$location->lat or !$location->long )
				{
					try
					{
						$location->getLatLong();
					}
					catch ( \Exception $e ) {}
				}
				$item->location = json_encode( $location );
			}
		}
		
		/* Pass up */
		return parent::_createOrUpdate( $item, $type );
	}
		
	/**
	 * DELETE /calendar/events/{id}
	 * Delete a event
	 *
	 * @param		int		$id			ID Number
	 * @throws		2L296/5	INVALID_ID	The event ID does not exist
	 * @apireturn		void
	 * @return Response
	 */
	public function DELETEitem( int $id ): Response
	{
		try
		{
			$item = Event::load( $id );
			if ( $this->member and !$item->canDelete( $this->member ) )
			{
				throw new Exception( 'NO_PERMISSION', '2F294/B', 404 );
			}
			
			$item->delete();
			
			return new Response( 200, NULL );
		}
		catch ( OutOfRangeException $e )
		{
			throw new Exception( 'INVALID_ID', '2L296/5', 404 );
		}
	}
}