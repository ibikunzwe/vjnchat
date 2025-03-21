<?php
/**
 * @brief		iCalendar ICS Parser
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	Calendar
 * @since		19 Dec 2013
 */

namespace IPS\calendar\Icalendar;

/* To prevent PHP errors (extending class does not exist) revealing path */

use BadFunctionCallException;
use BadMethodCallException;
use DateInterval;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use IPS\Api\Webhook;
use IPS\Application;
use IPS\calendar\Calendar;
use IPS\calendar\Date;
use IPS\calendar\Event;
use IPS\calendar\Icalendar;
use IPS\Content\Search\Index;
use IPS\DateTime;
use IPS\Db;
use IPS\File;
use IPS\GeoLocation;
use IPS\Http\Url;
use IPS\Member;
use IPS\Settings;
use IPS\Text\Parser;
use OutOfRangeException;
use UnderflowException;
use UnexpectedValueException;
use function count;
use function defined;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_null;
use function stripos;
use function strlen;
use function substr;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * iCalendar ICS Parser
 * @todo	[Future] Tags we do not support but may want to look into supporting: EXDATE, EXRULE, RDATE
 */
class ICSParser
{
	/**
	 * Parse an RRULE into an array of recurrence data we can use
	 *
	 * @param string $rrule		The ICS RRULE value
	 * @param string|null $timezone	Timezone to treat recurring end date as
	 * @param Date|null $startDate	If supplied, we can use the start date to help convert certain repeat rules to make them simpler
	 * @return	array
	 * @throws	InvalidArgumentException
	 * @note	Some implementations separate with : and some with ; so we have to support both
	 *@todo	[Future] We do not support the properties BYSETPOS, BYYEARDAY, BYMONTH, BYHOUR, BYMINUTE, BYSECOND, BYWEEKNO, WKST
	 * @todo	[Future] We do not support complex BYDAY values, such as 1TU or -1FR
	 * @todo	[Future] We do not support multiple values for BYMONTHDAY, nor do we support negative values
	 * @todo	[Future] We do not support HOURLY, MINUTELY or SECONDLY "FREQ" values
	 */
	public static function parseRrule( string $rrule, string $timezone=NULL, Date $startDate = NULL ): array
	{
		$rule		= array_map( 'trim', explode( ';', str_replace( ':', ';', $rrule ) ) );
		$dayNames	= array();
		$repeatData	= array(
			'event_repeat'				=> FALSE,		/* Enable/disable checkbox */
			'event_repeats'				=> NULL,		/* Daily, weekly, monthly, yearly */
			'event_repeat_freq'			=> NULL,		/* Repeat every 1 day, 2 days, 3 days, etc. */
			'repeat_end_occurrences'	=> NULL,		/* Ends after x occurrences (if this and end_date are both empty, that means repeat never ends) */
			'repeat_end_date'			=> NULL,		/* Ends on x date (which is separate from the event end date - e.g. jan 9 2014 3pm to jan 10 2014 3pm, repeat annually until jan 9 2019) */
		);

		foreach( Date::getDayNames() as $day )
		{
			$repeatData['repeat_freq_on_' . $day['ical'] ]	= NULL;		/* If repeating weekly, this is the days of the week as checkboxes (e.g. repeat every wed, fri and sat) */
			$dayNames[] = $day['ical'];
		}

		foreach( $rule as $ruleData )
		{
			$_ruleData	= explode( '=', $ruleData );

			switch( $_ruleData[0] )
			{
				default:
					throw new InvalidArgumentException( $_ruleData[0] );

				case 'WKST':
					/* We can safely ignore this when it is set to the default value of MO. We can't make assumptions in other cases. */
					if( mb_strtolower( $_ruleData[1] ) != 'mo' )
					{
						throw new InvalidArgumentException( $_ruleData[0] );
					}
				break;

				case 'FREQ':
					$frequency	= mb_strtolower( $_ruleData[1] );

					if( in_array( $frequency, array( 'daily', 'weekly', 'monthly', 'yearly' ) ) )
					{
						$repeatData['event_repeats']	= $frequency;
						$repeatData['event_repeat']		= TRUE;
					}
					else
					{
						/* We don't support less than daily*/
						throw new InvalidArgumentException( 'FREQ' );
					}
				break;

				case 'BYDAY':
					$days		= explode( ',', $_ruleData[1] );

					foreach( $days as $day )
					{
						foreach( $dayNames as $dayName )
						{
							$dayPos	= stripos( $day, $dayName );

							if( $dayPos !== FALSE )
							{
								/* We only support basic day recurrence values, not the negative/positive numbers */
								if( $dayPos > 0 )
								{
									throw new InvalidArgumentException( 'BYDAY' );
								}
								else
								{
									$repeatData['repeat_freq_on_' . $dayName ]	= TRUE;
								}

								break;
							}
						}
					}
				break;

				case 'BYMONTHDAY':
					$values	= array_map( 'trim', explode( ',', $_ruleData[1] ) );

					if( count( $values ) > 1 OR stripos( $values[0], '-' ) !== FALSE )
					{
						throw new InvalidArgumentException( 'BYMONTHDAY' );
					}
				break;

				case 'COUNT':
					$repeatData['repeat_end_occurrences']	= (int) $_ruleData[1];
				break;

				case 'INTERVAL':
					$repeatData['event_repeat_freq']		= (int) $_ruleData[1];
				break;

				case 'UNTIL':
					$repeatData['repeat_end_date']			= new Date( $_ruleData[1], $timezone ? new DateTimeZone( $timezone ) : NULL );
				break;
			}
		}

		/* If no repeat frequency specified, default to 1 (e.g. every week, every day, etc.) */
		if( $repeatData['event_repeats'] AND !$repeatData['event_repeat_freq'] )
		{
			$repeatData['event_repeat_freq']	= 1;
		}

		/* Try to make the returned rules a little "smarter" for efficiency reasons... */
		/* Convert "repeats every 14 days" into "2 weeks" */
		if( $repeatData['event_repeats'] == 'daily' AND $repeatData['event_repeat_freq'] % 7 == 0 )
		{
			$repeatData['event_repeats'] = 'weekly';
			$repeatData['event_repeat_freq'] = $repeatData['event_repeat_freq'] / 7;
		}

		/* Convert "repeats every week on Tuesday" to just "repeats every week" if the start date was Tuesday */
		if( $repeatData['event_repeats'] == 'weekly' AND $startDate !== NULL )
		{
			$_repeatDays	= array();

			foreach( Date::getDayNames() as $day )
			{
				if( $repeatData['repeat_freq_on_' . $day['ical']] )
				{
					$_repeatDays[]	= $day['english'];
				}
			}

			if( ( count( $_repeatDays ) == 1 AND $startDate->weekday == $_repeatDays[0] ) OR count( $_repeatDays ) == 7 )
			{
				foreach( $dayNames as $day )
				{
					$repeatData['repeat_freq_on_' . $day ] = NULL;
				} 
			}
		}

		return $repeatData;
	}

	/**
	 * Create an RRULE value from an array of data
	 *
	 * @note	This is basically the opposite of parseRrule
	 * @param	array		$repeat		The event repeat data
	 * @return	string|NULL
	 *@see		self::parseRrule()
	 */
	public static function buildRrule( array $repeat ): ?string
	{
		/* If checkbox was not checked, just return */
		if( !isset( $repeat['event_repeat'] ) OR !$repeat['event_repeat'] )
		{
			return NULL;
		}

		/* The basics - always expected */
		$rrule	= array(
			'FREQ=' . mb_strtoupper( $repeat['event_repeats'] ),
			'INTERVAL=' . $repeat['event_repeat_freq']
		);

		/* Other possible properties - not all will be present */
		if( isset( $repeat['repeat_end_occurrences'] ) AND $repeat['repeat_end_occurrences'] )
		{
			$rrule[]	= 'COUNT=' . $repeat['repeat_end_occurrences'];
		}

		if( isset( $repeat['repeat_end_date'] ) AND $repeat['repeat_end_date'] )
		{
			$rrule[]	= 'UNTIL=' . Date::createFromForm( $repeat['repeat_end_date'], '', 'UTC' )->modifiedIso8601();
		}

		/* By-day rule */
		$days	= array();

		foreach( Date::getDayNames() as $day )
		{
			if( isset( $repeat['repeat_freq_on_' . $day['ical'] ] ) AND $repeat['repeat_freq_on_' . $day['ical'] ] )
			{
				$days[]	= $day['ical'];
			}
		}

		if( count( $days ) )
		{
			$rrule[]	= 'BYDAY=' . implode( ',', $days );
		}

		return implode( ';', $rrule );
	}

	/**
	 * @brief	Calendar we are importing to
	 */
	protected ?Calendar $calendar = NULL;

	/**
	 * @brief	Member we are importing as
	 */
	protected ?Member $member = NULL;

	/**
	 * @brief	Feed we are importing from
	 */
	protected ?Icalendar $feed = NULL;

	/**
	 * Perform some basic error checking
	 *
	 * @param string $content	The ICS contents
	 * @return	bool
	 * @throws	UnexpectedValueException
	 * @note	This is abstracted as a static method so we can perform error checking prior to saving the feed
	 */
	public static function isValid( string $content ): bool
	{
		/* Perform some basic error checking */
		if( !$content )
		{
			throw new UnexpectedValueException( "NO_CONTENT" );
		}

		$_raw	= preg_replace( "#(\n\r|\r|\n){1,}#", "\n", $content );
		$_raw	= explode( "\n", $_raw );
		
		if( !count($_raw) )
		{
			throw new UnexpectedValueException( "NO_CONTENT" );
		}
		
		if( $_raw[0] != 'BEGIN:VCALENDAR' )
		{
			throw new UnexpectedValueException( "BAD_CONTENT" );
		}

		return TRUE;
	}

	/**
	 * Parse the supplied contents (which may come from a URL or an uploaded file) and import events
	 *
	 * @param string $content	The ICS contents
	 * @param int|Calendar $calendar	The calendar to import to
	 * @param int|Member $member		The member the imported events should be 'from'
	 * @param int|null $feed		The feed we are importing (used to detect and prevent duplicate imports)
	 * @param int|null $venue		The venue if provided
	 * @return	array		Number of events imported and skipped
	 * @throws	UnexpectedValueException
	 */
	public function parse( string $content, Calendar|int $calendar, int|Member $member, int $feed=NULL, int $venue=NULL ): array
	{
		/* Load the calendar */
		$this->calendar	= ( $calendar instanceof Calendar ) ? $calendar : Calendar::load( $calendar );
		
		/* And the member */
		$this->member	= ( $member instanceof Member ) ? $member : Member::load( $member );

		/* And the feed */
		if( $feed !== NULL )
		{
			$this->feed		= Icalendar::load( $feed );
		}

		/* Perform some basic error checking */
		static::isValid( $content );

		$_raw	= preg_replace( "#(\n\r|\r|\n){1,}#", "\n", $content );
		$_raw	= explode( "\n", $_raw );

		/* Store the raw data we will parse */
		$this->_rawIcsData	= $_raw;
		
		/* Now loop and start parsing */
		foreach( $this->_rawIcsData as $k => $v )
		{
			$line	= explode( ':', $v );
			
			switch( $line[0] )
			{
				case 'BEGIN':
					$this->_parseBeginBlock( $line[1], $k );
				break;
				
				/* Unsupported at this time */
				case 'CALSCALE':
				case 'METHOD':
				case 'X-WR-TIMEZONE':
				case 'X-WR-RELCALID':
				default:
				break;
			}
		}

		/* Convert the raw ICS data to GMT now */
		if( count($this->_parsedIcsData) )
		{
			$this->_parsedIcsData	= $this->_convertToGmt( $this->_parsedIcsData );
		}

		/* Now loop over the results in order to insert */
		$_imported	= 0;
		$_skipped	= 0;
		$_newEvents = 0;
		$maxImport  = 500;
		
		// Leave this here - useful for debugging
		// print_r($this->_parsedIcsData);exit;

		if ( count( $this->_parsedIcsData ) and isset( $this->_parsedIcsData['events'] ) )
		{
			usort( $this->_parsedIcsData['events'], function( $a, $b ) {
				if ( $a['start']['raw_ts'] == $b['start']['raw_ts'] )
				{
					return 0;
				}
				
				return ( $a['start']['raw_ts'] > $b['start']['raw_ts'] ) ? -1 : 1;
			} );
			
			/* Loop over the events */
			foreach( $this->_parsedIcsData['events'] as $event )
			{
				if ( $_imported > $maxImport )
				{
					break;
				}
				
				/* Quickly, if we don't support the recurrence data provided let's just skip this event */
				if( isset( $event['recurr'] ) )
				{
					try
					{
						$rrule = static::parseRrule( $event['recurr'] );
					}
					catch( InvalidArgumentException $e )
					{
						continue;
					}
				}
				
				$event['uid']		= $event['uid'] ?: md5( implode( ',', $event['start'] ) . implode( ',', $event['end'] ) );

				/* Figure out some times */
				$event_unix_from	= $event['start']['utc_time'];
				$event_unix_to		= isset( $event['end'] ) ? $event['end']['utc_time'] : NULL;
				$event_all_day		= $event['start']['type'] == 'DATE';
				
				/* End dates in iCalendar format are "exclusive", meaning they are actually the day ahead. */
				/* @link	http://microformats.org/wiki/dtend-issue */
				/* Only adjust end date if end date is not equal too the start date already. */
				/* @see Ticket 876817 */
				
				if( $event_unix_to AND $event['end']['type'] == 'DATE' AND ( $event_unix_from->getTimestamp() != $event_unix_to->getTimestamp() ) )
				{
					$event_unix_to = $event_unix_to->sub( new DateInterval( 'P1D' ) );
				}
				
				/* It is a single day event if end date is equal to start date */
				if( $event_unix_from == $event_unix_to )
				{
					$event_unix_to	= NULL;
				}

				/* If there is a duration, calculate the end date again */
				if ( ! $event_unix_to AND isset( $event['duration'] ) AND $event['duration'] )
				{
					preg_match( "#(\d+?)H#is", $event['duration'], $match );
					$hour   = $match[1] ?? 0;

					preg_match( "#(\d+?)M#is", $event['duration'], $match );
					$minute = $match[1] ?? 0;

					preg_match( "#(\d+?)S#is", $event['duration'], $match );
					$second = $match[1] ?? 0;

					$event_unix_to	= $event_unix_from->add( new DateInterval( 'PT' . ( $hour * 3600 ) . 'H' . ( $minute * 60 ) . 'M' . $second . 'S' ) );
				}

				/* If this is an all day event, adjust the timestamps */
				if( $event_all_day )
				{
					$event_unix_from	= $event_unix_from->setTime( 0, 0, 0 );
					$event_unix_to		= $event_unix_to ? $event_unix_to->setTime( 0, 0, 0 ) : NULL;
				}

				/* If we are missing crucial data, skip this event */
				if( !$event_unix_from OR ( ( !isset( $event['description'] ) OR !$event['description'] ) AND ( !isset( $event['summary'] ) OR !$event['summary'] ) ) )
				{
					$_skipped++;
					continue;
				}
								
				/* Update previously imported events, if possible */
				$eventId	= NULL;
				$_new		= FALSE;

				if( $event['uid'] )
				{
					try
					{
						/* If we are importing an ICS file, feed here will be null. */
						if ( is_null( $this->feed ) )
						{
							/* Bubble up so we can create a new event */
							throw new UnderflowException;
						}
						
						$eventId	= Db::i()->select( 'import_event_id', 'calendar_import_map', array( array( 'import_guid=? and import_feed_id=?', $event['uid'], $this->feed->id ) ) )->first();
						
						try
						{
							$newEvent	= Event::load( $eventId );
						}
						catch( OutOfRangeException $e )
						{
							/* This event seems to have been deleted - skip it. */
							$_skipped++;
							continue;
						}

						$_skipped++;
					}
					catch( UnderflowException $e )
					{
						$newEvent	= new Event;
						$_new		= TRUE;

						/* Basics */
						$newEvent->post_key		= md5( mt_rand() );
						$newEvent->member_id	= $this->member->member_id;
						$newEvent->calendar_id	= $this->calendar->id;

						if( $venue )
						{
							$newEvent->venue = $venue;
						}

						$newEvent->approved		= 1;

						/* RSVP ? */
						$newEvent->rsvp			= ( $this->feed === NULL ) ? 1 : (int) $this->feed->allow_rsvp;

						/* Time */
						$newEvent->saved		= ( isset( $event['created'] ) AND $event['created'] AND $event['created'] < time() ) ? $event['created'] : time();
						$newEvent->lastupdated	= ( isset( $event['created'] ) AND $event['created'] AND $event['created'] < time() ) ? $event['created'] : time();
					}
				}

				/* Basics */
				$newEvent->title		= ( isset( $event['summary'] ) AND $event['summary'] ) ? $event['summary'] : mb_substr( strip_tags( $event['description'] ), 0, 100 );
				$content				= ( isset( $event['description'] ) AND $event['description'] ) ? nl2br( $event['description'] ) : $event['summary'] . ( isset( $event['location'] ) ? '<br>' . $event['location'] : '' );
				$newEvent->content		= Parser::parseStatic( $content, NULL, $this->member );
				$newEvent->sequence		= ( isset( $event['sequence'] ) ? intval( $event['sequence'] ) : 0 );

				$newEvent->all_day		= $event_all_day;
				$newEvent->recurring	= ( isset( $event['recurr'] ) ) ? $event['recurr'] : NULL;
				$newEvent->start_date	= $event_unix_from->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
				$newEvent->end_date		= $event_unix_to ? $event_unix_to->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : NULL;

				/* Geolocation? */
				if( isset( $event['geo'] ) AND is_array( $event['geo'] ) AND count( $event['geo'] ) )
				{
					/* If we are updating an event, don't pull geo data if we already have it and it hasn't changed */
					$update = TRUE;

					if( $newEvent->location )
					{
						$existingLocation = json_decode( $newEvent->location, true );

						if( isset( $existingLocation['lat'] ) AND $existingLocation['lat'] == $event['geo']['lat'] AND isset( $existingLocation['long'] ) AND $existingLocation['long'] == $event['geo']['long'] )
						{
							$update	= FALSE;
						}
					}

					if( $update === TRUE )
					{
						try
						{
							$newEvent->location	= json_encode( GeoLocation::getByLatLong( $event['geo']['lat'], $event['geo']['long'] ) );
						}
						catch( BadFunctionCallException $e ){}
					}
				}
				elseif( isset( $event['location'] ) )
				{
					try
					{
						$location = new GeoLocation;
						$location->addressLines = array( $event['location'] );
						$location->getLatLong( TRUE );

						$newEvent->location	= json_encode( $location );
					}
					catch( BadFunctionCallException $e ){}
				}

				/* Save */
				$newEvent->save();
	 			
				/* Increment counter */
				$_imported++;

				/* Increment only if this is a new event */
				if( $_new )
				{
					Webhook::fire( str_replace( '\\', '', substr( get_class( $newEvent ), 3 ) ) . '_create', $newEvent, $newEvent->webhookFilters() );
					$_newEvents++;
				}

				/* Add to index */
				Index::i()->index( $newEvent );
				
				/* Update map */
				if( $this->feed !== NULL AND !$eventId )
				{
					Db::i()->insert( 'calendar_import_map', array(
						'import_feed_id'	=> $this->feed->id,
						'import_event_id'	=> $newEvent->id,
						'import_guid'		=> $event['uid'],
					)	);
				}

				/* Add any event attendees that are members of our installation */
				if( !$eventId AND isset($event['attendee']) AND count($event['attendee']) )
				{
					foreach( $event['attendee'] as $attendee )
					{
						if( $attendee['email'] )
						{
							$_loadedMember	= Member::load( $attendee['email'] );
							
							if( $_loadedMember->member_id )
							{
								Db::i()->insert( 'calendar_event_rsvp', array(
									'rsvp_member_id'	=> $_loadedMember->member_id,
									'rsvp_event_id'		=> $newEvent->id,
									'rsvp_date'			=> time(),
								)	);
							}
						}
					}
				}
			}
		}

		/* Increment post counts */
		if( $_newEvents )
		{
			$this->member->member_posts	= $this->member->member_posts + $_newEvents;
			$this->member->member_last_post = time();
			$this->member->save();
		}

		/* Return the data */
		return array( 'skipped' => $_skipped, 'imported' => $_imported );
	}

	/**
	 * @brief	Array of calendar events we are adding to an iCalendar export
	 */
	protected array $_events = array();

	/**
	 * Add an event
	 *
	 * @param Event $event 	Event data
	 * @return	void
	 */
	public function addEvent( Event $event ) : void
	{
		if( $event->id )
		{
			$this->_events[ $event->id ] = $event;
		}
	}

	/**
	 * Remove an event
	 *
	 * @param int $eventId	Event id
	 * @return	void
	 */
	public function removeEvent( int $eventId ) : void
	{
		if( $eventId )
		{
			unset( $this->_events[ $eventId ] );
		}
	}

	/**
	 * Build iCalendar feed and return
	 *
	 * @param int|Calendar|null $calendar	The calendar the feed belongs to
	 * @return	string		iCalendar feed (can be downloaded or sent as webcal subscription)
	 */
	public function buildICalendarFeed( Calendar|int $calendar=NULL ): string
	{
		/* Load the calendar */
		$this->calendar	= ( $calendar instanceof Calendar ) ? $calendar : ( $calendar !== NULL ? Calendar::load( $calendar ) : NULL );

		/* Start formatting the output */
		$output	 = "BEGIN:VCALENDAR\r\n";
		$output	.= "VERSION:2.0\r\n";
		$output	.= "PRODID:-//InvisionCommunity Events " . Application::load( 'calendar' )->version . "//EN\r\n";
		$output	.= "METHOD:PUBLISH\r\n";
		$output	.= "CALSCALE:GREGORIAN\r\n";
		$output	.= "REFRESH-INTERVAL:PT15M\r\n";
		$output	.= "X-PUBLISHED-TTL:PT15M\r\n";
		if( $this->calendar !== NULL )
		{
			$output	.= "X-WR-CALNAME:" . $this->_encodeSpecialCharacters( $this->calendar->_title ) . "\r\n";
			$output	.= "NAME:" . $this->_encodeSpecialCharacters( $this->calendar->_title ) . "\r\n";
		}
		else
		{
			$text = Member::loggedIn()->language()->get('all_calendars');
			$output	.= "X-WR-CALNAME:" . $this->_encodeSpecialCharacters( $text . ' - ' . Settings::i()->board_name ) . "\r\n";
			$output	.= "NAME:" . $this->_encodeSpecialCharacters( $text . ' - ' . Settings::i()->board_name ) . "\r\n";
		}
		
		/* Add the time zones to the export */
		$output	.= $this->_addTimezones();
		
		/* Then add the events */
		$output	.= $this->_addEvents();
		
		/* Finalize the output */
		$output	.= "END:VCALENDAR\r\n";
		
		/* And return */
		return $output;
	}

	/**
	 * Build the VTIMEZONE parts of the iCalendar feed
	 *
	 * @return	string
	 */
	protected function _addTimezones(): string
	{
		/* Initialize */
		$output	= '';

		/* Get the years that all events span */
		$years	= array();
		
		if( count( $this->_events ) )
		{
			foreach( $this->_events as $event )
			{
				$years[ $event->_start_date->year ]	= $event->_start_date->year;
				
				if( $event->_end_date )
				{
					$_startTime	= $event->_start_date->getTimestamp();

					while( $_startTime < $event->_end_date->getTimestamp() )
					{
						$years[ $event->_start_date->year ]	= $event->_start_date->year;
						$years[ $event->_end_date->year ]	= $event->_end_date->year;
						
						$_startTime	+= 2592000;	// add one month
					}
				}
			}
		}
		
		/* Now add the timezones */
		foreach( $years as $year )
		{
			$_daylight_start	= strtotime( 'last Sunday of March ' . $year );
			$_standard_start	= strtotime( 'last Sunday of October ' . $year );
			$_daylight			= gmmktime( 2, 0, 0, 3 , gmdate( 'j', $_daylight_start ), $year );
			$_standard			= gmmktime( 2, 0, 0, 10, gmdate( 'j', $_standard_start ), $year );
			
			$output	.= "BEGIN:VTIMEZONE\r\n";
			$output	.= "TZID:Europe/London\r\n";
			$output	.= "TZURL:https://tzurl.org/zoneinfo/Europe/London\r\n";
			$output	.= "X-LIC-LOCATION:Europe/London\r\n";

			$output	.= "BEGIN:DAYLIGHT\r\n";
			$output	.= "TZOFFSETFROM:+0000\r\n";
			$output	.= "TZOFFSETTO:+0100\r\n";
			$output	.= "TZNAME:BST\r\n";
			$output	.= "DTSTART:" . Date::ts( $_daylight )->modifiedIso8601( TRUE, TRUE ) . "\r\n";
			$output	.= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU\r\n";
			$output	.= "END:DAYLIGHT\r\n";

			$output	.= "BEGIN:STANDARD\r\n";
			$output	.= "TZOFFSETFROM:+0100\r\n";
			$output	.= "TZOFFSETTO:+0000\r\n";
			$output	.= "TZNAME:GMT\r\n";
			$output	.= "DTSTART:" . Date::ts( $_standard )->modifiedIso8601( TRUE, TRUE ) . "\r\n";
			$output	.= "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU\r\n";
			$output	.= "END:STANDARD\r\n";

			$output	.= "END:VTIMEZONE\r\n";
		}
		
		/* Return the final output */
		return $output;
	}

	/**
	 * Return a UID for iCalendar
	 *
	 * @param Event $event	Event
	 * @return	string
	 */
	protected static function _buildUid( Event $event ): string
	{
		$baseUrl = Url::internal('');
		return $event->id . '-' . $event->calendar_id . '-' . md5( (string) $baseUrl ) . '@' . $baseUrl->data['host'];
	}

	/**
	 * Build the VEVENT parts of the iCalendar feed
	 *
	 * @return	string
	 */
	protected function _addEvents(): string
	{
		/* Basic Init */
		$output	= '';

		/* Loop over the events */
		if( count( $this->_events ) )
		{
			foreach( $this->_events as $event )
			{
				/* Normal stuff */
				$output	.= "BEGIN:VEVENT\r\n";
				$output	.= "SUMMARY:" . $this->_encodeSpecialCharacters( $event->title ) . "\r\n";
				$output	.= "DTSTAMP:" . Date::ts( $event->saved )->modifiedIso8601( TRUE, TRUE ) . "\r\n";
				$output	.= "SEQUENCE:" . $event->sequence . "\r\n";
				$output	.= "UID:" . static::_buildUid( $event ) . "\r\n";
				$output	.= $this->_foldLines( "ORGANIZER;CN=\"" . $this->_encodeSpecialCharacters( $event->author()->name, false ) . '":' . Settings::i()->email_out ) . "\r\n";

				/* Attachments */
				$attachments	= array();

				preg_match_all( "/(http.+?attachment\.php\?id=(\d+))/i", $event->content, $matches );

				if( is_array($matches) AND count($matches) )
				{
					foreach( $matches[2] as $k => $v )
					{
						$attachments[ $v ]	= $matches[0][ $k ];
					}
				}

				if( count( $attachments ) )
				{
					foreach( Db::i()->select( '*', 'core_attachments', 'attach_id IN(' . implode( ',', array_keys( $attachments ) ) . ')' ) as $attachment )
					{
						$file		= File::get( 'core_Attachment', $attachment['attach_location'] );
						$output	.= "ATTACH;FMTTYPE=" . File::getMimeType( $file->originalFilename ) . ":" . $attachments[ $attachment['attach_id'] ] . "\r\n";
					}
				}

				/* Description */
				$output	.= "DESCRIPTION:" . $this->_encodeSpecialCharacters( $event->content ) . "\r\n";

				/* Add the times/dates */
				if( $event->_end_date )
				{
					if( $event->all_day )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $event->_start_date->modifiedIso8601( FALSE ) . "\r\n";
						$output	.= "DTEND;VALUE=DATE:" . $event->_end_date->add( new DateInterval( 'P1D' ) )->modifiedIso8601( FALSE ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART:" . $event->_start_date->modifiedIso8601( TRUE, TRUE ) . "\r\n";
						$output	.= "DTEND:" . $event->_end_date->modifiedIso8601( TRUE, TRUE ) . "\r\n";
					}
				}
				else
				{
					if( $event->all_day )
					{
						$output	.= "DTSTART;VALUE=DATE:" . $event->_start_date->modifiedIso8601( FALSE ) . "\r\n";
					}
					else
					{
						$output	.= "DTSTART:" . $event->_start_date->modifiedIso8601( TRUE, TRUE ) . "\r\n";
					}
				}
				
				/* Is this event recurring? */
				if ( $event->recurring )
				{
					$output	.= "RRULE:" . $event->recurring . "\r\n";
				}
				
				/* Any attendees to the event? */
				try
				{
					foreach( $event->attendees( Event::RSVP_YES ) as $attendee )
					{
						$output	.= $this->_foldLines( "ATTENDEE;CN=\"" . $this->_encodeSpecialCharacters( $attendee->name, false ) . '";CUTYPE=INDIVIDUAL;PARTSTAT=ACCEPTED:' . Settings::i()->email_out ) . "\r\n";
					}
				}
				catch( BadMethodCallException $e ){}

				/* End */
				$output	.= "END:VEVENT\r\n";
			}
		}

		/* And return the combined output now */
		return $output;
	}

	/**
	 * Encode special characters in a string for iCalendar
	 *
	 * @param string $text		String to encode
	 * @param bool $lineFold	Line-fold
	 * @return	string		Encoded string
	 */
	protected function _encodeSpecialCharacters( string $text, bool $lineFold=true ): string
	{
		$text	= strip_tags( str_replace( array( "<br>", "<br />" ), "\n", $text ) );
		$text	= str_replace( "\\", "\\\\", $text );
		$text	= str_replace( "\n" , '\\n', $text );
		$text	= str_replace( "\r" , '\\n', $text );
		$text	= str_replace( ','  , '\,', $text );
		$text	= str_replace( ';'  , '\;', $text );
		$text	= str_replace( '"', '\"', $text );
		
		if( $lineFold )
		{
			$text	= $this->_foldLines( $text );
		}
		
		return $text;
	}
	
	/**
	 * Fold lines per RFC2445
	 *
	 * @param string $text	String to fold
	 * @return	string
	 * @link	https://gist.github.com/81747
	 */
	protected function _foldLines( string $text ): string
	{
		$return	= array();
		$_extra	= 15; /* Takes into account line beginning, i.e. "DESCRIPTION:" */
		
		while( strlen($text) > 60 )
		{
			$space	= 75 - $_extra; /* Remove line beginning - subsequent loops this will be tab character */
			$mbcc	= $space;
			
			while( $mbcc )
			{
				$line	= mb_substr( $text, 0, $mbcc );	/* Get first chunk of chars */
				$octet	= strlen( $line ); /* Determine how long this really is (3-byte letters could triple the size) */
				
				/* Too long ? */
				if( $octet > $space )
				{
					if( $mbcc - ( $octet - $space ) < 1 )
					{
						$mbcc -= round( $mbcc / 3 );
					}
					else
					{
						$mbcc -= $octet - $space;
					}
				}
				else
				{
					$return[]	= $line;
					$_extra		= 1;
					$text		= mb_substr( $text, $mbcc );
					break;
				}
			}
		}
		
		/* Anything left? */
		if( !empty($text) )
		{
			$return[]	= $text;
		}
		
		/* Return now */
		return implode( "\r\n\t", $return );
	}

	/**
	 * @brief	Type of begin block we are currently parsing
	 */
	protected string $_begin = '';
	
	/**
	 * @brief	Raw iCalendar feed data after parsing
	 */
	protected array $_parsedIcsData	= array();
	
	/**
	 * @brief	Raw iCalendar data before parsing
	 */
	protected array $_rawIcsData = array();

	/**
	 * @brief	Earliest timestamp from feed
	 */
	protected int $_feedEarliest	= 0;

	/**
	 * @brief	Latest timestamp from feed
	 */
	protected int $_feedLatest		= 0;

	/**
	 * Un-encode special characters in a string coming from iCalendar feed
	 *
	 * @param string $text	String to unencode
	 * @return	string		Unencoded string
	 */
	protected function _unencodeSpecialCharacters( string $text ): string
	{
		/* Reverse encoding */
		if( stripos( $text, 'encoding=' ) === 0 )
		{
			preg_match( "#encoding=(.+?):(.+?)$#i", $text, $matches );
			
			if( $matches[1] )
			{
				switch( mb_strtolower($matches[1]) )
				{
					case 'base64':
						$text	= base64_decode( $matches[2] );
					break;
					
					case 'quoted-printable':
						$text	= quoted_printable_decode( $matches[2] );
					break;
				}
			}
			else
			{
				$text	= mb_substr( $text, mb_strpos( $text, ':' ) );
			}
		}

		$text	= str_replace( '\\n', "\n", $text );
		$text	= str_replace( '\,', "," , $text );
		$text	= str_replace( '\;', ";" , $text );
		$text	= str_replace( '\:', ":" , $text );
		$text	= str_replace( 'DQUOTE', '"' , $text );

		return $text;
	}

	/**
	 * Unfold lines per RFC2445 4.1
	 *
	 * @param string $string	Starting string
	 * @param int $line	Starting line number
	 * @return	string
	 */
	protected function _unfoldLines( string $string, int $line ): string
	{
		/* Recursively unfold lines as needed */
		if( isset( $this->_rawIcsData[ $line + 1 ] ) AND ( mb_substr( $this->_rawIcsData[ $line + 1 ], 0, 1 ) == ' ' OR mb_substr( $this->_rawIcsData[ $line + 1 ], 0, 1 ) == "\t" ) )
		{
			$string	.= ltrim( $this->_rawIcsData[ $line + 1 ] );
			$string	= $this->_unfoldLines( $string, $line + 1 );
		}
		
		return $string;
	}

	/**
	 * Unparse time information from iCalendar datetime info
	 *
	 * @param string $string		iCalendar line
	 * @return	array 	Time information
	 */
	protected function _unparseTimeInfo( string $string ): array
	{
		/* init */
		$value = NULL;
		$rawTime = NULL;
		$tzid = NULL;

		/* split the timestamp from the properties */
		$dt = explode( ':', $string );

		/* Set the raw time value */
		$rawTime = $dt[1];

		/* Which properties do we have */
		$properties = explode( ';', $dt[0] );

		foreach( $properties as $property )
		{
			if( mb_strpos( $property, '=' ) !== false )
			{
				$_property = explode( '=', $property );

				/* Grab our needed values from the given properties */
				switch( $_property[0] )
				{
					case 'VALUE':
						$value = $_property[1];
						break;
					case 'TZID':
						$tzid = $_property[1];
						break;
				}
			}
		}

		/* If the value is a date without a time, then type should be reset to date */
		$type = ( mb_strlen( $rawTime ) === 8 ) ? 'DATE' : 'DATETIME';

		/* Format return array */
		$return  = array(
						'type'		=> $type,
						'raw'		=> $rawTime,
						'raw_ts'	=> strtotime( $rawTime ),
						'tzid'		=> ( $tzid ) ? str_replace( '"', '', $tzid ) : '',
						);

		/* Is this the earliest or latest timestamp? */
		if ( ( $this->_feedEarliest == 0 ) OR ( $return['raw_ts'] < $this->_feedEarliest ) )
		{
			$this->_feedEarliest	= $return['raw_ts'];
		}
		
		if ( ( $this->_feedLatest == 0 ) OR ( $return['raw_ts'] > $this->_feedLatest ) )
		{
			$this->_feedLatest		= $return['raw_ts'];
		}
		
		/* Return our results */
		return $return;
	}

	/**
	 * Parse a 'BEGIN:' block in an iCalendar feed
	 *
	 * @param string $type	Type of 'BEGIN' object
	 * @param int $start	Line number
	 * @return	void
	 */
	protected function _parseBeginBlock( string $type, int $start ) : void
	{
		switch( $type )
		{
			case 'VCALENDAR':
				$this->_begin	= 'VCALENDAR';
				$this->_processVcalendarObject( $start + 1 );
			break;

			case 'STANDARD':
				if ( $this->_begin	== 'VTIMEZONE' )
				{
					$this->_processTimezoneTypeObject( $start + 1, 'STANDARD' );
				}
			break;

			case 'VEVENT':
				$this->_begin	= 'VEVENT';
				$this->_processEventObject( $start + 1 );
			break;
			
			/* Anything else is unsupported at this time */
			default:
			break;
		}
	}

	/**
	 * @brief	Keep track of object we are parsing inside an event object
	 */
	protected ?string $currentlyParsing = NULL;

	/**
	 * Parse event object in an ical feed
	 *
	 * @param int $start	Line number
	 * @return	void
	 */
	protected function _processEventObject( int $start ) : void
	{
		/* Init */
		$_break	= false;
		$_event	= array();

		$this->currentlyParsing	= 'EVENT';

		/* Loop over the lines */
		$_recid	= null;

		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			/* Unparse and get content */
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];

			if( $this->currentlyParsing != 'EVENT' AND !in_array( $_type, array( 'END', 'BEGIN' ) ) )
			{
				continue;
			}
			
			switch( $_type )
			{
				case 'CLASS':
					$_event['access_class']			= $_data;
				break;
				
				case 'CREATED':
					if( !isset( $_event['created'] ) OR !$_event['created'] )
					{
						$_event['created']			= strtotime( $_data );
					}
				break;
				
				case 'SUMMARY':
					if( mb_strpos( $_data, 'LANGUAGE=' ) === 0 )
					{
						$_data	= preg_replace( "/^LANGUAGE=(.+?):(.+?)$/i", "\\2", $_data );
					}

					$_event['summary']				= $this->_unencodeSpecialCharacters( $_data );
				break;

				case 'DESCRIPTION':
					if( mb_strpos( $_data, 'LANGUAGE=' ) === 0 )
					{
						$_data	= preg_replace( "/^LANGUAGE=(.+?):(.+?)$/i", "\\2", $_data );
					}

					$_event['description']			= $this->_unencodeSpecialCharacters( $_data );
				break;
				
				case 'DURATION':
					$_event['duration']				= $_data;
				break;

				case 'DTSTART':
					$_event['start']				= $this->_unparseTimeInfo( $this->_rawIcsData[$i] );
				break;
				
				case 'DTEND':
					$_event['end']					= $this->_unparseTimeInfo( $this->_rawIcsData[$i] );
				break;
				
				case 'DTSTAMP':
					$_event['created']				= strtotime( $_data );
				break;
				
				case 'LAST-MODIFIED':
					$_event['last_modified']		= strtotime( $_data );
				break;

				case 'TRANSP':
					$_event['time_transparent']		= $_data;
				break;								

				case 'GEO':
					$_geo							= explode( ":", $_data );
					$_event['geo']					= array( 'lat' => $_geo[0], 'long' => $_geo[1] );
				break;

				case 'ORGANIZER':
					if ( $_data )
					{
						$line							= explode( ':', $_data );
						$_event['organizer']			= array( 'name' => str_replace( 'CN=', '', $line[0] ), 'email' => $line[1] );
					}
				break;

				case 'ATTENDEE':
					$line							= explode( ':', $_data );
					$_email							= '';
					
					foreach( $line as $_line )
					{
						$_line	= str_replace( 'cn=', '', mb_strtolower($_line) );

						if( filter_var( $_line, FILTER_VALIDATE_EMAIL ) !== FALSE )
						{
							$_email	= $_line;
						}
					}

					$_event['attendee'][]			= array( 'name' => str_replace( 'CN=', '', $line[0] ), 'email' => $_email );
				break;
				
				case 'UID':
					$_event['uid']					= $_data;
				break;
				
				case 'STATUS':
					$_event['status']				= $_data;
				break;
				
				case 'LOCATION':
					$_event['location']				= $this->_unencodeSpecialCharacters( $_data );
				break;

				case 'SEQUENCE':
					$_event['sequence']				= intval($_data);
				break;
				
				case 'RRULE':
					$_event['recurr']				= $_data;
				break;
				
				case 'BEGIN':
					$this->currentlyParsing	= $_data;
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'RECURRENCE-ID':
					$_recid	= $_data;
				break;
				
				case 'END':
					if( $this->currentlyParsing == 'EVENT' )
					{
						$_break	= true;
					}
					else
					{
						$this->currentlyParsing	= 'EVENT';
					}
				break;
			}
			
			if( $_break )
			{
				if( $_recid )
				{
					$_event['uid']	= md5( $_event['uid'] . $_recid );
				}

				$this->_parsedIcsData['events'][] = $_event;
				break;
			}
		}
	}
	
	/**
	 * Parse core vcalendar object in an ical feed
	 *
	 * @param int $start	Line number
	 * @return	void
	 */
	protected function _processVcalendarObject( int $start ) : void
	{
		/* Loop over the lines */
		for( $i = $start, $j = count( $this->_rawIcsData ); $i < $j; $i++ )
		{
			/* Unparse and get the data */
			$tmp	= $this->_unparseContent( $this->_rawIcsData[$i], $i );
			
			if ( !$tmp )
			{
				continue;
			}
				
			$_type	= $tmp['type'];
			$_data	= $tmp['data'];
			
			switch( $_type )
			{
				case 'PRODID':
					$this->_parsedIcsData['core']['product']		= $_data;
				break;

				case 'VERSION':
					$this->_parsedIcsData['core']['version']		= $_data;
				break;

				case 'BEGIN':
					$this->_parseBeginBlock( $_data, $i );
				break;

				case 'X-WR-CALNAME':
					$this->_parsedIcsData['core']['calendar_name']	= $_data;
				break;
				
				case 'END':
					return;
			}
		}
	}
	
	/**
	 * Unformat content from incoming ical feed
	 *
	 * @param string $string	String content to unparse
	 * @param int $line	Line number
	 * @return	mixed	Array of data, or false
	 */
	protected function _unparseContent( string $string, int $line ): mixed
	{
		/* If the line starts with a space it was folded (skip it) */
		if( substr( $this->_rawIcsData[ $line ], 0, 1 ) == ' ' )
		{
			return false;
		}
		
		/* Process */
		$_temp	= preg_split( "/(:|;)/", $string );
		$_type	= array_shift( $_temp );
		$_data	= implode( ':', $_temp );
		
		/* Unfold lines if necessary */
		$_data	= $this->_unfoldLines( $_data, $line );
		
		/* Return the data */
		return array( 'type' => $_type, 'data' => $_data );
	}

	/**
	 * Convert times to GMT based on timezones
	 *
	 * @param array $data	Parsed data
	 * @return	array
	 */
	protected function _convertToGmt( array $data ): array
	{
		/* Fix events */
		if ( isset( $data['events'] ) AND is_array( $data['events'] ) AND count( $data['events'] ) )
		{
			foreach( $data['events'] as $id => $event )
			{
				foreach( array( 'start', 'end' ) as $method )
				{
					/* Is a timezone specified? */
					if ( isset( $event[ $method ]['tzid'] ) AND $event[ $method ]['tzid'] )
					{
						/* Let's test it first... */
						$timezone	= NULL;

						if( in_array( $event[ $method ]['tzid'], DateTime::getTimezoneIdentifiers() ) )
						{
							try
							{
								$timezone	= new DateTimeZone( $event[ $method ]['tzid'] );
							}
							catch ( Exception $e ) {}
						}

						$event[ $method ]['utc_time']	= new DateTime( $event[ $method ]['raw'], $timezone );
					}
					elseif( isset( $event[ $method ]['raw'] ) )
					{
						$event[ $method ]['utc_time']	= new DateTime( $event[ $method ]['raw'] );
					}
				}
				
				$data['events'][ $id ] = $event;
			}
		}

		return $data;
	}
}
