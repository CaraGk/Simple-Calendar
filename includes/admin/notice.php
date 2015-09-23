<?php
/**
 * Admin Notice
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin notice.
 *
 * An admin notice as an object.
 */
class Notice {

	/**
	 * Notice id.
	 * Will be the notice key in saved notices option.
	 *
	 * @access public
	 * @var string|array
	 */
	public $id = '';

	/**
	 * Notice type
	 * Gives the notice a CSS class.
	 *
	 * @access public
	 * @var string notice|error|updated|update-nag
	 */
	public $type = '';

	/**
	 * Additional classes.
	 *
	 * @access public
	 * @var array
	 */
	public $class = array();

	/**
	 * To which users the notice should be shown.
	 * If not set, will be visible to all users.
	 *
	 * @access public
	 * @var string
	 */
	public $capability = '';

	/**
	 * In which screen the notice should appear.
	 * If not set, will appear in every dashboard page/screen.
	 *
	 * @access public
	 * @var array
	 */
	public $screen = array();

	/**
	 * For which posts edit screens the notice should appear.
	 * If not set, will fallback on $screen rule only.
	 *
	 * @access public
	 * @var array
	 */
	public $post = array();

	/**
	 * Can the notice be dismissed by the user?
	 * If false, you need to set up a dismissal event.
	 *
	 * @access public
	 * @var bool
	 */
	public $dismissable = true;

	/**
	 * Whether to hide notice while keeping it stored.
	 * If false, will keep the notice in option without showing it.
	 *
	 * @access public
	 * @var bool
	 */
	public $visible = true;

	/**
	 * The notice content.
	 * Supports html. You would normally wrap this in paragraph tags.
	 *
	 * @access public
	 * @var string
	 */
	public $content = '';

	/**
	 * Make a notice.
	 *
	 * @param array $notice
	 */
	public function __construct( $notice ) {

		if ( isset( $notice['id'] ) && isset( $notice['content'] ) ) {

			// Content.
			$this->id      = isset( $notice['id'] )      ? ( is_array( $notice['id'] ) ? array_map( 'sanitize_key', $notice['id'] ) : sanitize_key( $notice['id'] ) ) : '';
			$this->content = isset( $notice['content'] ) ? wp_kses_post( $notice['content'] ) : '';

			// Type.
			$default    = 'notice';
			$type       = isset( $notice['type'] ) ? esc_attr( $notice['type'] ) : $default;
			$types      = array(
				'error',
				'notice',
				'updated',
				'update-nag',
			);
			$this->type = in_array( $type, $types ) ? $type : $default;

			// Visibility.
			$this->capability  = isset( $notice['capability'] )  ? esc_attr( $notice['capability'] ) : '';
			$this->screen      = isset( $notice['screen'] )      ? ( is_array( $notice['screen'] ) ? array_map( 'esc_attr', $notice['screens'] ) : array( esc_attr( $notice['screen'] ) ) ) : array();
			$this->post        = isset( $notice['post'] )        ? ( is_array( $notice['post'] ) ? array_map( 'intval', $notice['post'] ) : array( intval( $notice['post'] ) ) ) : array();
			$this->dismissable = isset( $notice['dismissable'] ) ? ( $notice['dismissable'] === false ? false: true ) : true;
			$this->visible     = isset( $notice['visible'] )     ? ( $notice['visible'] === false ? false: true ) : true;

		}

	}

	/**
	 * Add the notice.
	 *
	 * @access public
	 */
	public function add() {
		if ( ! empty( $this->id ) && ! empty( $this->content ) ) {
			$notices              = get_option( 'simple-calendar_admin_notices', array() );
			if ( is_array( $this->id ) ) {
				foreach ( $this->id as $k => $v ) {
					$notices[ $k ][ $v ] = $this;
				}
			} else {
				$notices[ $this->id ][] = $this;
			}
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

	/**
	 * Remove the notice.
	 *
	 * @access public
	 */
	public function remove() {
		if ( ! empty( $this->id ) && ! empty( $this->content ) ) {
			$notices = get_option( 'simple-calendar_admin_notices', array() );
			if ( is_array( $this->id ) ) {
				foreach ( $this->id as $k => $v ) {
					unset( $notices[ $k ] );
				}
			} else {
				unset( $notices[ $this->id ] );
			}
			update_option( 'simple-calendar_admin_notices', $notices );
		}
	}

}
