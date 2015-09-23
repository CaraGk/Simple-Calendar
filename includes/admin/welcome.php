<?php
/**
 * Welcome Page Class
 *
 * Adapted from analogue code found in WoCommerce, EDD and WordPress itself.
 *
 * @package SimpleCalendar/Admin
 */
namespace SimpleCalendar\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Welcome page.
 *
 * Shows a feature overview for the new version (major) and credits.
 */
class Welcome {

	/**
	 * Tweets.
	 *
	 * @access public
	 * @var array
	 */
	public $tweets = array();

	/**
	 * Install type.
	 *
	 * @access public
	 * @var array
	 */
	public $install = '';

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		$this->tweets  = $this->get_tweets();
		$this->install = isset( $_GET['simcal_install'] ) ? esc_attr( $_GET['simcal_install'] ) : '';

		add_action( 'admin_menu', array( $this, 'welcome_page_tabs' ) );
		add_action( 'admin_head', array( $this, 'remove_submenu_pages' ) );
	}

	/**
	 * Add page screens.
	 */
	public function welcome_page_tabs() {

		$welcome_page_name  = __( 'About Simple Calendar', 'google-calendar-events' );
		$welcome_page_title = __( 'Welcome to Simple Calendar', 'google-calendar-events' );

		$page = isset( $_GET['page'] ) ? $_GET['page'] : 'simple-calendar_about';

		switch ( $page ) {

			case 'simple-calendar_about' :
				$page = add_dashboard_page(
					$welcome_page_title,
					$welcome_page_name,
					'manage_options',
					'simple-calendar_about',
					array( $this, 'about_screen' )
				);
				add_action( 'admin_print_styles-' . $page, array( $this, 'styles' ) );
				break;

			case 'simple-calendar_credits' :
				$page = add_dashboard_page(
					$welcome_page_title, $welcome_page_name,
					'manage_options',
					'simple-calendar_credits',
					array( $this, 'credits_screen' )
				);
				add_action( 'admin_print_styles-' . $page, array( $this, 'styles' ) );
				break;

			case 'simple-calendar_translators' :
				$page = add_dashboard_page(
					$welcome_page_title,
					$welcome_page_name,
					'manage_options',
					'simple-calendar_translators',
					array( $this, 'translators_screen' )
				);
				add_action( 'admin_print_styles-' . $page, array( $this, 'styles' ) );
				break;
		}

	}

	/**
	 * Remove dashboard page links.
	 */
	public function remove_submenu_pages() {
		remove_submenu_page( 'index.php', 'simple-calendar_about' );
		remove_submenu_page( 'index.php', 'simple-calendar_credits' );
		remove_submenu_page( 'index.php', 'simple-calendar_translators' );
	}

	/**
	 * Load styles.
	 */
	public function styles() {
		wp_enqueue_style(
			'simcal-activation',
			SIMPLE_CALENDAR_URL . '/assets/css/activation.css',
			array(),
			SIMPLE_CALENDAR_VERSION
		);
	}

	/**
	 * Tweets in random order.
	 *
	 * @return array
	 */
	private function get_tweets() {

		$tweets = array(
			sprintf( 'Simple Calendar is a great #WordPress plugin to manage and display calendars! %s', 'https://simplecalendar.io' ),
			sprintf( 'Simple Calendar is a cool plugin to manage and display calendars in your #WordPress site! %s', 'https://simplecalendar.io' ),
			sprintf( 'I use Simple Calendar to manage and display calendars in my #WordPress site! Check it out: %s', 'https://simplecalendar.io' ),
		);

		shuffle( $tweets );

		return $tweets;
	}

	/**
	 * Intro shown on every about page screen.
	 */
	private function intro() {

		$version = explode( '.', SIMPLE_CALENDAR_VERSION );
		unset( $version[ count( $version ) - 1 ] );
		$major_version = join( '.', $version );

		// Translators: %s prints the current major version of the plugin. ?>
		<h1><?php printf( __( 'Welcome to Simple Calendar %s', 'google-calendar-events' ), $major_version ); ?></h1>

		<div class="about-text calendar-about-text">
			<?php

			if ( 'fresh' == $this->install ) {
				$message = __( 'Thanks, all done!', 'google-calendar-events' );
			} elseif ( 'update' == $this->install ) {
				$message = __( 'Thank you for updating to the latest version!', 'google-calendar-events' );
			} else {
				$message = __( 'Thanks for installing!', 'google-calendar-events' );
			}

			echo $message;

			// Translators: %s prints the current major version of the plugin.
			printf( ' ' . __( 'Simple Calendar %s is more powerful, stable and secure than ever before. We hope you really enjoy using it.', 'google-calendar-events' ), $major_version );

			?>
		</div>

		<div class="simcal-badge"><?php printf( _x( 'Version %s', 'Plugin version', 'google-calendar-events' ), SIMPLE_CALENDAR_VERSION ); ?></div>

		<p>
			<a href="<?php echo admin_url( 'edit.php?post_type=calendar' ); ?>"
				class="button button-primary"
				><?php _e( 'Calendars', 'google-calendar-events' ); ?></a>
			<a href="<?php echo admin_url( 'admin.php?page=simple-calendar_settings' ); ?>"
				class="button button-primary"
				><?php _e( 'Settings', 'google-calendar-events' ); ?></a>
			<a href="<?php echo \SimpleCalendar\plugin()->get_url( 'docs' ); ?>"
				class="docs button button-primary"
				><?php _e( 'Docs', 'google-calendar-events' ); ?></a>
			<!--
			<a href="https://twitter.com/share"
				class="twitter-share-button" data-url="https://simplecalendar.io/"
				data-text="<?php echo esc_attr( $this->tweets[0] ); ?>"
				data-via="InsertTwitterHandle"
				data-size="large"
				>Tweet</a>
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			-->
		</p>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_about' ) echo 'nav-tab-active'; ?>"
				href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_about' ), 'index.php' ) ) ); ?>"
				><?php _e( "What's New", 'google-calendar-events' ); ?></a>
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_credits' ) echo 'nav-tab-active'; ?>"
				href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_credits' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Credits', 'google-calendar-events' ); ?></a>
			<a class="nav-tab <?php if ( $_GET['page'] == 'simple-calendar_translators' ) echo 'nav-tab-active'; ?>"
				href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'simple-calendar_translators' ), 'index.php' ) ) ); ?>"
				><?php _e( 'Translators', 'google-calendar-events' ); ?></a>
		</h2>
		<?php

	}

	/**
	 * Output the about screen.
	 */
	public function about_screen() {

		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap">

				<?php $this->intro(); ?>

				<hr />

				<ul>
					<li><a href="<?php echo admin_url( 'edit.php?post_type=calendar' ); ?>"><?php _e( 'Go to Calendars', 'google-calendar-events' ); ?></a></li>
					<li><a href="<?php echo admin_url( 'edit.php?post_type=calendar&page=simple-calendar_settings' ); ?>"><?php _e( 'Go to Settings', 'google-calendar-events' ); ?></a></li>
				</ul>

			</div>
		</div>
		<?php

	}

	/**
	 * Output the credits screen.
	 */
	public function credits_screen() {

		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap">
				<?php $this->intro(); ?>
				<p class="about-description">
					<?php printf(
						__( 'Simple Calendar is developed and maintained by a worldwide team of passionate individuals and backed by an awesome developer community. Want to see your name? <a href="%s">Contribute to Goolge Calendar Events</a>.', 'google-calendar-events' ),
						'https://github.com/moonstonemedia/WP-Google-Calendar-Events/blob/refactor/contributing.md'
					); ?>
				</p>
				<?php echo $this->contributors(); ?>
			</div>
		</div>
		<?php

	}

	/**
	 * Output the translators screen.
	 */
	public function translators_screen() {

		?>
		<div id="simcal-welcome">
			<div class="wrap about-wrap simcal-welcome">
				<?php $this->intro(); ?>
				<p class="about-description">
					<?php printf(
						__( 'Simple Calendar has been kindly translated into several other languages thanks to our translation team. Want to see your name? <a href="%s">Translate Simple Calendar</a>.', 'google-calendar-events' ),
						'https://www.transifex.com/moonstonemedia/simple-calendar/'
					); ?>
				</p>
				<?php

				// Transifex API is not open and requires authentication,
				// Otherwise something like this would be possible:
				// `json_decode( 'https://www.transifex.com/api/2/project/simple-calendar/languages/', true );`
				// Since this is not possible, this has to be done manually.

				?>
			</div>
		</div>
		<?php

	}

	/**
	 * Render Contributors List.
	 *
	 * @return string $contributor_list HTML formatted list of contributors.
	 */
	public function contributors() {

		$contributors = $this->get_contributors();

		if ( empty( $contributors ) ) {
			return '';
		}

		$contributor_list = '<ul class="wp-people-group">';

			foreach ( $contributors as $contributor ) {

				$contributor_list .= '<li class="wp-person">';
					$contributor_list .= sprintf(
						'<a href="%s" title="%s">%s</a>',
						esc_url( 'https://github.com/' . $contributor->login ),
						esc_html( sprintf( __( 'View %s', 'google-calendar-events' ), $contributor->login ) ),
						sprintf( '<img src="%s" width="64" height="64" class="gravatar" alt="%s" />', esc_url( $contributor->avatar_url ), esc_html( $contributor->login ) )
					);
					$contributor_list .= sprintf(
						'<a class="web" href="%s">%s</a>',
						esc_url( 'https://github.com/' . $contributor->login ),
						esc_html( $contributor->login )
					);
				$contributor_list .= '</li>';

			}

		$contributor_list .= '</ul>';

		return $contributor_list;
	}

	/**
	 * Retrieve list of contributors from GitHub.
	 *
	 * @return mixed
	 */
	public function get_contributors() {

		$contributors = get_transient( '_simple-calendar_contributors' );
		if ( false !== $contributors ) {
			return $contributors;
		}

		$response = wp_safe_remote_get(
			'https://api.github.com/repos/moonstonemedia/WP-Google-Calendar-Events/contributors'
		);
		if ( is_wp_error( $response ) || 200 != wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$contributors = json_decode( wp_remote_retrieve_body( $response ) );
		if ( ! is_array( $contributors ) ) {
			return array();
		}

		set_transient( '_simple-calendar_contributors', $contributors, HOUR_IN_SECONDS );

		return $contributors;
	}

}
