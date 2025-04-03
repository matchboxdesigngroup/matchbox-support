<?php
/**
 * Matchbox Support plugin for WordPress
 *
 * @package           matchbox-support
 * @link              https://github.com/matchboxdesigngroup/matchbox-support
 * @author            Matchbox, Cullen Whitmore
 * @copyright         2024 Matchbox Design Group
 * @license           GPL v2 or later
 *
 * Plugin Name:       Matchbox Support
 * Description:       Add helpers for the Matchbox support team.
 * Version:           1.0.0
 * Plugin URI:        https://github.com/matchboxdesigngroup/matchbox-support
 * Author:            Matchbox Design Group, Cullen Whitmore
 * Author URI:        https://matchboxdesigngroup.com/
 * Text Domain:       matchbox-support
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * License:           General Public License v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or( at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * This file represents the entry point for the Matchbox Support plugin where it handles
 * the initial setup like defining constants. It's responsible for initiating the plugin's
 * functionality by setting up necessary hooks and loading required files.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Matchbox_Support\Matchbox_Support_Main;

/**
 * Define constants
 */
if ( ! defined( 'MATCHBOX_SUPPORT_DIR' ) ) {
	define( 'MATCHBOX_SUPPORT_DIR', __DIR__ );
}
if ( ! defined( 'MATCHBOX_SUPPORT_FILE' ) ) {
	define( 'MATCHBOX_SUPPORT_FILE', __FILE__ );
}

/**
 * Register custom autoloader
 */
spl_autoload_register(
	function ( $class_name ) {
		$prefix   = 'Matchbox_Support\\';
		$base_dir = __DIR__ . '/includes/';

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

require_once __DIR__ . '/vendor/autoload.php';

// Instantiate the main class.
Matchbox_Support_Main::instance();
