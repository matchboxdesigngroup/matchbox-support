<?php
/**
 * Main class for Matchbox Userback functionality.
 *
 * @package MatchboxSupport
 */

namespace MatchboxSupport;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Userback
 */
class Userback {

	/**
	 * Instance of the class.
	 *
	 * @var Userback
	 */
	private static $instance;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Get the singleton instance of the class.
	 *
	 * @return Userback
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'initialize_userback' ) );
	}

	/**
	 * Initialize Userback functionality.
	 */
	public function initialize_userback() {
		// Enqueue the custom JavaScript and CSS files.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) { // If administrator or editor.
			add_action( 'wp_footer', array( $this, 'print_script' ), 100 );
			add_action( 'admin_footer', array( $this, 'print_script' ), 100 );
			add_action( 'admin_bar_menu', array( $this, 'add_userback_toggle' ), 100 );
		}
	}

	/**
	 * Print the Userback script in the footer.
	 *
	 * This function retrieves the Userback access token from the options
	 * and embeds the Userback widget script into the footer of the page.
	 */
	public function print_script() {
		$access_token = get_option( 'matchbox_userback_token', 'default_token_if_not_set' );
		echo "<script type='text/javascript'>
			window.Userback = window.Userback || {};
			Userback.access_token = '{$access_token}';
			(function(d) {
				var s = d.createElement('script'); s.async = true;
				s.src = 'https://static.userback.io/widget/v1.js';
				(d.head || d.body).appendChild(s);
			})(document);
		</script>";
	}

	/**
	 * Add a toggle button to the WordPress admin bar for hiding or showing the Userback overlay
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar The WordPress Admin Bar object used to add custom nodes.
	 */
	public function add_userback_toggle( $wp_admin_bar ) {
		// Define the SVG icon used by Userback.
		$svg_icon = '
		<svg width="16" height="16" viewBox="0 0 30 24" xmlns="http://www.w3.org/2000/svg">
			<path d="M5.25 0.875H8.5V2.5H11.75V5.75H18.25V2.5H21.5V0.875H24.75V4.125H21.5V5.75V7.375H24.75V10.625H26.375V5.75H29.625V13.875H26.375V18.75H23.125V23.625H19.875H16.625V20.375H19.875V18.75H10.125V20.375H13.375V23.625H10.125H6.875V18.75H3.625V13.875H0.375V5.75H3.625V10.625H5.25V7.375H8.5V5.75V4.125H5.25V0.875ZM8.5 15.5H11.75V10.625H8.5V15.5ZM18.25 15.5H21.5V10.625H18.25V15.5Z"></path>
		</svg>';

		// Create the pill toggle markup.
		$toggle_markup = '
		<div class="matchbox-pill-toggle" id="matchbox-pill-toggle">
			<div class="toggle-icon">' . $svg_icon . '</div>
		</div>';

		$wp_admin_bar->add_node(
			array(
				'id' => 'matchbox_userback_toggle',
				'title' => $toggle_markup,
				'href' => '#',
				'meta' =>
					array(
						'onclick' => 'matchboxToggleUserback(); return false;',
						'title' => 'Show or hide the testing feedback overlay',
					),
				'parent' => 'top-secondary', // Moves it to the right side of the admin bar near "Howdy, admin".
			)
		);
	}

	/**
	 * Enqueue JavaScript and CSS assets for the Matchbox Userback functionality.
	 */
	public function enqueue_assets() {
		// Enqueue the JS file.
		wp_enqueue_script( 'matchbox-toggle-userback', plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/toggle-userback.js', array( 'jquery' ), MATCHBOX_SUPPORT_VERSION, true );

		// Enqueue the CSS file.
		wp_enqueue_style( 'matchbox-toggle-userback-style', plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/css/toggle-userback.css', array(), MATCHBOX_SUPPORT_VERSION, 'all' );
	}
}
