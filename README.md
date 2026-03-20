# Matchbox Support

This WordPress plugin adds helpers for the Matchbox support team.

## Key features

- Add Matchbox's HelpScout Beacon for site admins.
- Optional **image forwarding**: rewrite attachment and media API URLs to load images from another domain (e.g. production) on local or staging.

### Image forwarding

Use **Settings → Matchbox Support** to set a **Forward base URL** and **When to apply**.

- **Off** — no URL rewriting.
- **All environments** — rewriting runs everywhere (use with care on production databases).
- **Non-production only** — rewriting runs only when `wp_get_environment_type()` is not `production` (i.e. for `local`, `development`, or `staging`).

Set the environment in `wp-config.php`, for example:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' ); // or staging, development, production
```

Optional: define `MATCHBOX_IMAGE_FORWARD_URL` in `wp-config.php` to override the saved base URL (handy for local installs without changing the saved option).

Override forwarding programmatically with the `matchbox_image_forward_allow` filter.

Forwarded URLs are built from WordPress attachment APIs (`wp_get_attachment_url`, image `src`/`srcset`, REST attachment responses, etc.). **Hardcoded media URLs inside saved post HTML are not rewritten.**

If you previously used the separate “MDG Image Forwarding” plugin, deactivate it when using this feature to avoid double replacements.

### HelpScout Beacon

The plugin adds a HelpScout Beacon for site admins to the site's front-end and admin areas.
This allows those users to submit a support ticket from directly within their site.

## Installation

1. Navigate to the plugin's directory and run `composer install`

## Requirements

- A pre-configured [HelpScout Beacon](https://docs.helpscout.com/article/1250-beacon-jumpstart-guide).

## Setup

To enable the beacon, you must define the `HELPSCOUT_BEACON_ID` constant in your wp-config.php file.

1. Get your Beacon ID from HelpScout.
2. Add `define('HELPSCOUT_BEACON_ID', 'YOUR_BEACON_ID_HERE');` to the wp-config.php file.
3. Replace `YOUR_BEACON_ID_HERE` with your Beacon ID.
4. Enable the plugin.
5. Reload the page to confirm your HelpScout Beacon appears on the site.
