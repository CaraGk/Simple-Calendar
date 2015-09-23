<?php
/**
 * Google Calendar Feed
 *
 * @package SimpleCalendar/Feeds
 */
namespace SimpleCalendar\Feeds;

use Carbon\Carbon;
use SimpleCalendar\Abstracts\Calendar;
use SimpleCalendar\Abstracts\Feed;
use SimpleCalendar\Feeds\Admin\Google_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Calendar feed.
 *
 * A feed using a simple Google API key to pull events from public calendars.
 */
class Google extends Feed {

	/**
	 * Google Calendar API key.
	 *
	 * @access private
	 * @var string
	 */
	private $google_api_key = '';

	/**
	 * Google Calendar ID.
	 *
	 * @access private
	 * @var string
	 */
	private $google_calendar_id = '';

	/**
	 * Set properties.
	 *
	 * @param string|Calendar $calendar
	 */
	public function __construct( $calendar = '' ) {

		parent::__construct( $calendar );

		$this->type = 'google';
		$this->name = __( 'Google Calendar', 'google-calendar-events' );

		// Google API Key.
		$settings = get_option( 'simple-calendar_settings_feeds' );
		$this->google_api_key = isset( $settings['google']['api_key'] ) ? esc_attr( $settings['google']['api_key'] ) : '';

		if ( $this->calendar_id > 0 ) {

			$this->google_calendar_id = $this->esc_google_calendar_id( get_post_meta( $this->calendar_id, '_google_calendar_id', true ) );

			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {

				$events = ! empty( $this->google_api_key ) ? $this->get_events() : array();

				if ( ! empty( $events ) && is_array( $events ) ) {
					ksort( $events, SORT_NUMERIC );
				}

				$this->events = $events;
			}
		}

		if ( is_admin() ) {
			$admin = new Google_Admin( $this, $this->google_api_key, $this->google_calendar_id );
			$this->settings = $admin->settings_fields();
		}
	}

	/**
	 * Decode a calendar id.
	 *
	 * @param  string $id Base64 encoded id.
	 *
	 * @return string
	 */
	public function esc_google_calendar_id( $id ) {
		return base64_decode( $id );
	}

	/**
	 * Get events feed.
	 *
	 * Normalizes Google data into a standard array object to list events.
	 *
	 * @return string|array
	 */
	public function get_events() {

		$calendar = get_transient( '_simple-calendar_feed_id_' . strval( $this->calendar_id ) . '_' . $this->type );

		if ( empty( $calendar ) && ! empty( $this->google_calendar_id ) ) {

			$calendar = $error = '';
			try {
				$response = $this->get_calendar( $this->google_calendar_id );
			} catch ( \Exception $e ) {
				$error = $e->getMessage();
			}

			if ( isset( $response['events'] ) && isset( $response['timezone'] ) ) {

				$calendar['title']       = $response['title'];
				$calendar['description'] = $response['description'];
				$calendar['timezone']    = $response['timezone'];
				$calendar['url']         = $response['url'];
				$calendar['events']      = '';

				// If no timezone has been set, use calendar feed.
				if ( 'use_calendar' == get_post_meta( $this->calendar_id, '_feed_timezone_setting', true ) ) {
					$this->timezone = $calendar['timezone'];
				}

				if ( ! empty( $response['events'] ) && is_array( $response['events'] ) ) {
					foreach ( $response['events'] as $event ) {
						if ( $event instanceof \Google_Service_Calendar_Event ) {

							// Visibility.
							$visibility = $event->getVisibility();
							// Public calendars may have private events which can't be properly accessed by simple api key method.
							if ( $this->type == 'google' && ( $visibility == 'private' || $visibility == 'confidential' ) ) {
								continue;
							}

							$whole_day = false;

							// Event start properties.
							$start_timezone = ! $event->getStart()->timeZone ? $calendar['timezone'] : $event->getStart()->timeZone;
							if ( is_null( $event->getStart()->dateTime ) ) {
								$whole_day = true;
								$google_start     = Carbon::parse( $event->getStart()->date )->startOfDay()->setTimezone( $start_timezone );
								$google_start_utc = Carbon::parse( $event->getStart()->date )->startOfDay()->setTimezone( 'UTC' );
							} else {
								$google_start     = Carbon::parse( $event->getStart()->dateTime )->setTimezone( $start_timezone );
								$google_start_utc = Carbon::parse( $event->getStart()->dateTime )->setTimezone( 'UTC' );
							}
							// Start.
							$start = $google_start->getTimestamp();
							// Start UTC.
							$start_utc = $google_start_utc->setTimezone( 'UTC' )->getTimestamp();

							// Event end properties.
							$end_timezone = ! $event->getEnd()->timeZone ? $calendar['timezone'] : $event->getEnd()->timeZone;
							if ( is_null( $event->getEnd()->dateTime ) ) {
								$google_end = Carbon::parse( $event->getEnd()->date )->setTimezone( $end_timezone )->endOfDay();
								$google_end_utc = Carbon::parse( $event->getEnd()->date )->setTimezone( 'UTC' )->endOfDay();
							}  else {
								$google_end = Carbon::parse( $event->getEnd()->dateTime )->setTimezone( $end_timezone );
								$google_end_utc = Carbon::parse( $event->getEnd()->dateTime )->setTimezone( 'UTC' );
							}
							// End.
							$end = $google_end->getTimestamp();
							// End UTC.
							$end_utc = $google_end_utc->getTimestamp();

							// Count multiple days.
							$span = 0;
							if ( false == $event->getEndTimeUnspecified() ) {
								$a = intval( $google_start_utc->setTimezone( $calendar['timezone'] )->format( 'Ymd' ) );
								$b = intval( $google_end_utc->setTimezone( $calendar['timezone']  )->format( 'Ymd' ) );
								$span = max( ( $b - $a ), 0 );
							}
							$multiple_days = $span > 0 ? $span + 1 : false;

							// Google cannot have two different locations for start and end time.
							$start_location = $end_location = $event->getLocation();

							// Recurring event.
							$recurrence = $event->getRecurrence();

							// Build the event.
							$calendar['events'][ $start_utc ][] = array(
								'type'           => 'google-calendar',
								'title'          => $event->getSummary(),
								'description'    => $event->getDescription(),
								'link'           => $event->getHtmlLink(),
								'visibility'     => $visibility,
								'uid'            => $event->getICalUID(),
								'calendar'       => $this->calendar_id,
								'timezone'       => $this->timezone,
								'start'          => $start,
								'start_utc'      => $start_utc,
								'start_timezone' => $start_timezone,
								'start_location' => $start_location,
								'end'            => $end,
								'end_utc'        => $end_utc,
								'end_timezone'   => $end_timezone,
								'end_location'   => $end_location,
								'whole_day'      => $whole_day,
								'multiple_days'  => $multiple_days,
								'recurrence'     => $recurrence ? $recurrence : false,
								'meta'           => array(
									'color'  => $event->getColorId(),
									'status' => $event->getStatus(),
								)
							);

						}
					}

					if ( ! empty( $calendar['events'] ) ) {
						set_transient(
							'_simple-calendar_feed_id_' . strval( $this->calendar_id ) . '_' . $this->type,
							$calendar,
							max( absint( $this->cache ), 60 )
						);
					}
				}

			} else {

				$message  = __( 'While trying to retrieve events, Google returned an error:', 'google-calendar-events' );
				$message .= '<br><br>' . $error . '<br><br>';
				$message .= __( 'Please ensure that both your Google Calendar ID and API Key are valid and that the Google Calendar you want to display is public.', 'google-calendar-events' ) . '<br><br>';
				$message .= __( 'Only you can see this notice.', 'google-calendar-events' );

				return $message;
			}

		}

		// If no timezone has been set, use calendar feed.
		if ( empty( $this->timezone ) && isset( $calendar['timezone'] ) ) {
			$this->timezone = $calendar['timezone'];
		}

		return isset( $calendar['events'] ) ? $calendar['events'] : array();
	}

	/**
	 * Connect to Google Calendar.
	 *
	 * @access protected
	 *
	 * @param  string $id (optional) The calendar id.
	 *
	 * @throws \Exception if Google Calendar Service throws one.
	 * @return null|array
	 */
	public function get_calendar( $id = '' ) {

		$calendar = null;
		$client   = $this->client();

		if ( ! is_null( $client ) ) {

			$calendar_id = $id ? $id : $this->calendar_id;
			if ( ! empty( $calendar_id ) ) {

				// Build the request args.
				$args = array();

				// Expand recurring events.
				$recurring = esc_attr( get_post_meta( $this->calendar_id, '_google_events_recurring', true ) );
				if ( $recurring == 'show' ) {
					$args['singleEvents'] = true;
				}

				// Query events using search terms.
				$search_query = esc_attr( get_post_meta( $this->calendar_id, '_google_events_search_query', true ) );
				if ( $search_query ) {
					$args['q'] = rawurlencode( $search_query );
				}

				// Max results to query.
				$max_results        = max( absint( get_post_meta( $this->calendar_id, '_google_events_max_results', true ) ), 1 );
				$args['maxResults'] = $max_results ? strval( $max_results ) : '2500';

				// Specify a timezone.
				$timezone = '';
				if ( 'use_calendar' != get_post_meta( $this->calendar_id, '_feed_timezone_setting', true ) ) {
					$args['timeZone'] = $timezone = $this->timezone;
				}

				// Lower bound (inclusive) for an event's end time to filter by.
				$earliest_event = intval( $this->time_min );
				if ( $earliest_event > 0 ) {
					$timeMin = Carbon::now();
					if ( ! empty( $timezone ) ) {
						$timeMin->setTimezone( $timezone );
					}
					$timeMin->setTimestamp( $earliest_event );
					$args['timeMin'] = $timeMin->toRfc3339String();
				}

				// Upper bound (exclusive) for an event's start time to filter by.
				$latest_event = intval( $this->time_max );
				if ( $latest_event > 0 ) {
					$timeMax = Carbon::now();
					if ( ! empty( $timezone ) ) {
						$timeMax->setTimezone( $timezone );
					}
					$timeMax->setTimestamp( $latest_event );
					$args['timeMax'] = $timeMax->toRfc3339String();
				}

				$response = $error = '';
				try {
					// Make the request.
					$response = $client->events->listEvents( $calendar_id, $args );
				} catch ( \Exception $e ) {
					$error .= $e->getMessage();
				}

				if ( $response instanceof \Google_Service_Calendar_Events ) {

					$calendar['title']       = $response->getSummary();
					$calendar['description'] = $response->getDescription();
					$calendar['timezone']    = $response->getTimeZone();
					$calendar['url']         = esc_url( '//www.google.com/calendar/embed?src=' . $calendar_id );

					$events = $response->getItems();
					$count  = count( $events );
					if ( $count > 0 ) {
						foreach ( $events as $event ) {
							$calendar['events'][] = $event;
						}
					} else {
						// There are no events in this calendar with the given $args.
						$calendar['events'][] = '';
					}

				} else {

					throw new \Exception( $error );
				}

			} // Is there a calendar id?

		} // Is the client working?

		return $calendar;
	}

	/**
	 * Google API Client.
	 *
	 * @access private
	 *
	 * @param  string $api_key
	 *
	 * @return null|\Google_Service_Calendar
	 */
	private function client( $api_key = '' ) {

		$api_key = ! empty( $api_key ) ? $api_key : $this->google_api_key;

		if ( $api_key ) {

			$client = new \Google_Client();
			$client->setDeveloperKey( $api_key );
			$client->setApplicationName( get_bloginfo( 'name' ) );
			$client->setAccessType( 'online' );
			// With a simple API key we can only have read access rights to public calendars.
			$client->setScopes( array( \Google_Service_Calendar::CALENDAR_READONLY ) );

			return new \Google_Service_Calendar( $client );
		}

		return null;
	}

}
