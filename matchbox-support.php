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

 // Load script with dynamic access token

function matchbox_support_load_script()
{
    if (current_user_can('administrator') || current_user_can('editor')) {
        add_action( 'admin_footer', 'add_matchbox_helpscout_beacon_to_admin_footer', 100 );
        add_action( 'wp_footer', 'add_matchbox_helpscout_beacon_to_frontend_footer', 100 );
        add_action( 'admin_bar_menu', 'matchbox_support_add_helpscout_toggle', 100 );

        // Enqueue the custom JavaScript and CSS files.
        add_action('wp_enqueue_scripts', 'matchbox_support_enqueue_assets');
        add_action('admin_enqueue_scripts', 'matchbox_support_enqueue_assets');
    }
}
add_action( 'init', 'matchbox_support_load_script' );

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
	wp_enqueue_script( 'matchbox-helpscout-beacon', plugin_dir_url( __FILE__ ) . 'assets/js/helpscout-beacon.js', array(), '1.0', true );


	// Pass the beacon_id to the JavaScript file.
	wp_localize_script('matchbox-helpscout-beacon', 'matchbox_helpscout_params', array(
		'beacon_id' => defined('HELPSCOUT_BEACON_ID') ? HELPSCOUT_BEACON_ID : ''
	));
}

/**
 * Add a toggle button to the WordPress admin bar for hiding or showing the Helpscout overlay
 *
 * @param WP_Admin_Bar $wp_admin_bar
 */
function matchbox_support_add_helpscout_toggle($wp_admin_bar)
{
    // Define the SVG icon used by Helpscout
    $svg_icon = '
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM169.8 165.3c7.9-22.3 29.1-37.3 52.8-37.3l58.3 0c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L280 264.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24l0-13.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1l-58.3 0c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM224 352a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/></svg>
    ';

    // Create the pill toggle markup
    $toggle_markup = '
        <div class="matchbox-support-pill-toggle" id="matchbox-support-pill-toggle">
            <div class="toggle-icon">' . $svg_icon . '</div>
        </div>
    ';

    $wp_admin_bar->add_node([
        'id'    => 'matchbox_helpscout_toggle',
        'title' => $toggle_markup,
        'href'  => '#',
        'meta'  => [
            'onclick' => 'matchboxToggleHelpscout(); return false;',
            'title'   => 'Show or hide the Matchbox Support overlay',
        ],
        'parent' => 'top-secondary', // Moves it to the right side of the admin bar near "Howdy, admin"
    ]);
}

/**
 * Enqueue the assets for the Matchbox Support plugin.
 *
 * Load the necessary JavaScript and CSS files for the plugin to work.
 *
 * @since 1.0.0
 */
function matchbox_support_enqueue_assets()
{
    // Enqueue the JS file
    wp_enqueue_script('matchbox-toggle-helpscout', plugin_dir_url(__FILE__) . 'assets/js/toggle-helpscout.js', ['jquery'], '1.0', true);

    // Enqueue the CSS file
    wp_enqueue_style('matchbox-toggle-helpscout-style', plugin_dir_url(__FILE__) . 'assets/css/toggle-helpscout.css', [], '1.0', 'all');
}