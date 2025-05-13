<?php
/**
 * Handles REST-API customisations.
 *
 * @since 1.0.0
 *
 * @package MatchboxSupport
 */

namespace MatchboxSupport;

/**
 * REST-API helper class.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * Default value for API restriction.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $option_default = 'users';

	/**
	 * Initialise all hooks for the REST-API.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		// Guard against multiple initialisations.
		if ( did_action( 'rest_api_init' ) ) {
			return;
		}

		add_filter( 'rest_authentication_errors', [ $this, 'restrict_rest_api' ], 99 );
		add_filter( 'rest_endpoints', [ $this, 'restrict_user_endpoints' ] );
		add_action( 'admin_init', [ $this, 'restrict_rest_api_setting' ] );
	}

	/**
	 * Block unauthenticated access to the REST-API when required.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_Error|null|bool $result Result from another auth handler.
	 *
	 * @return \WP_Error|null|bool Filtered result.
	 */
	public function restrict_rest_api( $result ) {
		if ( null !== $result ) {
			return $result;
		}

		$restrict = get_option( 'matchbox_restrict_rest_api', $this->option_default );

		if ( 'all' === $restrict && ! $this->user_can_access_rest_api() ) {
			return new \WP_Error(
				'rest_api_restricted',
				esc_html__( 'Authentication Required', 'matchbox-support' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return $result;
	}

	/**
	 * Remove user endpoints for visitors who cannot access them.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $endpoints List of registered endpoints.
	 *
	 * @return array Filtered list of endpoints.
	 */
	public function restrict_user_endpoints( $endpoints ) {
		$restrict = get_option( 'matchbox_restrict_rest_api', $this->option_default );

		if ( 'none' === $restrict ) {
			return $endpoints;
		}

		if ( ! $this->user_can_access_rest_api() ) {
			$keys = preg_grep( '/\/wp\/v2\/users\b/', array_keys( $endpoints ) );
			foreach ( $keys as $key ) {
				unset( $endpoints[ $key ] );
			}
		}

		return $endpoints;
	}

	/**
	 * Register the “restrict REST-API” option and settings field.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function restrict_rest_api_setting() {
		// Don’t show UI if the callbacks have been removed.
		if (
			! has_filter( 'rest_authentication_errors', [ $this, 'restrict_rest_api' ] ) ||
			! has_filter( 'rest_endpoints', [ $this, 'restrict_user_endpoints' ] )
		) {
			return;
		}

		$settings_args = [
			'type'              => 'string',
			'sanitize_callback' => [ $this, 'validate_restrict_rest_api_setting' ],
		];

		register_setting( 'reading', 'matchbox_restrict_rest_api', $settings_args );

		add_settings_field(
			'matchbox_restrict_rest_api',
			esc_html__( 'REST API Availability', 'matchbox-support' ),
			[ $this, 'restrict_rest_api_ui' ],
			'reading'
		);
	}

	/**
	 * Output the settings-field UI for the restriction option.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function restrict_rest_api_ui() {
		$restrict = get_option( 'matchbox_restrict_rest_api', $this->option_default );
		?>
		<fieldset>
			<legend class="screen-reader-text"><?php esc_html_e( 'REST API Availability', 'matchbox-support' ); ?></legend>

			<p><label for="restrict-rest-api-all">
				<input id="restrict-rest-api-all" name="matchbox_restrict_rest_api" type="radio" value="all"<?php checked( $restrict, 'all' ); ?> />
				<?php esc_html_e( 'Restrict all access to authenticated users', 'matchbox-support' ); ?>
			</label></p>

			<p><label for="restrict-rest-api-users">
				<input id="restrict-rest-api-users" name="matchbox_restrict_rest_api" type="radio" value="users"<?php checked( $restrict, 'users' ); ?> />
				<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: developer reference URL. */
							__( 'Restrict access to the <code><a href="%s">users</a></code> endpoint to authenticated users', 'matchbox-support' ),
							esc_url( 'https://developer.wordpress.org/rest-api/reference/users/' )
						)
					);
				?>
			</label></p>

			<p><label for="restrict-rest-api-none">
				<input id="restrict-rest-api-none" name="matchbox_restrict_rest_api" type="radio" value="none"<?php checked( $restrict, 'none' ); ?> />
				<?php esc_html_e( 'Publicly accessible', 'matchbox-support' ); ?>
			</label></p>
		</fieldset>
		<?php
	}

	/**
	 * Determine whether a user may access the REST-API.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $user_id User ID being checked (0 = current user).
	 *
	 * @return bool True if access is granted.
	 */
	public function user_can_access_rest_api( int $user_id = 0 ): bool {
		/**
		 * Filter whether a user can access the REST-API when restrictions apply.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $can_access Result of the default check (`is_user_logged_in()`).
		 * @param int  $user_id    ID of the user being checked.
		 */
		return (bool) apply_filters(
			'matchbox_user_can_access_rest_api',
			is_user_logged_in(),
			$user_id
		);
	}

	/**
	 * Sanitise the `matchbox_restrict_rest_api` setting.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $value Submitted value.
	 *
	 * @return string Sanitised value.
	 */
	public function validate_restrict_rest_api_setting( $value ): string {
		return in_array( $value, [ 'all', 'users', 'none' ], true ) ? $value : 'users';
	}
}
