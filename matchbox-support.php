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
 * Register autoloader
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

// Instantiate the main class.
Matchbox_Support_Main::instance();

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
function matchbox_support_initialize_update_checker() {
	// Check if the Plugin Update Checker class exists to prevent potential conflicts.
    if ( !class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory') ) {
        require_once plugin_dir_path(__FILE__) . 'includes/plugin-update-checker/plugin-update-checker.php';
	}

	// Initialize the update checker for the GitHub-hosted plugin.
	$updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/matchboxdesigngroup/matchbox-support',
		__FILE__,
		'matchbox-support'
	);

	// Configure the update checker to look for GitHub release assets.
	$updateChecker->getVcsApi()->enableReleaseAssets();
}
add_action( 'plugins_loaded', 'matchbox_support_initialize_update_checker' );
