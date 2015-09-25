<?php
/**
 * Admin functions
 *
 * Functions for the admin back end components only.
 *
 * @package SimpleCalendar/Admin/Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get settings pages and tabs.
 *
 * @return array
 */
function simcal_get_settings_pages() {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_settings_pages() : array();
}

/**
 * Get a settings page tab.
 *
 * @param  string $page
 *
 * @return null|\SimpleCalendar\Abstracts\Settings_Page
 */
function simcal_get_settings_page( $page ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_settings_page( $page ) : null;
}

/**
 * Get a field.
 *
 * @param array  $args
 * @param string $name
 *
 * @return null|\SimpleCalendar\Abstracts\Field
 */
function simcal_get_field( $args, $name = '' ) {
	$objects = \SimpleCalendar\plugin()->objects;
	return $objects instanceof \SimpleCalendar\Objects ? $objects->get_field( $args, $name ) : null;
}

/**
 * Print a field.
 *
 * @param array  $args
 * @param string $name
 */
function simcal_print_field( $args, $name = '' ) {

	$field = simcal_get_field( $args, $name );

	if ( $field instanceof \SimpleCalendar\Abstracts\Field ) {
		$field->html();
	}
}

/**
 * Clear feed transients cache.
 *
 * @param  string|int|array|\WP_Post $id
 *
 * @return bool
 */
function simcal_delete_feed_transients( $id = '' ) {

	if ( is_numeric( $id ) ) {
		$id = intval( $id ) > 0 ? absint( $id ) : simcal_get_calendars();
	} elseif ( $id instanceof WP_Post ) {
		$id = $id->ID;
	} elseif ( is_array( $id ) ) {
		$id = array_map( 'absint', $id );
	} else {
		$id = simcal_get_calendars( '', true );
	}

	$feed_types = simcal_get_feed_types();

	if ( is_array( $id ) ) {

		$posts = get_posts( array(
			'post_type' => 'calendar',
			'fields'    => 'ids',
			'post__in'  => $id,
			'nopaging'  => true,
		) );

		foreach ( $posts as $post ) {
			$calendar = simcal_get_calendar( $post );
			if ( $calendar instanceof \SimpleCalendar\Abstracts\Calendar ) {
				foreach ( $feed_types as $feed_type ) {
					delete_transient( '_simple-calendar_feed_id_' . strval( $calendar->id ) . '_' . $feed_type );
				}
			}
		}

	} else {

		$post = get_post( $id );
		$calendar = simcal_get_calendar( $post );
		if ( $calendar instanceof \SimpleCalendar\Abstracts\Calendar ) {
			foreach ( $feed_types as $feed_type ) {
				delete_transient( '_simple-calendar_feed_id_' . strval( $calendar->id ) . '_' . $feed_type );
			}
		}
	}

	return delete_transient( '_simple-calendar_feed_ids' );
}

/**
 * Sanitize a variable of unknown type.
 *
 * Recursive helper function to sanitize a variable from input,
 * which could also be a multidimensional array of variable depth.
 *
 * @param  mixed  $var  Variable to sanitize.
 * @param  string $func Function to use for sanitizing text strings (default 'sanitize_text_field')
 *
 * @return array|string Sanitized variable
 */
function simcal_sanitize_input( $var, $func = 'sanitize_text_field'  ) {

	if ( is_null( $var ) ) {
		return '';
	}

	if ( is_bool( $var ) ) {
		if ( $var === true ) {
			return 'yes';
		} else {
			return 'no';
		}
	}

	if ( is_string( $var ) || is_numeric( $var ) ) {
		$func = is_string( $func ) && function_exists( $func ) ? $func : 'sanitize_text_field';
		return call_user_func( $func, trim( strval( $var ) ) );
	}

	if ( is_object( $var ) ) {
		$var = (array) $var;
	}

	if ( is_array( $var ) ) {
		$array = array();
		foreach ( $var as $k => $v ) {
			$array[ $k ] = simcal_sanitize_input( $v );
		}
		return $array;
	}

	return '';
}

/**
 * Check if a screen is a plugin admin view.
 * Returns the screen id if true, false (bool) if not.
 *
 * @return string|bool
 */
function simcal_is_admin_screen() {

	$view = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

	if ( $view instanceof WP_Screen ) {

		// Screens used by this plugin.
		$screens = array(
			'customize',
			'calendar',
			'calendar_page_simple-calendar_settings',
			'calendar_page_simple-calendar_tools',
			'edit-calendar',
			'edit-calendar_category',
			'dashboard_page_simple-calendar_about',
			'dashboard_page_simple-calendar_credits',
			'dashboard_page_simple-calendar_translators',
		);
		if ( in_array( $view->id, $screens ) ) {
			return $view->id;
		}
	}

	return false;
}

/**
 * Get admin notices.
 *
 * @return array
 */
function simcal_get_admin_notices() {
	$notices = new \SimpleCalendar\Admin\Notices();
	return $notices->get_notices();
}

/**
 * Delete admin notices.
 */
function simcal_delete_admin_notices() {
	delete_option( 'simple-calendar_admin_notices' );
}

/**
 * Print a shortcode tip.
 *
 * @param int $post_id
 */
function simcal_print_shortcode_tip( $post_id ) {

	$browser = new \Browser();
	if ( $browser::PLATFORM_APPLE == $browser->getPlatform() ) {
		$cmd = '&#8984;&#43;C';
	} else {
		$cmd = 'Ctrl&#43;C';
	}

	$shortcut  = sprintf( __( 'Press %s to copy.', 'google-calendar-events' ), $cmd );
	$shortcode = sprintf( '[calendar id="%s"]', $post_id );

	echo "<input readonly='readonly' " .
				"class='simcal-shortcode simcal-calendar-shortcode simcal-shortcode-tip' " .
				"title='" . $shortcut . "' " .
				"onclick='this.select();' value='" . $shortcode . "' />";
}

if ( ! function_exists( 'mb_detect_encoding' ) ) {

	/**
	 * Fallback function for `mb_detect_encoding()`,
	 * php_mbstring module in the php.ini could be missing.
	 *
	 * @param  string $string
	 * @param  null   $enc
	 * @param  null   $ret
	 *
	 * @return bool
	 */
	function mb_detect_encoding( $string, $enc = null, $ret = null ) {

		static $enclist = array(
			'UTF-8',
			'ASCII',
			'ISO-8859-1',
			'ISO-8859-2',
			'ISO-8859-3',
			'ISO-8859-4',
			'ISO-8859-5',
			'ISO-8859-6',
			'ISO-8859-7',
			'ISO-8859-8',
			'ISO-8859-9',
			'ISO-8859-10',
			'ISO-8859-13',
			'ISO-8859-14',
			'ISO-8859-15',
			'ISO-8859-16',
			'Windows-1251',
			'Windows-1252',
			'Windows-1254',
		);

		$result = false;

		foreach ( $enclist as $item ) {
			$sample = iconv( $item, $item, $string );
			if ( md5( $sample ) == md5( $string ) ) {
				if ( $ret === null ) {
					$result = $item;
				} else {
					$result = true;
				}
				break;
			}
		}

		return $result;
	}

}
