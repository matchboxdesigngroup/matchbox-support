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
	 * Plugin path.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin_path;

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
		$this->plugin_path = MATCHBOX_SUPPORT_DIR . '/';

		add_action(
			'init',
			function () {
				if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
					$beacon_id = get_option( 'matchbox_helpscout_beacon_id', '' );
					$beacon_is_valid = get_option( 'matchbox_helpscout_beacon_id_valid', false );
					if ( empty( $beacon_id ) || ! $beacon_is_valid ) {
						return;
					}
					add_action( 'admin_footer', [ $this, 'add_matchbox_helpscout_beacon_to_admin_footer' ], 100 );
					add_action( 'wp_footer', [ $this, 'add_matchbox_helpscout_beacon_to_frontend_footer' ], 100 );
					add_action( 'admin_bar_menu', [ $this, 'matchbox_support_add_helpscout_toggle' ], 100 );

					add_action( 'wp_enqueue_scripts', [ $this, 'matchbox_support_enqueue_assets' ] );
					add_action( 'admin_enqueue_scripts', [ $this, 'matchbox_support_enqueue_assets' ] );
				}
			}
		);

		add_action( 'plugins_loaded', [ $this, 'matchbox_support_initialize_update_checker' ] );
		add_action( 'plugins_loaded', [ $this, 'init_helpers' ], 1 );
		add_action( 'template_redirect', [ $this, 'disable_author_archive' ], 0 );

		add_action( 'admin_menu', [ $this, 'settings_menu' ] );
		add_action( 'admin_init', [ $this, 'settings_init' ] );
		add_action( 'admin_init', [ $this, 'disable_block_recommendations' ] );

		add_action( 'init', [ $this, 'register_blocks' ] );

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
			MATCHBOX_SUPPORT_VERSION,
			true
		);

		wp_localize_script(
			'matchbox-helpscout-beacon',
			'matchbox_helpscout_params',
			[ 
				'beacon_id' => get_option( 'matchbox_helpscout_beacon_id', '' ),
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
			MATCHBOX_SUPPORT_VERSION,
			true
		);

		wp_localize_script(
			'matchbox-helpscout-beacon',
			'matchbox_helpscout_params',
			[ 
				'beacon_id' => get_option( 'matchbox_helpscout_beacon_id', '' ),
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
			MATCHBOX_SUPPORT_VERSION,
			true
		);

		wp_enqueue_style(
			'matchbox-toggle-helpscout-style',
			plugin_dir_url( MATCHBOX_SUPPORT_FILE ) . 'assets/css/toggle-helpscout.css',
			[],
			MATCHBOX_SUPPORT_VERSION
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
			MATCHBOX_SUPPORT_FILE,
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

	/**
	 * Initializes helper functions.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_helpers() {
		require_once $this->plugin_path . 'src/functions/utils.php';
	}

	/**
	 * Adds a settings page under the WordPress Settings menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings_menu() {
		add_options_page(
			'Matchbox Support Settings',
			'Matchbox Support',
			'manage_options',
			'matchbox-support',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Renders the Matchbox Support settings page content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_page() {
		echo '<div class="wrap">';
		echo '<h1>Matchbox Support Settings</h1>';
		echo '<form action="options.php" method="post">';
		settings_fields( 'matchbox-support' );
		do_settings_sections( 'matchbox-support' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Registers Matchbox Support plugin settings and settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings_init() {
		register_setting( 'matchbox-support', 'matchbox_userback_token' );
		register_setting( 'matchbox-support', 'matchbox_helpscout_beacon_id' );

		add_settings_section(
			'matchbox_support_settings_section',
			'Userback and HelpScout Settings',
			[ $this, 'settings_section_cb' ],
			'matchbox-support'
		);

		add_settings_field(
			'matchbox_userback_token',
			'Userback Access Token',
			[ $this, 'userback_token_field_cb' ],
			'matchbox-support',
			'matchbox_support_settings_section'
		);

		add_settings_field(
			'matchbox_helpscout_beacon_id',
			'HelpScout Beacon ID',
			[ $this, 'helpscout_beacon_id_field_cb' ],
			'matchbox-support',
			'matchbox_support_settings_section'
		);

		add_filter( 'pre_update_option_matchbox_userback_token', [ $this, 'validate_userback_token' ], 10, 2 );
		add_filter( 'pre_update_option_matchbox_helpscout_beacon_id', [ $this, 'validate_helpscout_beacon_id' ], 10, 2 );
	}



	/**
	 * Callback for the settings section description.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function settings_section_cb() {
		echo '<p>Configure your Matchbox Support settings below.</p>';
	}

	/**
	 * Callback for rendering the Userback Access Token input field.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function userback_token_field_cb() {
		$value = get_option( 'matchbox_userback_token' );
		echo '<input type="text" id="matchbox_userback_token" name="matchbox_userback_token" value="' . esc_attr( $value ) . '" />';
		if ( get_option( 'matchbox_userback_token_valid' ) ) {
			echo '<span style="display:inline-block; vertical-align:middle; margin-left:8px;" title="Valid token"><svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle;"><circle cx="10" cy="10" r="9" fill="#28a745"/><path d="M6 10.5l2.5 2.5 5-5" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
		} else {
			echo '<span style="display:inline-block; vertical-align:middle; margin-left:8px;" title="Invalid token"><svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle;"><circle cx="10" cy="10" r="9" fill="#dc3545"/><path d="M6 6l8 8M14 6l-8 8" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
		}
	}

	/**
	 * Callback for rendering the HelpScout Beacon ID input field.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function helpscout_beacon_id_field_cb() {
		$value = get_option( 'matchbox_helpscout_beacon_id' );
		echo '<input type="text" id="matchbox_helpscout_beacon_id" name="matchbox_helpscout_beacon_id" value="' . esc_attr( $value ) . '" />';
		if ( get_option( 'matchbox_helpscout_beacon_id_valid' ) ) {
			echo '<span style="display:inline-block; vertical-align:middle; margin-left:8px;" title="Valid token"><svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle;"><circle cx="10" cy="10" r="9" fill="#28a745"/><path d="M6 10.5l2.5 2.5 5-5" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
		} else {
			echo '<span style="display:inline-block; vertical-align:middle; margin-left:8px;" title="Invalid token"><svg width="20" height="20" viewBox="0 0 20 20" style="vertical-align:middle;"><circle cx="10" cy="10" r="9" fill="#dc3545"/><path d="M6 6l8 8M14 6l-8 8" stroke="#fff" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
		}
	}

	/**
	 * Disables block recommendations from 3rd-party plugins in the block editor.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function disable_block_recommendations() {
		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
	}

	/**
	 * Register all blocks defined in the build/blocks directory.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_blocks() {
		// Get all block.json files in the build/blocks directory.
		$block_folders = glob( plugin_dir_path( dirname( __DIR__ ) ) . 'build/blocks/*', GLOB_ONLYDIR );

		foreach ( $block_folders as $block_folder ) {
			// Skip registering the userback-toggle block if the Userback token is not set.
			if ( strpos( $block_folder, 'userback-toggle' ) !== false ) {
				$userback_token = get_option( 'matchbox_userback_token' );
				$is_valid       = get_option( 'matchbox_userback_token_valid' );
				if ( empty( $userback_token ) || ! $is_valid ) {
					continue;
				}
			}

			if ( file_exists( $block_folder . '/block.json' ) ) {
				register_block_type( $block_folder );
			}
		}
	}

	/**
	 * Validates the Userback access token by making a request to the Userback API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_value The new value of the option.
	 * @param string $old_value The old value of the option.
	 *
	 * @return string The sanitized new value if valid, otherwise the old value.
	 */
	public function validate_userback_token( $new_value, $old_value ) {
		if ( empty( $new_value ) ) {
			delete_option( 'matchbox_userback_token_valid' );
			return $old_value;
		}

		$response = wp_remote_post(
			'https://api.userback.io/?jsSnippetLoad',
			[
				'body' => [ 
					'action' => 'js_snippet/load',
					'load_type' => 'web',
					'access_token' => sanitize_text_field( $new_value ),
				],
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				'matchbox_userback_token',
				'invalid_token',
				'Error connecting to Userback API.',
				'error'
			);
			update_option( 'matchbox_userback_token_valid', false );

			return $old_value;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( false === $json ) {
			add_settings_error(
				'matchbox_userback_token',
				'invalid_token',
				'Invalid Userback access token. Please check and try again.',
				'error'
			);
			update_option( 'matchbox_userback_token_valid', false );

			return $old_value;
		}

		update_option( 'matchbox_userback_token_valid', true );

		return sanitize_text_field( $new_value );
	}

	/**
	 * Validates the HelpScout Beacon ID by making a request to the HelpScout API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_value The new value of the option.
	 * @param string $old_value The old value of the option.
	 *
	 * @return string The sanitized new value if valid, otherwise the old value.
	 */
	public function validate_helpscout_beacon_id( $new_value, $old_value ) {
		if ( empty( $new_value ) ) {
			delete_option( 'matchbox_helpscout_beacon_id_valid' );
			return $old_value;
		}

		$response = wp_remote_get( 'https://d3hb14vkzrxvla.cloudfront.net/v1/' . sanitize_text_field( $new_value ) );

		if ( is_wp_error( $response ) ) {
			add_settings_error(
				'matchbox_helpscout_beacon_id',
				'invalid_beacon_id',
				'Error connecting to HelpScout API.',
				'error'
			);
			update_option( 'matchbox_helpscout_beacon_id_valid', false );
			return $old_value;
		}

		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) || 'Not Found' === $json['message'] ) {
			add_settings_error(
				'matchbox_helpscout_beacon_id',
				'invalid_beacon_id',
				'Invalid HelpScout Beacon ID. Please check and try again.',
				'error'
			);
			update_option( 'matchbox_helpscout_beacon_id_valid', false );
			return $old_value;
		}

		update_option( 'matchbox_helpscout_beacon_id_valid', true );
		return sanitize_text_field( $new_value );
	}
}
