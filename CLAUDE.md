# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### JavaScript / Blocks
```bash
npm start          # development watch mode (WordPress Scripts)
npm run build      # production build
npm run lint:js    # lint JavaScript
npm run lint:css   # lint CSS
npm run format     # auto-format code
```

### PHP
```bash
composer install                # install dependencies (includes dev tools)
composer install --no-dev       # production install (used by CI release)
vendor/bin/phpcs                # run PHP CodeSniffer (config in .phpcs.xml)
vendor/bin/phpcbf               # auto-fix PHPCS violations
```

There are no PHP unit tests — PHPCS is the primary PHP quality gate.

## Architecture

### Plugin Entry Points
- `matchbox-support.php` — plugin header, defines constants (`MATCHBOX_SUPPORT_*`), bootstraps `Plugin::instance()`
- `src/functions/utils.php` — global helpers: `dd()` (debug dump), `format_phone_for_tel_link()`

### PHP Namespace & Autoloading
Namespace: `MatchboxSupport\`  
PSR-4 root: `src/MatchboxSupport/` (via Composer)

### Core Classes

| Class | File | Responsibility |
|---|---|---|
| `Plugin` | `src/MatchboxSupport/Plugin.php` | Bootstrap; settings pages; HelpScout beacon; block registration; auto-update via `plugin-update-checker` |
| `ImageForwarding` | `src/MatchboxSupport/ImageForwarding.php` | Rewrites attachment/media URLs to a remote domain (production) — useful on staging/local |
| `API` | `src/MatchboxSupport/API.php` | REST API access restriction (all / authenticated-only / disabled) |
| `Userback` | `src/MatchboxSupport/Userback.php` | Userback feedback widget; token validation |
| `LoginSecurity` | `src/MatchboxSupport/LoginSecurity.php` | Blocks generic usernames; checks passwords against HaveIBeenPwned API; hides weak-password UI |
| `SecurityHeaders` | `src/MatchboxSupport/SecurityHeaders.php` | Emits configurable HTTP security headers (HSTS, CSP, X-Frame-Options, etc.) |

### Patterns
- **Singleton** — all major classes expose a static `instance()` method; they are instantiated once inside `Plugin`.
- **WordPress hooks** — all behaviour is wired via `add_action()` / `add_filter()` inside each class's constructor or `init()`.
- **Options** — settings are stored in the WordPress options table with `matchbox_` prefix.
- **Gutenberg blocks** — `src/blocks/` contains block source; `npm run build` compiles to `build/blocks/`. `Plugin` scans `build/blocks/` at runtime and calls `register_block_type()` for each found directory.

### Build Output
`npm run build` writes to `build/` (blocks) and is committed. The `.distignore` file controls what is excluded from the release ZIP.

### Release Process
GitHub Actions (`.github/workflows/release.yml`) triggers on `v*` tags: runs `composer install --no-dev`, `npm ci && npm run build`, packages via rsync, and creates a GitHub Release with a ZIP artifact.

## WordPress Settings
All plugin settings live under **Settings → Matchbox Support** in wp-admin. The plugin stores data via `get_option` / `update_option` with keys like `matchbox_helpscout_beacon_id`, `matchbox_image_forwarding_domain`, etc.
