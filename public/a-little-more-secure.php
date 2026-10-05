<?php

/**
 * Plugin Name: A little more secure
 * Plugin URI: https://github.com/palasthotel/wp-a-little-more-secure
 * Description: Stop bots from brute force hacking your wp-login.php
 * Version: 1.1.2
 * Author: Palasthotel <webmaster@palasthotel.de>
 * Author URI: https://palasthotel.de
 * Text Domain: a-little-more-secure
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 8.2
 * Tested up to: 7.1
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @copyright Palasthotel
 * @package Palasthotel\ALittleMoreSecure
 */

namespace Palasthotel\ALittleMoreSecure;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

class Plugin extends Components\Plugin {

	const DOMAIN = "a-little-more-secure";

	const NONCE_ACTION = 'a-little-more-secure-login-action';
	const NONCE_NAME = 'a-little-more-secure-login-nonce';

	const FILTER_GET_PARAM_NAME = "a_little_more_secure_get_param_name";
	const FILTER_REDIRECT_WAIT_SECONDS = "a_little_more_secure_redirect_wait_seconds";

	const FILTER_IS_UNLOCKED = "a_little_more_secure_is_unlocked";

	const DEFAULT_GET_PARAM_NAME = "a-little-more-secure";
	const DEFAULT_REDIRECT_WAIT_SECONDS = 3;
	public Environment $environment;
	public Gate $gate;

	public function onCreate() {

		$this->loadTextdomain(
			self::DOMAIN,
			"languages"
		);

		$this->environment = new Environment($this);
		$this->gate = new Gate($this);

	}
}

Plugin::instance();

require_once dirname( __FILE__ ) . "/public-functions.php";
