=== A little more secure ===
Contributors: palasthotel, edwardbock, janaeggebrecht
Donate link: https://palasthotel.de/
Tags: security
Requires at least: 5.0
Requires PHP: 8.2
Tested up to: 7.1.2
Stable tag: 1.1.1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Stop bots from brute force hacking your wp-login.php

== Description ==

Stop bots from brute force hacking your wp-login.php

A request to wp-login.php without an unlock parameter answers with a 404 and a
holding page. JavaScript counts down a few seconds, then redirects to the same
URL with the parameter appended. Only that request renders a usable login form,
and only it carries the nonce that a login POST has to contain — a POST without
a valid nonce is rejected.

This raises the cost of naive automation. It is not a lockout mechanism: whoever
requests the unlock URL first can read the nonce and post with it, which is why
the plugin is called *a little* more secure.

There is nothing to configure. Activating the plugin is enough.

== Customising ==

Three filters, for a theme or a small plugin of your own.

`a_little_more_secure_is_unlocked` decides whether a request counts as unlocked.
By default that is the presence of the parameter; override it to implement your
own rule, for example a one-time token or an office IP allowlist:

`add_filter( 'a_little_more_secure_is_unlocked', function ( $is_unlocked ) {
	return $is_unlocked || my_own_check();
} );`

`a_little_more_secure_get_param_name` changes the name of the unlock parameter,
`a-little-more-secure` by default.

`a_little_more_secure_redirect_wait_seconds` changes the delay before the
redirect, 3 seconds by default.

A theme with its own login form that posts to wp-login.php has to render the
nonce itself, otherwise the POST is rejected — call
`a_little_more_secure_nonce_field()` inside the form. Forms built with
`wp_login_form()` get it automatically.

= Rotating the unlock parameter =

The default parameter name is public knowledge, so a bot written for this plugin
can hardcode it. If you want a name that changes over time, put the token in the
name itself: the parameter name filter runs both when the redirect URL is built
and when the request is checked, so both sides agree without storing anything.

`function my_alms_token( int $bucketsAgo = 0 ): string {
	$ttl    = 15 * MINUTE_IN_SECONDS;
	$bucket = (int) floor( time() / $ttl ) - $bucketsAgo;

	return 'alms_' . substr( hash_hmac( 'sha256', 'alms|' . $bucket, wp_salt( 'nonce' ) ), 0, 20 );
}

add_filter( 'a_little_more_secure_get_param_name', function () {
	return my_alms_token();
} );

add_filter( 'a_little_more_secure_is_unlocked', function ( $is_unlocked ) {
	return $is_unlocked || isset( $_GET[ my_alms_token( 1 ) ] );
} );`

Accepting the previous bucket as well keeps a request that crosses a bucket
boundary from being rejected, so the effective validity is 15 to 30 minutes.
Keep the token alphanumeric — PHP rewrites dots and spaces in parameter names.

Be aware of what this does and does not do. It stops bots that hardcode the
parameter name. It does not stop anything that fetches the page and reads the
name out of it — the value has to be handed to the browser before anyone is
logged in, so a scraper can always obtain it too.

Three things to expect: bookmarked unlock URLs stop working once the token
expires, though an expired token lands on the holding page and is redirected
with a fresh one, so it costs one extra request. Keep the lifetime well above
the redirect delay, otherwise the token can expire during the countdown. And be
careful with page caching — a short-lived token in cached HTML means logins that
are rejected until the cache is refreshed.

== Installation ==

1. Upload `a-little-more-secure.zip` to the `/wp-content/plugins/` directory
1. Extract the Plugin to a `a-little-more-secure` Folder
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Do I have to configure anything? =

No. Activate the plugin and it works. Everything that can be changed is changed
with the filters described above, in a theme or a small plugin of your own.

= Why does my login page return a 404? =

That is intentional. A request to wp-login.php without the unlock parameter is
answered with a 404 so that automated scanners see a missing page. Your browser
still shows the holding page and is redirected to the real form a few seconds
later. Uptime monitors and scanners pointed at wp-login.php will report it as
missing — point them at a different URL, or exclude wp-login.php.

= I cannot log in. It says "Sorry, this feels not very secure". =

That message means the login form was submitted without a valid nonce. The usual
causes:

1. Something is caching wp-login.php. It must not be cached — the page carries a
nonce that goes stale.
2. Your theme renders its own login form that posts to wp-login.php without the
nonce field. Call `a_little_more_secure_nonce_field()` inside the form.
3. The login page sat open in a tab for more than a day. Nonces expire after at
most 24 hours. Reload the page and log in again.

= Does it work without JavaScript? =

No. The redirect to the unlocked form is done in JavaScript, and only the
unlocked form carries the nonce a login needs. Without JavaScript you will see
the login page but the submission is rejected. If that is a problem for you, add
your own rule with the `a_little_more_secure_is_unlocked` filter.

= Does it protect XML-RPC and the REST API? =

No. The plugin only guards wp-login.php. Brute force attempts against
xmlrpc.php, the REST API or application passwords are unaffected, and those are
common targets. If you do not use XML-RPC, disable it separately.

= Can I keep using a bookmarked login URL? =

Yes. The unlock parameter is a fixed name by default, so a bookmark such as
example.com/wp-login.php?a-little-more-secure keeps working and skips the wait.
That changes only if you set up a rotating parameter as described above.

= Does it work on multisite? =

Yes, network activated or per site. The plugin stores nothing and has no
per-site setup, so both work the same way.

= Does the plugin store any data about my visitors? =

No. It writes no options, sets no cookies and creates no database tables. The
only thing it looks at is whether the unlock parameter is present in the request.

= Is my login secure now? =

More secure than before, but this is a speed bump, not a lock. The unlock
parameter and the nonce both have to be handed to a browser that is not logged
in yet, so anything that fetches the page can read them too. It defeats bots
that post blindly at wp-login.php, which is most of them. It does not defeat a
determined attacker, and it does nothing about weak passwords or repeated
attempts from the same source — combine it with strong passwords and rate
limiting.

== Screenshots ==

1. Wait a few seconds for the login to be secured

2. Your login is a little bit more secure

== Changelog ==

= 1.1.1 =
**Bug Fixes**
* change plugin author and tested wp version (cc62934)
* change plugin author and tested wp version (f2948df)
* resolve symlinks when copying the payload into SVN (0b7f0a2)
* resolve symlinks when copying the payload into SVN (51a9b4d)

= 1.1.0 =
**Features**
* let sites decide for themselves when the login is unlocked (5d16264)

**Bug Fixes**
* block direct access to the DEV wrapper too (45550fe)
* declare GPL-3.0 and the PHP version the plugin actually needs (141c4a2)
* harden the login gate and make the translations extractable (33f28cf)
* restore the public API function dropped in the rewrite (188482d)
* set the 404 before the login page starts printing (73b7856)
* stop the Playground blueprint from pinning an old version (1dd10cc)

= 1.0.4 =
* Check compatiblility with 6.5.2

= 1.0.3 =
* Optimization: load textdomain properly
* Optimization: 404 if no valid login parameter

= 1.0.2 =
* Bugfix: Not redirected properly if hash in url

= 1.0.1 =
* WP5.7 compatible

= 1.0.0 =
 * First release


== Upgrade Notice ==
