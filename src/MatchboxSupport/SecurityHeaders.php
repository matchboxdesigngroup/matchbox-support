<?php
/**
 * Handles configurable HTTP security headers.
 *
 * @since 2.1.0
 *
 * @package MatchboxSupport
 */

namespace MatchboxSupport;

defined( 'ABSPATH' ) || exit;

/**
 * Sends optional security headers on front-end, admin, and login pages.
 *
 * @since 2.1.0
 */
class SecurityHeaders {

	/**
	 * Map of supported security headers to their option names and default values.
	 *
	 * Each entry contains:
	 *   - option  (string)      WordPress option name for the enable/disable toggle.
	 *   - value   (string|null) WordPress option name for the header value, or null
	 *                           when the value is fixed (see `default`).
	 *   - default (string)      Default header value used when `value` is null or the
	 *                           value option has not been saved.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string, array{option: string, value: string|null, default: string}>
	 */
	private const HEADERS = array(
		'Strict-Transport-Security' => array(
			'option'  => 'matchbox_header_hsts',
			'value'   => null,
			'default' => 'max-age=31536000; includeSubDomains',
		),
		'X-Content-Type-Options'    => array(
			'option'  => 'matchbox_header_xcto',
			'value'   => null,
			'default' => 'nosniff',
		),
		'X-Frame-Options'           => array(
			'option'  => 'matchbox_header_xfo',
			'value'   => 'matchbox_header_xfo_value',
			'default' => 'SAMEORIGIN',
		),
		'Referrer-Policy'           => array(
			'option'  => 'matchbox_header_referrer',
			'value'   => 'matchbox_header_referrer_value',
			'default' => 'strict-origin-when-cross-origin',
		),
		'Permissions-Policy'        => array(
			'option'  => 'matchbox_header_permissions',
			'value'   => 'matchbox_header_permissions_value',
			'default' => 'camera=(), microphone=(), geolocation=()',
		),
		'Content-Security-Policy'   => array(
			'option'  => 'matchbox_header_csp',
			'value'   => 'matchbox_header_csp_value',
			'default' => 'upgrade-insecure-requests',
		),
	);

	/**
	 * Initialise security-header hooks.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'send_headers', array( $this, 'send_security_headers' ) );
		add_action( 'admin_init',   array( $this, 'send_security_headers' ) );
		add_action( 'login_init',   array( $this, 'send_security_headers' ) );
	}

	/**
	 * Send all enabled security headers.
	 *
	 * Iterates the header map, checks which headers are toggled on, resolves
	 * the value for each, applies the `matchbox_security_headers` filter, then
	 * calls PHP's header() for each entry.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function send_security_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		$headers = array();

		foreach ( self::HEADERS as $header_name => $config ) {
			if ( ! get_option( $config['option'], false ) ) {
				continue;
			}

			$value = $config['value']
				? get_option( $config['value'], $config['default'] )
				: $config['default'];

			if ( ! empty( $value ) ) {
				$headers[ $header_name ] = $value;
			}
		}

		/**
		 * Filter all enabled security headers before they are sent.
		 *
		 * @since 2.1.0
		 *
		 * @param array<string, string> $headers Associative array of header name => value.
		 */
		$headers = apply_filters( 'matchbox_security_headers', $headers );

		foreach ( $headers as $name => $value ) {
			header( sanitize_text_field( $name ) . ': ' . sanitize_text_field( $value ) );
		}
	}
}
