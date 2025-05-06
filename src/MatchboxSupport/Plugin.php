<?php
/**
 * Main plugin class for Matchbox Support.
 *
 * @since 1.0.0
 *
 * @package MatchboxSupport
 */

namespace MatchboxSupport;

defined( 'ABSPATH' ) || exit;

/**
 * Bootstraps the Matchbox Support plugin.
 *
 * @since 1.0.0
 */
class Plugin {
	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @var ?Plugin
	 */
	private static ?Plugin $instance = null;

	/**
	 * REST-API helper instance.
	 *
	 * @since 1.0.0
	 *
	 * @var API
	 */
	private API $api;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin Instance of the plugin class.
	 */
	public static function instance(): Plugin {
		return self::$instance ?? self::$instance = new self();
	}

	/**
	 * Constructor. Sets up hooks and helper classes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function __construct() {
		add_action(
			'init',
			function () {
				if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
					add_action( 'admin_footer', [ $this, 'add_matchbox_helpscout_beacon_to_admin_footer' ], 100 );
					add_action( 'wp_footer', [ $this, 'add_matchbox_helpscout_beacon_to_frontend_footer' ], 100 );
					add_action( 'admin_bar_menu', [ $this, 'matchbox_support_add_helpscout_toggle' ], 100 );

					add_action( 'wp_enqueue_scripts', [ $this, 'matchbox_support_enqueue_assets' ] );
					add_action( 'admin_enqueue_scripts', [ $this, 'matchbox_support_enqueue_assets' ] );
				}
			}
		);

		add_action( 'plugins_loaded', [ $this, 'matchbox_support_initialize_update_checker' ] );
		add_action( 'template_redirect', [ $this, 'disable_author_archive' ], 0 );

		$this->api = new API();
		$this->api->init();
	}

	/**
	 * Output the HelpScout beacon script in the **admin** footer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_matchbox_helpscout_beacon_to_admin_footer() {
		wp_enqueue_script(
			'matchbox-helpscout-beacon',
			plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/helpscout-beacon.js',
			[],
			'1.0',
			true
		);

		wp_localize_script(
			'matchbox-helpscout-beacon',
			'matchbox_helpscout_params',
			[
				'beacon_id' => defined( 'HELPSCOUT_BEACON_ID' ) ? HELPSCOUT_BEACON_ID : '',
			]
		);
	}

	/**
	 * Output the HelpScout beacon script in the **front-end** footer.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_matchbox_helpscout_beacon_to_frontend_footer() {
		wp_enqueue_script(
			'matchbox-helpscout-beacon',
			plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/helpscout-beacon.js',
			[],
			'1.0',
			true
		);

		wp_localize_script(
			'matchbox-helpscout-beacon',
			'matchbox_helpscout_params',
			[
				'beacon_id' => defined( 'HELPSCOUT_BEACON_ID' ) ? HELPSCOUT_BEACON_ID : '',
			]
		);
	}

	/**
	 * Add a toggle button for the HelpScout overlay to the admin-bar.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_Admin_Bar $wp_admin_bar Admin-bar instance.
	 *
	 * @return void
	 */
	public function matchbox_support_add_helpscout_toggle( $wp_admin_bar ) {
		$svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>';

		$wp_admin_bar->add_node(
			[
				'id'     => 'matchbox_helpscout_toggle',
				'title'  => '<div class="matchbox-support-pill-toggle" id="matchbox-support-pill-toggle"><div class="toggle-icon">' . $svg_icon . '</div></div>',
				'href'   => '#',
				'meta'   => [
					'onclick' => 'matchboxToggleHelpscout(); return false;',
					'title'   => esc_attr__( 'Show or hide the Matchbox Support overlay', 'matchbox' ),
				],
				'parent' => 'top-secondary',
			]
		);
	}

	/**
	 * Enqueue plugin assets (JS & CSS).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function matchbox_support_enqueue_assets() {
		wp_enqueue_script(
			'matchbox-toggle-helpscout',
			plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/js/toggle-helpscout.js',
			[ 'jquery' ],
			'1.0',
			true
		);

		wp_enqueue_style(
			'matchbox-toggle-helpscout-style',
			plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/css/toggle-helpscout.css',
			[],
			'1.0'
		);
	}

	/**
	 * Set-up the Plugin-Update-Checker for GitHub releases.
	 *
	 * @since 0.3.0
	 *
	 * @return void
	 */
	public function matchbox_support_initialize_update_checker() {
		$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/matchboxdesigngroup/matchbox-support',
			__FILE__,
			'matchbox-support'
		);

		$update_checker->getVcsApi()->enableReleaseAssets();
	}

	/**
	 * Disable author archive pages (404 them by default).
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function disable_author_archive() {

		/**
		 * Filter whether author archives should be disabled.
		 *
		 * Return `false` to keep author archives accessible.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $disable_author Whether to disable author archives.
		 */
		$disable_author = apply_filters( 'matchbox_disable_author_archive', true );

		if ( is_author() && $disable_author ) {

			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();

			if ( $template = get_404_template() ) {
				include $template;
			}

			exit;
		}
	}
}
