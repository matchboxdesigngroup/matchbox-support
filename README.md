# Matchbox Support

This WordPress plugin adds helpers for the Matchbox support team.

## Key features

- Add Matchbox's HelpScout Beacon for site admins.

### HelpScout Beacon

The plugin adds a HelpScout Beacon for site admins to the site's front-end and admin areas.
This allows those users to submit a support ticket from directly within their site.

## Requirements

- A pre-configured [HelpScout Beacon](https://docs.helpscout.com/article/1250-beacon-jumpstart-guide).

## Setup

To enable the beacon, you must define the `HELPSCOUT_BEACON_ID` constant in your wp-config.php file.

1. Get your Beacon ID from HelpScout.
1. Add `define('HELPSCOUT_BEACON_ID', 'YOUR_BEACON_ID_HERE');` to the wp-config.php file.
1. Replace `YOUR_BEACON_ID_HERE` with your Beacon ID.
1. Enable the plugin.
1. Reload the page to confirm your HelpScout Beacon appears on the site.
