# Matchbox Support Plugin Audit — July 2026

Scope: full source review of the plugin at v2.0.5 (`src/`, `assets/`, `matchbox-support.php`, `.github/workflows/release.yml`, build/tooling config). No PHP unit tests exist; findings are from static review.

Severity legend: 🔴 High · 🟠 Medium · 🟡 Low / hygiene

---

## 1. Security

### 🔴 1.1 Userback access token echoed into inline JS without escaping
`src/MatchboxSupport/Userback.php:84-95` — `print_script()` interpolates the raw option value into a `<script>` block:

```php
$access_token = get_option( 'matchbox_userback_token', 'default_token_if_not_set' );
echo "<script ...> Userback.access_token = '{$access_token}'; ...";
```

A value containing `'` or `</script>` breaks out of the string/script context, giving stored XSS that renders in wp-admin for every admin/editor, and on the public front end when "Show Widget to Logged-Out Visitors" is enabled. The save-time validator does not prevent this: the option can be written via WP-CLI, import plugins, direct `update_option()`, or the broken validation path in 1.2.

**Fix:** output via `wp_json_encode( $access_token )` (or `esc_js()`), and prefer `wp_add_inline_script()` over a hand-echoed `<script>` tag (also makes it CSP-manageable).

### 🔴 1.2 Token/beacon validation logic is broken — invalid values are accepted as valid
`src/MatchboxSupport/Plugin.php:1012-1027` (`validate_userback_token`):

```php
$json = json_decode( $body, true );
if ( false === $json ) { ... }
```

`json_decode()` returns **`null`** on failure, never `false`, so this branch is dead. Any HTTP response — including an error body — marks the token valid (`matchbox_userback_token_valid = true`). The response code and body content are never actually checked.

`validate_helpscout_beacon_id` (`Plugin.php:1064`) has the mirror problem plus an undefined-index warning: `'Not Found' === $json['message']` is evaluated without checking `$json` is an array containing `message` — a non-JSON 200 body throws a PHP warning (fatal-adjacent on PHP 8 `TypeError` patterns) and skips real validation.

**Fix:** check `wp_remote_retrieve_response_code() === 200`, decode with `json_decode`, verify shape with `isset()`, and validate a positive success signal from the API rather than the absence of one error string.

### 🟠 1.3 Settings can never be cleared
Both validators return `$old_value` when the submitted value is empty (`Plugin.php:983-986`, `1043-1046`). Emptying the Userback token or Beacon ID field and saving silently restores the previous value — you cannot deactivate these integrations from the UI. Return `''` (and delete the `_valid` flag) instead.

### 🟠 1.4 Login lockout risk from username blocklist
`LoginSecurity::block_generic_credentials()` runs at `authenticate` priority 30 and hard-blocks any username on the list — including a **legitimate existing account** named e.g. `admin`. Many legacy sites have exactly one admin user named `admin`; activating this plugin (or enabling the feature) locks them out with no bypass. Consider blocking only when the username does **not** correspond to an existing user (kills enumeration/bot noise without lockouts), or checking `username_exists()` and warn admins in the settings UI.

### 🟠 1.5 HIBP check runs on the live login path on every successful login
`LoginSecurity::is_pwned_password()` makes a synchronous HTTPS request (10 s timeout) during authentication. Every successful login pays that latency, and an API slowdown delays all logins site-wide (it fails open, which is correct, but only after the timeout). Consider caching the negative result per user (e.g. transient keyed on user + password hash prefix) or moving the check to `wp_authenticate_user`/password-change events instead of every login.

### 🟡 1.6 Missing `ABSPATH` guards
`src/MatchboxSupport/API.php` and `src/functions/utils.php` lack the `defined( 'ABSPATH' ) || exit;` guard present in every other file. Low practical risk (namespaced class / guarded function definitions), but inconsistent with the plugin's own convention.

### 🟡 1.7 `dd()` shipped in production code
`src/functions/utils.php:13` defines a dump-and-die debug helper loaded on every request via `plugins_loaded`. If any theme/plugin code path ever calls it (or it is left in shipped code), it dumps raw data and kills the response with no capability check. Guard it with `WP_DEBUG` or move it to a dev-only include.

### 🟡 1.8 `register_setting()` without sanitize callbacks
`matchbox_userback_token`, `matchbox_helpscout_beacon_id`, `matchbox_userback_public`, all `matchbox_header_*` options, `matchbox_enable_hibp_check`, and `matchbox_hide_weak_password_checkbox` are registered with no `sanitize_callback`. Header values are sanitized at send time (`SecurityHeaders.php:123` — good, prevents CRLF injection), but stored values should be sanitized at write time too: checkboxes → `rest_sanitize_boolean`/`'1'`, token/ID → `sanitize_text_field`, `matchbox_header_xfo_value`/`referrer_value` → whitelist against the allowed sets.

### 🟡 1.9 HelpScout validation hits a hardcoded CloudFront hostname
`Plugin.php:1048` requests `https://d3hb14vkzrxvla.cloudfront.net/v1/<id>`. That distribution hostname is an implementation detail of HelpScout's CDN and can change without notice, at which point validation silently breaks (and with 1.2 fixed, valid beacons would start being rejected). Isolate it as a class constant with a comment, and handle its disappearance gracefully.

---

## 2. Performance

### 🟠 2.1 Userback toggle JS/CSS load for every visitor on every page
`Userback::initialize_userback()` (`Userback.php:56-58`) hooks `wp_enqueue_scripts`/`admin_enqueue_scripts` **before** the token/capability checks, so `toggle-userback.js` + CSS are enqueued for all visitors — logged-out users on production included — even when the widget never renders. Move the enqueue inside the `$is_authorized_user` branch (mirroring how `Plugin` gates the HelpScout assets).

### 🟠 2.2 Unnecessary jQuery dependency
Both toggle scripts are pure vanilla JS but are enqueued with `array( 'jquery' )` (`Userback.php:134`, `Plugin.php:211`), forcing jQuery onto the front end for the toggle feature. Drop the dependency.

### 🟠 2.3 Saving settings fires two remote HTTP requests every time
The settings page is one `<form>` with per-section submit buttons, so every save posts **all** fields, and the `pre_update_option_*` validators run remote requests to Userback and HelpScout on each save — even when those fields didn't change (`pre_update_option_{option}` filters run before WP's unchanged-value short-circuit). Compare `$new_value !== $old_value` first and return early.

### 🟡 2.4 Verbose console logging + body-wide MutationObserver in shipped JS
`assets/js/toggle-helpscout.js` and `toggle-userback.js` each contain ~20 `console.log/warn` calls that run in production, and observe `document.body` with `{ childList: true, subtree: true }`, firing the callback on every DOM mutation until the overlay appears. Strip the logging; scope or debounce the observer.

### 🟡 2.5 Update checker constructed on every request
`matchbox_support_initialize_update_checker` runs on `plugins_loaded` for all requests including the front end. PUC throttles its remote checks internally, but constructing it (and enabling release assets) is admin-only concern — wrap in `is_admin() || wp_doing_cron()`.

---

## 3. Best practices & technical debt

### 🟠 3.1 Fatal error when `vendor/autoload.php` is missing
`matchbox-support.php:56` does `require_once __DIR__ . '/vendor/autoload.php';` unconditionally. `vendor/` is git-ignored, so a dev clone (or a bad deploy) activates the plugin and white-screens the site. Guard with `file_exists()` + admin notice.

### 🟠 3.2 Duplicated code
- `add_matchbox_helpscout_beacon_to_admin_footer()` and `..._to_frontend_footer()` (`Plugin.php:132-173`) are byte-identical — collapse to one method hooked twice.
- `toggle-helpscout.js` and `toggle-userback.js` are the same ~150-line file with two IDs swapped — parameterize one script.
- The valid/invalid SVG indicator markup is pasted twice (`Plugin.php:687-689`, `719-721`) — extract a helper.
- The Userback SVG icon is duplicated across `Userback.php`, `index.js`, and `toolbar-toggle.js`.

### 🟠 3.3 Version / metadata drift
- Plugin header `Requires PHP: 8.0` vs `.phpcs.xml` `testVersion 8.1-`.
- `package.json` version `1.0.0` vs plugin `2.0.5`; block.json `1.0.0`.
- Numerous `@since TBD` placeholders and `@since 2.1.0` docblocks in a 2.0.5 plugin.
- `CLAUDE.md` states `build/` is committed; it is not (see 3.6).

### 🟠 3.4 Inconsistent bootstrap / singleton pattern
`Plugin`, `Userback`, `ImageForwarding` are singletons instantiated in the main file; `API`, `LoginSecurity`, `SecurityHeaders` are plain `new` inside `Plugin`'s constructor. `Userback`/`ImageForwarding` bypass `Plugin` entirely. Pick one composition root (`Plugin`) and instantiate everything there.

### 🟡 3.5 i18n gaps and wrong text domain
Most settings-page strings are hardcoded English with no translation functions (`Plugin.php:311-312`, `555`, `566-569`, field labels, checkbox labels, section titles). `Plugin.php:193` uses text domain `'matchbox'` instead of `'matchbox-support'`. Either commit to translating (wrap everything, add `load_plugin_textdomain`) or accept it's internal-only — but the mixed state fails PHPCS's i18n sniffs either way.

### 🟡 3.6 Release pipeline gaps
- `release.yml` has no `actions/setup-node` step — `npm ci` uses whatever Node the runner image ships, so builds aren't reproducible. Pin a Node version (and PHP 8.0 here vs 8.1 in phpcs config — align).
- No CI on push/PR at all: PHPCS, `lint:js`, `lint:css`, and the block build only ever run on a developer machine or at release time. Add a lint workflow.
- `build/blocks` is required at runtime by `register_blocks()` but is neither committed nor gitignored-and-built on activation; in a git-based deploy the block silently never registers.

### 🟡 3.7 Misc code quality
- `LoginSecurity.php:163-183` — the docblock for `is_blocked_username()` sits on `get_blocked_usernames()`, and `is_blocked_username()` has none (PHPCS violation).
- `Plugin.php:267` — assignment inside condition (`if ( $template = get_404_template() )`), flagged by WPCS.
- `Userback.php:85` — misleading default `'default_token_if_not_set'` is dead code (the hook is only added when the token is non-empty) and would be printed into JS if reached.
- `API::init()`'s `did_action( 'rest_api_init' )` guard doesn't do what the comment says ("guard against multiple initialisations") — it just skips hooking on late init. Harmless but misleading.
- `format_phone_for_tel_link()` returns a bare digit string (no `tel:` prefix) in the fallback branch — callers get an invalid href for non-US numbers.
- CHANGELOG exists but latest entries lag features (LoginSecurity/SecurityHeaders are `@since 2.1.0` yet unreleased/undocumented).

---

## 4. Accessibility

### 🟠 4.1 Admin-bar toggles are not keyboard/screen-reader accessible
Both admin-bar pill toggles (`Plugin.php:184-198`, `Userback.php:102-127`) render a clickable `<div>` inside an `<a href="#">`:
- No `role="switch"`/`button`, no `aria-pressed`, state conveyed only by a CSS class — invisible to assistive tech.
- The click handler is on the inner div; keyboard activation (Enter/Space) toggles nothing, and `href="#"` scrolls to top.
- The only label is a `title` attribute, which is unreliable for screen readers and unavailable to touch users.

**Fix:** make the node title a real `<button>` (or put `role="switch"` + `aria-pressed` + `aria-label` on the anchor), toggle `aria-pressed` in JS, and add visible focus styles.

### 🟠 4.2 Token validity indicated by color-only icons
The green-check / red-X SVGs on the settings page (`Plugin.php:687-689`, `719-721`) rely on color and a `title` tooltip. Add visually-hidden text (e.g. `<span class="screen-reader-text">Token is valid</span>`) and `aria-hidden="true"` on the SVGs.

### 🟡 4.3 Settings fields not programmatically labelled
`add_settings_field()` calls omit the `label_for` arg, so the table-row headings ("Userback Access Token", etc.) are plain `<th>` text not associated with the inputs. Pass `array( 'label_for' => 'matchbox_userback_token' )` (matching the input IDs already in place).

### 🟡 4.4 Decorative SVGs unlabelled
Admin-bar and toolbar SVGs lack `aria-hidden="true"`/`focusable="false"`; the block-editor `ToolbarButton` is fine (it has a `label`).

---

## 5. Suggested priority order

1. **1.1 + 1.2 + 1.3** — escape the Userback token output; fix both validators (`json_decode` null check, response-code check, allow clearing values). Small, contained, highest risk reduction.
2. **1.4** — add `username_exists()` awareness to the blocklist to remove the admin-lockout foot-gun before wider rollout.
3. **2.1 + 2.2** — stop shipping toggle assets (and jQuery) to anonymous visitors.
4. **3.1 + 3.6** — autoload guard, pinned Node, and a lint CI workflow.
5. **4.1 + 4.2** — accessible toggles and validity indicators.
6. Remaining hygiene items (i18n, duplication, version drift, console logging) as a cleanup pass.
