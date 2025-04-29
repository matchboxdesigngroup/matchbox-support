<?php
/**
 * Main class for Matchbox Support.
 *
 * @package matchbox-support
 * @since   2.0.0
 */

namespace Matchbox_Support;

defined( 'ABSPATH' ) || exit;

/**
 * Main class for Matchbox Support.
 * Handles the initialization and core functionality of the plugin.
 */
class Matchbox_Support_Main {

	/**
	 * Instance
	 *
	 * @since 2.0.0
	 * @access private
	 * @static
	 *
	 * @var $_instance object The single instance of the class.
	 */
	private static $instance = null;

	/**
	 * Singleton instance.
	 *
	 * @return Matchbox_Support_Main
	 */
	public static function instance(): Matchbox_Support_Main {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Matchbox_Support_Main constructor.
	 */
	public function __construct() {
		add_action(
			'init',
			function () {
				if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
					add_action( 'admin_footer', array( $this, 'add_matchbox_helpscout_beacon_to_admin_footer' ), 100 );
					add_action( 'wp_footer', array( $this, 'add_matchbox_helpscout_beacon_to_frontend_footer' ), 100 );
					add_action( 'admin_bar_menu', array( $this, 'matchbox_support_add_helpscout_toggle' ), 100 );

					// Enqueue the custom JavaScript and CSS files.
					add_action( 'wp_enqueue_scripts', array( $this, 'matchbox_support_enqueue_assets' ) );
					add_action( 'admin_enqueue_scripts', array( $this, 'matchbox_support_enqueue_assets' ) );
				}
			}
		);
		add_action( 'plugins_loaded', array( $this, 'matchbox_support_initialize_update_checker' ) );
		add_action( 'template_redirect', [ $this, 'disable_author_archive' ], 0 );
	}

	/**
	 * Add Matchbox HelpScout Beacon to the admin footer.
	 *
	 * Embed JavaScript code to load and initialize the HelpScout Beacon
	 * for communication and support purposes in the WordPress admin footer.
	 *
	 * @since 1.0.0
	 */
	public function add_matchbox_helpscout_beacon_to_admin_footer() {
		// Enqueue the JavaScript file.
		wp_enqueue_script( 'matchbox-helpscout-beacon', plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/helpscout-beacon.js', array(), '1.0', true );

		// Pass the beacon_id to the JavaScript file.
		wp_localize_script(
			'matchbox-helpscout-beacon',
			'matchbox_helpscout_params',
			array(
				'beacon_id' => defined( 'HELPSCOUT_BEACON_ID' ) ? HELPSCOUT_BEACON_ID : '',
			)
		);
	}

	/**
	 * Add Matchbox HelpScout Beacon to the front-end footer for site administrators.
	 *
	 * Embed JavaScript code to load and initialize the HelpScout Beacon
	 * for communication and support purposes in the WordPress front-end footer.
	 * Display only for site administrators.
	 *
	 * @since 1.0.0
	 */
	public function add_matchbox_helpscout_beacon_to_frontend_footer() {
		// Check if the current user is an administrator.
		wp_enqueue_script( 'matchbox-helpscout-beacon', plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/helpscout-beacon.js', array(), '1.0', true );

		// Pass the beacon_id to the JavaScript file.
		wp_localize_script(
			'matchbox-helpscout-beacon',
			'matchbox_helpscout_params',
			array(
				'beacon_id' => defined( 'HELPSCOUT_BEACON_ID' ) ? HELPSCOUT_BEACON_ID : '',
			)
		);
	}

	/**
	 * Add a toggle button to the WordPress admin bar for hiding or showing the Helpscout overlay
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar WP Admin Bar.
	 */
	public function matchbox_support_add_helpscout_toggle( $wp_admin_bar ) {
		// Define the SVG icon used by Helpscout.
		$svg_icon = '
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
    ';

		// Create the pill toggle markup.
		$toggle_markup = '
        <div class="matchbox-support-pill-toggle" id="matchbox-support-pill-toggle">
            <div class="toggle-icon">' . $svg_icon . '</div>
        </div>
    ';

		$wp_admin_bar->add_node(
			array(
				'id' => 'matchbox_helpscout_toggle',
				'title' => $toggle_markup,
				'href' => '#',
				'meta' =>
					array(
						'onclick' => 'matchboxToggleHelpscout(); return false;',
						'title' => 'Show or hide the Matchbox Support overlay',
					),
				'parent' => 'top-secondary', // Moves it to the right side of the admin bar near "Howdy, admin".
			)
		);
	}

	/**
	 * Enqueue the assets for the Matchbox Support plugin.
	 *
	 * Load the necessary JavaScript and CSS files for the plugin to work.
	 *
	 * @since 1.0.0
	 */
	public function matchbox_support_enqueue_assets() {
		// Enqueue the JS file.
		wp_enqueue_script( 'matchbox-toggle-helpscout', plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/toggle-helpscout.js', array( 'jquery' ), '1.0', true );

		// Enqueue the CSS file.
		wp_enqueue_style( 'matchbox-toggle-helpscout-style', plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/css/toggle-helpscout.css', array(), '1.0', 'all' );
	}

	/**
	 * Initialize Plugin Update Checker for GitHub-hosted updates.
	 *
	 * This function sets up the Plugin Update Checker (PUC) to check for plugin updates from
	 * the specified GitHub repository. It is configured to look for the latest release
	 * of the plugin, allowing the plugin to automatically fetch updates when a new version
	 * is tagged in GitHub.
	 *
	 * @link https://github.com/YahnisElsts/plugin-update-checker?tab=readme-ov-file#github-integration
	 * @return void
	 * @since 0.3.0
	 */
	public function matchbox_support_initialize_update_checker() {
		// Initialize the update checker for the GitHub-hosted plugin.
		$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/matchboxdesigngroup/matchbox-support',
			__FILE__,
			'matchbox-support'
		);

		// Configure the update checker to look for GitHub release assets.
		$update_checker->getVcsApi()->enableReleaseAssets();
	}

	/**
	 * Disable the author archive.
	 *
	 * Fire early on `template_redirect` so the normal template loader never runs.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function disable_author_archive() {
		/*
		* Filter:
		* --------
		* Return `false` from your (child-)theme to KEEP the author archive.
		*
		* add_filter( 'matchbox_disable_author_archive', '__return_false' );
		*/
		$disable_author = apply_filters( 'matchbox_disable_author_archive', true );

		// Only 404 real author archives that we *do* want to disable.
		if ( is_author() && $disable_author ) {

			// Tell WP this is a 404 and send the proper HTTP header.
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();

			// Load the 404 template if it exists and stop execution.
			if ( $template = get_404_template() ) {
				include $template;
			}
			exit;
		}
	}
}
