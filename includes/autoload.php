<?php
/**
 * Register custom autoloader
 *
 * @package Matchbox_Support
 */

spl_autoload_register(
	function ( $class_name ) {
		$prefix   = 'Matchbox_Support\\';
		$base_dir = MATCHBOX_SUPPORT_DIR . '/includes/';

		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = strtolower( str_replace( array( '\\', '_' ), array( '/', '-' ), substr( $class_name, strlen( $prefix ) ) ) );

		$relative_class_parts = explode( '/', $relative_class );
		$relative_class_parts[ array_key_last( $relative_class_parts ) ] = 'class-' . end( $relative_class_parts );
		$relative_class = implode( '/', $relative_class_parts );

		$file = $base_dir . $relative_class . '.php';

		if ( is_file( $file ) ) {
			require $file;
		}
	}
);

require_once MATCHBOX_SUPPORT_DIR . '/vendor/autoload.php';
