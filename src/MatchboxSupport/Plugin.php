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
	 * Login-security helper instance.
	 *
	 * @since 2.1.0
	 *
	 * @var LoginSecurity
	 */
	private LoginSecurity $login_security;

	/**
	 * Security-headers helper instance.
	 *
	 * @since 2.1.0
	 *
	 * @var SecurityHeaders
	 */
	private SecurityHeaders $security_headers;

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

		$this->login_security = new LoginSecurity();
		$this->login_security->init();

		$this->security_headers = new SecurityHeaders();
		$this->security_headers->init();
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
		if ( function_exists( 'settings_errors' ) ) {
			settings_errors();
		}
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
		register_setting(
			'matchbox-support',
			'matchbox_image_forward_base_url',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_image_forward_base_url' ],
				'default'           => '',
			]
		);
		register_setting(
			'matchbox-support',
			'matchbox_image_forward_scope',
			[
				'type'              => 'string',
				'sanitize_callback' => [ $this, 'sanitize_image_forward_scope' ],
				'default'           => 'non_production',
			]
		);

		add_settings_section(
			'matchbox_support_settings_section',
			'Userback and HelpScout Settings',
			[ $this, 'settings_section_cb' ],
			'matchbox-support'
		);

		add_settings_section(
			'matchbox_support_image_forward_section',
			'Image forwarding',
			[ $this, 'image_forwarding_section_cb' ],
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

		add_settings_field(
			'matchbox_image_forward_base_url',
			'Forward base URL',
			[ $this, 'image_forward_base_url_field_cb' ],
			'matchbox-support',
			'matchbox_support_image_forward_section'
		);

		add_settings_field(
			'matchbox_image_forward_scope',
			'When to apply',
			[ $this, 'image_forward_scope_field_cb' ],
			'matchbox-support',
			'matchbox_support_image_forward_section'
		);

		add_filter( 'pre_update_option_matchbox_userback_token', [ $this, 'validate_userback_token' ], 10, 2 );
		add_filter( 'pre_update_option_matchbox_helpscout_beacon_id', [ $this, 'validate_helpscout_beacon_id' ], 10, 2 );

		register_setting( 'matchbox-support', 'matchbox_enable_hibp_check' );
		register_setting( 'matchbox-support', 'matchbox_hide_weak_password_checkbox' );
		register_setting(
			'matchbox-support',
			'matchbox_blocked_usernames',
			array( 'sanitize_callback' => array( $this, 'sanitize_blocked_usernames' ) )
		);

		add_settings_section(
			'matchbox_login_security_section',
			'Login Security',
			[ $this, 'login_security_section_cb' ],
			'matchbox-support'
		);

		add_settings_field(
			'matchbox_enable_hibp_check',
			'Have I Been Pwned Check',
			[ $this, 'hibp_check_field_cb' ],
			'matchbox-support',
			'matchbox_login_security_section'
		);

		add_settings_field(
			'matchbox_hide_weak_password_checkbox',
			'Weak Password Checkbox',
			[ $this, 'hide_weak_password_field_cb' ],
			'matchbox-support',
			'matchbox_login_security_section'
		);

		add_settings_field(
			'matchbox_blocked_usernames',
			'Blocked Usernames',
			[ $this, 'blocked_usernames_field_cb' ],
			'matchbox-support',
			'matchbox_login_security_section'
		);

		register_setting( 'matchbox-support', 'matchbox_header_hsts' );
		register_setting( 'matchbox-support', 'matchbox_header_xcto' );
		register_setting( 'matchbox-support', 'matchbox_header_xfo' );
		register_setting( 'matchbox-support', 'matchbox_header_xfo_value' );
		register_setting( 'matchbox-support', 'matchbox_header_referrer' );
		register_setting( 'matchbox-support', 'matchbox_header_referrer_value' );
		register_setting( 'matchbox-support', 'matchbox_header_permissions' );
		register_setting( 'matchbox-support', 'matchbox_header_permissions_value' );
		register_setting( 'matchbox-support', 'matchbox_header_csp' );
		register_setting( 'matchbox-support', 'matchbox_header_csp_value' );

		add_settings_section(
			'matchbox_security_headers_section',
			'Security Headers',
			[ $this, 'security_headers_section_cb' ],
			'matchbox-support'
		);

		add_settings_field(
			'matchbox_header_hsts',
			'Strict-Transport-Security',
			[ $this, 'header_hsts_field_cb' ],
			'matchbox-support',
			'matchbox_security_headers_section'
		);

		add_settings_field(
			'matchbox_header_xcto',
			'X-Content-Type-Options',
			[ $this, 'header_xcto_field_cb' ],
			'matchbox-support',
			'matchbox_security_headers_section'
		);

		add_settings_field(
			'matchbox_header_xfo',
			'X-Frame-Options',
			[ $this, 'header_xfo_field_cb' ],
			'matchbox-support',
			'matchbox_security_headers_section'
		);

		add_settings_field(
			'matchbox_header_referrer',
			'Referrer-Policy',
			[ $this, 'header_referrer_field_cb' ],
			'matchbox-support',
			'matchbox_security_headers_section'
		);

		add_settings_field(
			'matchbox_header_permissions',
			'Permissions-Policy',
			[ $this, 'header_permissions_field_cb' ],
			'matchbox-support',
			'matchbox_security_headers_section'
		);

		add_settings_field(
			'matchbox_header_csp',
			'Content-Security-Policy',
			[ $this, 'header_csp_field_cb' ],
			'matchbox-support',
			'matchbox_security_headers_section'
		);
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
	 * Image forwarding section description.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function image_forwarding_section_cb() {
		echo '<p>Rewrite media URLs on this site to load from another domain (for example production) so local or staging environments can display images that only exist remotely.</p>';
		echo '<p>Set <code>WP_ENVIRONMENT_TYPE</code> in <code>wp-config.php</code> (e.g. <code>local</code>, <code>staging</code>, <code>production</code>). With <strong>Non-production only</strong>, forwarding never runs when the environment type is <code>production</code>.</p>';
		echo '<p>You may define <code>MATCHBOX_IMAGE_FORWARD_URL</code> in <code>wp-config.php</code> to override the saved base URL (useful without a database option on local installs). Deactivate the separate &ldquo;MDG Image Forwarding&rdquo; plugin if you use this feature here.</p>';
		echo '<p><strong>Note:</strong> URLs stored inside post HTML are not rewritten; this affects attachment APIs and markup generated from attachment IDs.</p>';
	}

	/**
	 * Sanitize image forward base URL setting.
	 *
	 * @since TBD
	 *
	 * @param mixed $value Submitted value.
	 * @return string Stored URL with trailing slash, or empty string.
	 */
	public function sanitize_image_forward_base_url( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		$url = esc_url_raw( $value );
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			add_settings_error(
				'matchbox_image_forward_base_url',
				'matchbox_image_forward_url_scheme',
				__( 'Image forward URL must start with http:// or https://.', 'matchbox-support' ),
				'error'
			);
			return (string) get_option( 'matchbox_image_forward_base_url', '' );
		}

		if ( ! wp_http_validate_url( $url ) ) {
			add_settings_error(
				'matchbox_image_forward_base_url',
				'matchbox_image_forward_url_invalid',
				__( 'Image forward URL is not a valid URL.', 'matchbox-support' ),
				'error'
			);
			return (string) get_option( 'matchbox_image_forward_base_url', '' );
		}

		return trailingslashit( $url );
	}

	/**
	 * Sanitize image forward scope setting.
	 *
	 * @since TBD
	 *
	 * @param mixed $value Submitted value.
	 * @return string One of off, all, non_production.
	 */
	public function sanitize_image_forward_scope( $value ) {
		$allowed = [ 'off', 'all', 'non_production' ];
		if ( ! is_string( $value ) || ! in_array( $value, $allowed, true ) ) {
			return 'non_production';
		}
		return $value;
	}

	/**
	 * Forward base URL field.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function image_forward_base_url_field_cb() {
		$value = get_option( 'matchbox_image_forward_base_url', '' );
		if ( ! is_string( $value ) ) {
			$value = '';
		}
		echo '<input type="url" class="regular-text code" id="matchbox_image_forward_base_url" name="matchbox_image_forward_base_url" value="' . esc_attr( untrailingslashit( $value ) ) . '" placeholder="https://example.com" />';
		echo '<p class="description">' . esc_html__( 'Full site URL to load media from (scheme + host, optional path). Trailing slash is optional.', 'matchbox-support' ) . '</p>';
	}

	/**
	 * Forward scope field.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function image_forward_scope_field_cb() {
		$value = get_option( 'matchbox_image_forward_scope', 'non_production' );
		if ( ! is_string( $value ) || ! in_array( $value, [ 'off', 'all', 'non_production' ], true ) ) {
			$value = 'non_production';
		}
		$opts = [
			'off'             => __( 'Off', 'matchbox-support' ),
			'all'             => __( 'All environments', 'matchbox-support' ),
			'non_production'  => __( 'Non-production only (local, development, staging)', 'matchbox-support' ),
		];
		echo '<select id="matchbox_image_forward_scope" name="matchbox_image_forward_scope">';
		foreach ( $opts as $key => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Recommended: Non-production only, with WP_ENVIRONMENT_TYPE set correctly on each install.', 'matchbox-support' ) . '</p>';
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
	 * Callback for the Login Security settings section description.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function login_security_section_cb() {
		echo '<p>Configure login security settings. Generic usernames and common passwords are always blocked.</p>';
	}

	/**
	 * Callback for rendering the HIBP check toggle field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function hibp_check_field_cb() {
		$value = get_option( 'matchbox_enable_hibp_check', false );
		echo '<label for="matchbox_enable_hibp_check">';
		echo '<input type="checkbox" id="matchbox_enable_hibp_check" name="matchbox_enable_hibp_check" value="1"' . checked( $value, '1', false ) . ' />';
		echo ' Enable Have I Been Pwned password check';
		echo '</label>';
		echo '<p class="description">When enabled, passwords are checked against the <a href="https://haveibeenpwned.com/Passwords" target="_blank" rel="noopener noreferrer">Have I Been Pwned</a> database on login using a privacy-preserving API (k-anonymity). The full password is never sent over the network.</p>';
	}

	/**
	 * Callback for rendering the hide weak password checkbox toggle field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function hide_weak_password_field_cb() {
		$value = get_option( 'matchbox_hide_weak_password_checkbox', false );
		echo '<label for="matchbox_hide_weak_password_checkbox">';
		echo '<input type="checkbox" id="matchbox_hide_weak_password_checkbox" name="matchbox_hide_weak_password_checkbox" value="1"' . checked( $value, '1', false ) . ' />';
		echo ' Hide "Confirm use of weak password" checkbox';
		echo '</label>';
		echo '<p class="description">When enabled, the checkbox that allows users to bypass the weak password warning is hidden on profile and password reset screens.</p>';
	}

	/**
	 * Callback for rendering the blocked usernames textarea field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function blocked_usernames_field_cb() {
		$value = get_option( 'matchbox_blocked_usernames', '' );
		echo '<textarea id="matchbox_blocked_usernames" name="matchbox_blocked_usernames" rows="10" cols="40" placeholder="' . esc_attr( implode( "\n", LoginSecurity::BLOCKED_USERNAMES ) ) . '">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">One username per line. Usernames are case-insensitive. Leave blank to use the built-in default list.</p>';
	}

	/**
	 * Sanitize the blocked usernames option.
	 *
	 * @since 2.1.0
	 *
	 * @param string $value Raw textarea value.
	 *
	 * @return string Sanitized newline-separated list of lowercase usernames.
	 */
	public function sanitize_blocked_usernames( string $value ): string {
		$lines = array_filter(
			array_map(
				fn( $line ) => strtolower( sanitize_text_field( trim( $line ) ) ),
				explode( "\n", $value )
			)
		);
		return implode( "\n", array_values( $lines ) );
	}

	/**
	 * Callback for the Security Headers settings section description.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function security_headers_section_cb() {
		echo '<p>Configure HTTP security headers sent on all front-end, admin, and login pages. All headers default to <strong>off</strong> — enabling them on an existing site is safe, but review each header\'s value before enabling.</p>';
	}

	/**
	 * Callback for rendering the Strict-Transport-Security header field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function header_hsts_field_cb() {
		$enabled = get_option( 'matchbox_header_hsts', false );
		echo '<label for="matchbox_header_hsts">';
		echo '<input type="checkbox" id="matchbox_header_hsts" name="matchbox_header_hsts" value="1"' . checked( $enabled, '1', false ) . ' />';
		echo ' Enable Strict-Transport-Security header';
		echo '</label>';
		echo '<p class="description">Fixed value: <code>max-age=31536000; includeSubDomains</code><br><strong>Warning:</strong> Only enable this on sites fully served over HTTPS. Once sent, browsers will refuse HTTP connections for the duration of <code>max-age</code>.</p>';
	}

	/**
	 * Callback for rendering the X-Content-Type-Options header field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function header_xcto_field_cb() {
		$enabled = get_option( 'matchbox_header_xcto', false );
		echo '<label for="matchbox_header_xcto">';
		echo '<input type="checkbox" id="matchbox_header_xcto" name="matchbox_header_xcto" value="1"' . checked( $enabled, '1', false ) . ' />';
		echo ' Enable X-Content-Type-Options header';
		echo '</label>';
		echo '<p class="description">Fixed value: <code>nosniff</code>. Prevents browsers from MIME-sniffing responses away from the declared content type.</p>';
	}

	/**
	 * Callback for rendering the X-Frame-Options header field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function header_xfo_field_cb() {
		$enabled = get_option( 'matchbox_header_xfo', false );
		$value   = get_option( 'matchbox_header_xfo_value', 'SAMEORIGIN' );
		echo '<label for="matchbox_header_xfo">';
		echo '<input type="checkbox" id="matchbox_header_xfo" name="matchbox_header_xfo" value="1"' . checked( $enabled, '1', false ) . ' />';
		echo ' Enable X-Frame-Options header';
		echo '</label>';
		echo '<br><br>';
		echo '<select id="matchbox_header_xfo_value" name="matchbox_header_xfo_value">';
		foreach ( array( 'SAMEORIGIN', 'DENY' ) as $option ) {
			echo '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">Prevents the site from being embedded in an <code>&lt;iframe&gt;</code>. <code>SAMEORIGIN</code> allows same-origin embeds; <code>DENY</code> blocks all framing.</p>';
	}

	/**
	 * Callback for rendering the Referrer-Policy header field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function header_referrer_field_cb() {
		$enabled = get_option( 'matchbox_header_referrer', false );
		$value   = get_option( 'matchbox_header_referrer_value', 'strict-origin-when-cross-origin' );
		$options = array(
			'strict-origin-when-cross-origin',
			'no-referrer',
			'same-origin',
			'origin',
			'no-referrer-when-downgrade',
		);
		echo '<label for="matchbox_header_referrer">';
		echo '<input type="checkbox" id="matchbox_header_referrer" name="matchbox_header_referrer" value="1"' . checked( $enabled, '1', false ) . ' />';
		echo ' Enable Referrer-Policy header';
		echo '</label>';
		echo '<br><br>';
		echo '<select id="matchbox_header_referrer_value" name="matchbox_header_referrer_value">';
		foreach ( $options as $option ) {
			echo '<option value="' . esc_attr( $option ) . '"' . selected( $value, $option, false ) . '>' . esc_html( $option ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">Controls how much referrer information is included with requests. <code>strict-origin-when-cross-origin</code> is the recommended default.</p>';
	}

	/**
	 * Callback for rendering the Permissions-Policy header field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function header_permissions_field_cb() {
		$enabled = get_option( 'matchbox_header_permissions', false );
		$value   = get_option( 'matchbox_header_permissions_value', 'camera=(), microphone=(), geolocation=()' );
		echo '<label for="matchbox_header_permissions">';
		echo '<input type="checkbox" id="matchbox_header_permissions" name="matchbox_header_permissions" value="1"' . checked( $enabled, '1', false ) . ' />';
		echo ' Enable Permissions-Policy header';
		echo '</label>';
		echo '<br><br>';
		echo '<textarea id="matchbox_header_permissions_value" name="matchbox_header_permissions_value" rows="3" cols="60">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description">Restricts access to browser features. Default disables camera, microphone, and geolocation. Each directive follows the format <code>feature=()</code> to deny or <code>feature=(*)</code> to allow.</p>';
	}

	/**
	 * Callback for rendering the Content-Security-Policy header field.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function header_csp_field_cb() {
		$enabled = get_option( 'matchbox_header_csp', false );
		$value   = get_option( 'matchbox_header_csp_value', 'upgrade-insecure-requests' );
		echo '<label for="matchbox_header_csp">';
		echo '<input type="checkbox" id="matchbox_header_csp" name="matchbox_header_csp" value="1"' . checked( $enabled, '1', false ) . ' />';
		echo ' Enable Content-Security-Policy header';
		echo '</label>';
		echo '<br><br>';
		echo '<textarea id="matchbox_header_csp_value" name="matchbox_header_csp_value" rows="3" cols="60">' . esc_textarea( $value ) . '</textarea>';
		echo '<p class="description"><strong>Warning:</strong> A strict CSP can break site functionality (scripts, styles, inline code). The default <code>upgrade-insecure-requests</code> is safe for most sites. Test carefully before deploying a custom policy.</p>';
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
