# A little more secure

WordPress plugin that protects `wp-login.php` from brute force attacks.

A request to `/wp-login.php` without the unlock parameter answers with a 404
status and a holding page. JavaScript removes the username and password fields,
counts down a few seconds and then redirects to the same URL with the parameter
appended. Only that second request renders a usable form, and only it contains
the nonce that every login POST has to carry — a POST without a valid nonce is
rejected with a 400.

A client that does not run JavaScript still sees the form fields, since removing
them is the script's job, but it never obtains a nonce, so its POST is rejected
too.

This raises the cost of naive automation. It is not a lockout mechanism: anyone
who requests the unlock URL first can read the nonce and post with it, which is
why the plugin is called *a little* more secure. Combine it with rate limiting if
you need more.

- **WordPress.org:** https://wordpress.org/plugins/a-little-more-secure/
- **User documentation:** [public/README.txt](public/README.txt) (the text shown on WordPress.org)
- **Changelog:** [CHANGELOG.md](CHANGELOG.md) — release-please owns that file, so do
  not add notes to it by hand. Entries up to 1.0.4 are in the `== Changelog ==`
  section of [public/README.txt](public/README.txt).

## Requirements

WordPress 5.0 or newer and **PHP 8.2 or newer**. The PHP floor is enforced by
`public/composer.json` and the generated platform check — on an older PHP the
plugin does not degrade, it fails.

## Installation

Install *A little more secure* from the WordPress plugin directory, or download
`a-little-more-secure.zip` from the [latest release](https://github.com/palasthotel/wp-a-little-more-secure/releases/latest)
and extract it into `wp-content/plugins/`.

There is nothing to configure. Activating the plugin is enough.

## Customising

### The unlock parameter

The name of the GET parameter that unlocks the login form:

```php
add_filter( 'a_little_more_secure_get_param_name', function ( $name ) {
	return 'new_param_name';
} );
```

### The redirect delay

How many seconds the browser waits before redirecting, `3` by default:

```php
add_filter( 'a_little_more_secure_redirect_wait_seconds', function ( $seconds ) {
	return 10;
} );
```

### Deciding for yourself whether a request is unlocked

By default a request counts as unlocked when the parameter is present. Override
that to plug in your own rule, for example an IP allowlist:

```php
add_filter( 'a_little_more_secure_is_unlocked', function ( $is_unlocked ) use ( $my_office_ips ) {
	return $is_unlocked || in_array( $_SERVER['REMOTE_ADDR'], $my_office_ips, true );
} );
```

### Rendering the nonce in your own login form

A theme with a custom login form that posts to `wp-login.php` needs the nonce
field, otherwise the POST is rejected:

```php
a_little_more_secure_nonce_field();
```

Forms rendered through `wp_login_form()` get the field automatically via the
`login_form_bottom` filter.

### Rotating the unlock parameter

The default parameter name is in the plugin source on wordpress.org, so a bot
written for this plugin can hardcode it. To make the name change over time, put
the token in the name itself — `a_little_more_secure_get_param_name` runs both
when the redirect URL is built and when the request is checked, so both sides
agree without storing anything:

```php
function my_alms_token( int $bucketsAgo = 0 ): string {
	$ttl    = 15 * MINUTE_IN_SECONDS;
	$bucket = (int) floor( time() / $ttl ) - $bucketsAgo;

	return 'alms_' . substr( hash_hmac( 'sha256', 'alms|' . $bucket, wp_salt( 'nonce' ) ), 0, 20 );
}

add_filter( 'a_little_more_secure_get_param_name', function () {
	return my_alms_token();
} );

// Accept the previous bucket too, so a request crossing a bucket boundary is not
// rejected. Effective validity: 15 to 30 minutes.
add_filter( 'a_little_more_secure_is_unlocked', function ( $is_unlocked ) {
	return $is_unlocked || isset( $_GET[ my_alms_token( 1 ) ] );
} );
```

Keep the token alphanumeric — PHP rewrites dots and spaces in `$_GET` keys.

**What this is worth.** It stops bots that hardcode the parameter name. It does
not stop anything that fetches the page and reads the name out of it: the value
has to reach the browser before anyone is logged in, so a scraper can obtain it
just as easily. This is obfuscation, not access control.

**What it costs.** Bookmarked unlock URLs stop working once the token expires —
an expired token lands on the holding page and gets redirected with a fresh one,
so the cost is one extra request, but it is a behaviour change. Keep the lifetime
well above `a_little_more_secure_redirect_wait_seconds`, or the token can expire
during the countdown. And watch page caching: `login_form_bottom` is part of
`wp_login_form()` output and can sit on a cached page, where a short-lived token
means logins rejected until the cache refreshes.

For the same reason the plugin does not ship this behaviour itself — see
[issue #3](https://github.com/palasthotel/wp-a-little-more-secure/issues/3).

## Repository layout

`public/` is exactly what ships to WordPress.org. Everything outside it is
repository-only.

| Path | Description |
|---|---|
| `public/a-little-more-secure.php` | plugin header and the `Plugin` class |
| `public/classes/Gate.php` | the login gate — the whole protection lives here |
| `public/classes/Environment.php` | reads the filter-configurable settings |
| `public/classes/Components/` | shared plugin scaffolding (`Database` and `Update` are unused here) |
| `public/public-functions.php` | the public API for themes |
| `public/languages/` | translations (`de_DE`, `de_CH`, `de_CH_informal`, `ch_CH` + `.pot`) |
| `public/README.txt` | WordPress.org plugin page |
| `public/LICENSE` | GPL-3.0 text, shipped with the plugin |
| `assets/` | media for the WordPress.org plugin page — not part of the download |
| `a-little-more-secure.php` | DEV wrapper, loads `public/` when the repository is checked out into `wp-content/plugins/` |
| `LICENSE` | copy of the licence text so GitHub detects it |
| `version.txt` | the version, maintained by release-please |
| `.github/workflows/` | CI/CD, calling the shared workflows — see [.github/WORKFLOWS.md](.github/WORKFLOWS.md) |

### `assets/`

The release mirrors this directory into the `assets/` directory of the
WordPress.org SVN repository, which sits next to `trunk/` and is served on the
plugin page only — nothing in here is downloaded by users. WordPress.org
recognises the files by name: `screenshot-1.png`, `banner-772x250.png`,
`icon-128x128.png` and so on. `blueprints/blueprint.json` drives the *Live
Preview* button. The repository is the source of truth: files removed here are
removed from SVN on the next release.

There is no banner yet, so the plugin page shows the default header.

## Releasing

Releases are automated with [release-please](https://github.com/googleapis/release-please)
and deployed to the WordPress.org SVN repository. There is nothing to bump by
hand — commit with [conventional commits](https://www.conventionalcommits.org/)
and merge the release PR:

```
fix: …   → patch    feat: …  → minor    feat!: … → major
```

```
merge PR to main → release-please opens "chore(main): release x.y.z"
                 → merge it → tag vx.y.z → deploy to WordPress.org
```

The workflows call the shared ones in
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows); what is
specific to this plugin is in [.github/WORKFLOWS.md](.github/WORKFLOWS.md). See [CONTRIBUTING.md](CONTRIBUTING.md)
for the commit conventions and the local setup.

## Building locally

```sh
npm run pack    # → build/a-little-more-secure/ and a-little-more-secure.zip
```

This runs the shared `pack.sh` from
[palasthotel/github-workflows](https://github.com/palasthotel/github-workflows), which has
to be checked out next to this repository. It needs `composer` on PHP 8.2 or newer, because
it regenerates the autoloader in the payload.

## License

GNU General Public License v3.0 or later — see [LICENSE](LICENSE).
