<?php
/**
 * Plugin Name:       Matchbox Support
 * Plugin URI:        https://github.com/matchboxdesigngroup/matchbox-support
 * Description:       Add helpers for the Matchbox support team.
 * Author:            Matchbox Design Group, Cullen Whitmore
 * Author URI:        https://matchboxdesigngroup.com/
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Version:           1.0.0
 * License:           General Public License v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       matchbox-support
 *
 * This file represents the entry point for the Matchbox Support plugin where it handles
 * the initial setup like defining constants. It's responsible for initiating the plugin's
 * functionality by setting up necessary hooks and loading required files.
 *
 * @package matchbox-support
 * @since 0.1.0
 */

/**
 * Add Matchbox HelpScout Beacon to the admin footer.
 *
 * Embed JavaScript code to load and initialize the HelpScout Beacon
 * for communication and support purposes in the WordPress admin footer.
 *
 * @since 1.0.0
 */
function add_matchbox_helpscout_beacon_to_admin_footer() {
	// Enqueue the JavaScript file.
	wp_enqueue_script( 'matchbox-helpscout-beacon', plugin_dir_url( __FILE__ ) . 'assets/js/helpscout-beacon.js', array(), '1.0', true );

	// Pass the beacon_id to the JavaScript file.
	wp_localize_script('matchbox-helpscout-beacon', 'matchbox_helpscout_params', array(
		'beacon_id' => defined('HELPSCOUT_BEACON_ID') ? HELPSCOUT_BEACON_ID : ''
	));
}
add_action( 'admin_footer', 'add_matchbox_helpscout_beacon_to_admin_footer' );

/**
 * Add Matchbox HelpScout Beacon to the front-end footer for site administrators.
 *
 * Embed JavaScript code to load and initialize the HelpScout Beacon
 * for communication and support purposes in the WordPress front-end footer.
 * Display only for site administrators.
 *
 * @since 1.0.0
 */
function add_matchbox_helpscout_beacon_to_frontend_footer() {
	// Check if the current user is an administrator.
	if ( current_user_can( 'administrator' ) ) {
		wp_enqueue_script( 'matchbox-helpscout-beacon', plugin_dir_url( __FILE__ ) . 'assets/js/helpscout-beacon.js', array(), '1.0', true );
	}

	// Pass the beacon_id to the JavaScript file.
	wp_localize_script('matchbox-helpscout-beacon', 'matchbox_helpscout_params', array(
		'beacon_id' => defined('HELPSCOUT_BEACON_ID') ? HELPSCOUT_BEACON_ID : ''
	));
}
add_action( 'wp_footer', 'add_matchbox_helpscout_beacon_to_frontend_footer' );
