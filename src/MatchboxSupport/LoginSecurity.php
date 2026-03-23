<?php
/**
 * Handles login security by blocking generic credentials.
 *
 * @since 2.1.0
 *
 * @package MatchboxSupport
 */

namespace MatchboxSupport;

defined( 'ABSPATH' ) || exit;

/**
 * Login security class — blocks authentication with common/generic credentials.
 *
 * @since 2.1.0
 */
class LoginSecurity {

	/**
	 * Default list of blocked generic usernames (lowercase).
	 *
	 * @since 2.1.0
	 *
	 * @var array<string>
	 */
	public const BLOCKED_USERNAMES = array(
		'admin',
		'administrator',
		'demo',
		'guest',
		'info',
		'manager',
		'mysql',
		'oracle',
		'root',
		'support',
		'sysadmin',
		'test',
		'tester',
		'testuser',
		'user',
		'webmaster',
	);

	/**
	 * Default list of MD5 hashes of blocked common passwords.
	 *
	 * MD5 is used here solely for obfuscation — not for cryptographic
	 * security. The submitted password arrives in plaintext via the
	 * `authenticate` filter, so we MD5 it and compare against this list
	 * to avoid storing plaintext passwords in source code.
	 *
	 * @since 2.1.0
	 *
	 * @var array<string>
	 */
	private const BLOCKED_PASSWORD_HASHES = array(
		'5f4dcc3b5aa765d61d8327deb882cf99',
		'e10adc3949ba59abbe56e057f20f883e',
		'd8578edf8458ce06fbc5bb76a58c5ca4',
		'25d55ad283aa400af464c76d713c07ad',
		'827ccb0eea8a706c4c34a16891f84e7b',
		'e99a18c428cb38d5f260853678922e03',
		'25f9e794323b453885f5181f1b624d0b',
		'd0763edaa9d9bd2a9516280e9044d885',
		'fcea920f7412b5da7be0cf42b8c93759',
		'0d107d09f5bbe40cade3de5c71e9e9b7',
		'5fcfd41e547a12215b173ff47fdd3739',
		'7c6a180b36896a65c4c02c3514ae4240',
		'6c569aabbf7775ef8fc570e228c16b98',
		'3c3662bcb661d6de679c636744c66b62',
		'f379eaf3c831b04de153469d1bec345e',
		'96e79218965eb72c92a549dd5a330112',
		'ee11cbb19052e40b07aac5ae8c4e15dc',
	);

	/**
	 * Initialise login-security hooks.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'authenticate', array( $this, 'block_generic_credentials' ), 30, 3 );
		add_action( 'admin_head', array( $this, 'hide_weak_password_checkbox' ) );
		add_action( 'login_head', array( $this, 'hide_weak_password_checkbox' ) );
	}

	/**
	 * Hide the "Confirm use of weak password" checkbox on user profile
	 * and password reset screens, preventing users from opting into
	 * weak passwords.
	 *
	 * @since 2.1.0
	 *
	 * @return void
	 */
	public function hide_weak_password_checkbox(): void {
		if ( ! get_option( 'matchbox_hide_weak_password_checkbox', false ) ) {
			return;
		}

		echo '<style>.pw-weak { display: none !important; }</style>' . "\n";
	}

	/**
	 * Block authentication when generic credentials are used.
	 *
	 * Hooked to `authenticate` at priority 30 (after core authentication at 20).
	 *
	 * @since 2.1.0
	 *
	 * @param \WP_User|\WP_Error|null $user     Authenticated user, error, or null.
	 * @param string                  $username  Submitted username.
	 * @param string                  $password  Submitted password (plaintext).
	 *
	 * @return \WP_User|\WP_Error Authenticated user or error blocking login.
	 */
	public function block_generic_credentials( $user, string $username, string $password ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( $this->is_blocked_username( $username ) ) {
			return new \WP_Error(
				'matchbox_blocked_username',
				__( '<strong>Error:</strong> This username is not permitted. Please contact your site administrator to set up a unique account.', 'matchbox-support' )
			);
		}

		if ( $this->is_blocked_password( $password ) ) {
			return new \WP_Error(
				'matchbox_blocked_password',
				__( '<strong>Error:</strong> This password is too common and not permitted. Please reset your password to something more secure.', 'matchbox-support' )
			);
		}

		/**
		 * Filter whether the HIBP Pwned Passwords check is enabled.
		 *
		 * @since 2.1.0
		 *
		 * @param bool $enabled Whether the check is enabled.
		 */
		$hibp_enabled = apply_filters(
			'matchbox_hibp_check_enabled',
			(bool) get_option( 'matchbox_enable_hibp_check', false )
		);

		if ( $hibp_enabled && $this->is_pwned_password( $password ) ) {
			return new \WP_Error(
				'matchbox_pwned_password',
				__( '<strong>Error:</strong> This password has appeared in a known data breach and is not permitted. Please reset your password to something more secure.', 'matchbox-support' )
			);
		}

		return $user;
	}

	/**
	 * Check whether a username is in the blocked list.
	 *
	 * @since 2.1.0
	 *
	 * @param string $username Username to check.
	 *
	 * @return bool True if the username is blocked.
	 */
	private function get_blocked_usernames(): array {
		$saved = get_option( 'matchbox_blocked_usernames', '' );

		if ( ! empty( $saved ) ) {
			return array_values( array_filter(
				array_map( 'trim', explode( "\n", strtolower( $saved ) ) )
			) );
		}

		return self::BLOCKED_USERNAMES;
	}

	private function is_blocked_username( string $username ): bool {
		/**
		 * Filter the list of blocked generic usernames.
		 *
		 * @since 2.1.0
		 *
		 * @param array<string> $blocked_usernames List of blocked usernames (lowercase).
		 */
		$blocked_usernames = apply_filters( 'matchbox_blocked_usernames', $this->get_blocked_usernames() );

		return in_array( strtolower( trim( $username ) ), $blocked_usernames, true );
	}

	/**
	 * Check whether a password matches a known common password hash.
	 *
	 * @since 2.1.0
	 *
	 * @param string $password Plaintext password to check.
	 *
	 * @return bool True if the password is blocked.
	 */
	private function is_blocked_password( string $password ): bool {
		/**
		 * Filter the list of MD5 hashes of blocked common passwords.
		 *
		 * To add a password to the blocked list, append its MD5 hash:
		 * `md5( 'the-password-string' )`.
		 *
		 * @since 2.1.0
		 *
		 * @param array<string> $blocked_password_hashes List of MD5 hex-digest hashes.
		 */
		$blocked_password_hashes = apply_filters( 'matchbox_blocked_password_hashes', self::BLOCKED_PASSWORD_HASHES );

		return in_array( md5( $password ), $blocked_password_hashes, true );
	}

	/**
	 * Check whether a password has been exposed in a data breach via the
	 * Have I Been Pwned Pwned Passwords API (k-anonymity model).
	 *
	 * The full password or its full hash is never sent over the network.
	 * Only the first 5 characters of the SHA-1 hash are transmitted.
	 *
	 * @since 2.1.0
	 *
	 * @param string $password Plaintext password to check.
	 *
	 * @return bool True if the password appears in a known breach.
	 */
	private function is_pwned_password( string $password ): bool {
		$sha1   = strtoupper( sha1( $password ) );
		$prefix = substr( $sha1, 0, 5 );
		$suffix = substr( $sha1, 5 );

		$response = wp_remote_get(
			'https://api.pwnedpasswords.com/range/' . $prefix,
			array(
				'timeout' => 10,
				'headers' => array(
					'Add-Padding' => 'true',
					'User-Agent'  => 'MatchboxSupport/' . MATCHBOX_SUPPORT_VERSION,
				),
			)
		);

		// Fail open: if the API is unreachable, do not block login.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body  = wp_remote_retrieve_body( $response );
		$lines = explode( "\r\n", $body );

		foreach ( $lines as $line ) {
			if ( empty( $line ) ) {
				continue;
			}

			$parts = explode( ':', $line );

			if ( count( $parts ) < 2 ) {
				continue;
			}

			$hash_suffix = $parts[0];
			$count       = (int) $parts[1];

			// Skip padded entries.
			if ( 0 === $count ) {
				continue;
			}

			if ( strtoupper( $hash_suffix ) === $suffix ) {
				return true;
			}
		}

		return false;
	}
}
