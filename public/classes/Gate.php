<?php

namespace Palasthotel\ALittleMoreSecure;

defined( 'ABSPATH' ) || exit;

use Palasthotel\ALittleMoreSecure\Components\Component;

class Gate extends Component {

	public function onCreate(): void {
		parent::onCreate();

		add_action( 'login_init', [ $this, 'login_init' ] );
		add_action( 'login_form', [ $this, 'login_form' ] );
		add_action( "login_form_login", [ $this, 'login_action' ] );
		add_filter( 'login_form_bottom', [ $this, 'login_form_bottom' ], 10, 2 );

	}

	/**
	 * Sets the 404 status for a locked login form.
	 *
	 * This has to happen on login_init: wp-login.php calls login_header() before
	 * it fires login_form, so by the time the form is rendered the response may
	 * already be on its way and the status code no longer changeable.
	 *
	 * Only the login form is hidden, so every other login action - lost password,
	 * registration, confirmations - keeps its normal status.
	 */
	public function login_init(): void {
		$action = $_REQUEST['action'] ?? 'login';

		if ( ! is_string( $action ) || ! in_array( $action, [ '', 'login' ], true ) ) {
			return;
		}

		if ( ! $this->isUnlocked() ) {
			http_response_code( 404 );
		}
	}

	public function isUnlocked(): bool {
		return apply_filters(
			Plugin::FILTER_IS_UNLOCKED,
			isset($_GET[$this->plugin->environment->getParamName()])
		);
	}

	public function login_form() {

		if (!$this->isUnlocked()) {

			// The status code is set on login_init, before the page starts printing.

			$waitForSeconds = $this->plugin->environment->getWaitForSeconds();
			$paramName      = $this->plugin->environment->getParamName();

			if ( WP_DEBUG ) {
				echo "<!-- START secure login -->";
			}
			$img = esc_url( get_admin_url() . 'images/spinner.gif' );
			// --- START
			echo "<div id='secure-login-wrapper'><img src='$img' alt='' />";

			// ------ wait for secure login ---
			echo "<div id='wait-for-secure-login'>";
			printf( "<p>%s</p>", esc_html__( "Securing login...", 'a-little-more-secure' ) );
			$text = sprintf(
				esc_html__( "%s seconds left", 'a-little-more-secure' ),
				"<span id='wait-for-secure-login__seconds'>" . (int) $waitForSeconds . "</span>"
			);
			echo "<p><i>$text</i></p>";
			echo "</div>";

			// ------ redirect to login ---
			printf( "<div id='redirect-to-secure-login'>%s</div>", esc_html__( "Redirect to secure login...", 'a-little-more-secure' ) );

			// --- END
			echo "</div>";

			if ( WP_DEBUG ) {
				echo "<!-- END: secure login -->";
			}

			?>
			<style>
				#secure-login-wrapper {
					position: relative;
					padding-top: 20px;
				}

				#secure-login-wrapper img {
					position: absolute;
					top: 22px;
				}

				#secure-login-wrapper > div {
					padding-left: 30px;
				}

				#wait-for-secure-login, #redirect-to-secure-login {
					position: relative;
					font-size: 1.1rem;

				}

				#wait-for-secure-login p:nth-child(2) {
					font-size: 0.9rem;
				}
			</style>
			<script>
				// Both values come from filters a site can override, so they are
				// encoded instead of pasted into the JavaScript source.
				const waitForSeconds = <?= (int) $waitForSeconds ?>;
				const paramName = <?= wp_json_encode( $paramName ) ?>;
				let waited = 0;

				const waitEl = document.getElementById("wait-for-secure-login");
				const secondsEl = document.getElementById("wait-for-secure-login__seconds");
				const redirectEl = document.getElementById("redirect-to-secure-login");

				redirectEl.style.display = "none";

				document.getElementById("user_login").closest("p").remove();
				document.getElementById("user_pass").closest(".user-pass-wrap").remove();

				const uiInterval = setInterval(function () {
					waited++;
					const remaining = waitForSeconds - waited;
					secondsEl.innerText = remaining >= 0 ? remaining + "" : "0";
					if (remaining <= 0) clearInterval(uiInterval);
				}, 1000);
				setTimeout(function () {
					waitEl.style.display = "none";
					redirectEl.style.display = "inherit";
					const href = window.location.href;
					const hashParts = href.split("#");
					const connector = hashParts[0].indexOf("?") > 0 ? "&" : "?";
					window.location.href = hashParts[0] + connector + encodeURIComponent(paramName) + (hashParts.length > 1 ? "#" + hashParts[1] : "");
				}, waitForSeconds * 1000);
			</script>
			<?php
			login_footer();
			exit;
		} else {
			?>
			<style>
				#secure-login-info {
					padding-bottom: 10px;
					font-size: 14px;
				}
			</style>
			<?php
			printf( "<p id='secure-login-info'>🔒 %s</p>", esc_html__( "Your login is a little more secure.", 'a-little-more-secure' ) );
			$this->nonceField();
		}
	}

	public function nonceField() {
		wp_nonce_field( Plugin::NONCE_ACTION, Plugin::NONCE_NAME );
	}

	public function login_action() {
		if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) {
			return;
		}

		// Anything but a string - an array from "nonce[]=x", for instance - is not
		// a nonce, so it is rejected before wp_verify_nonce() ever sees it.
		$nonce = $_POST[ Plugin::NONCE_NAME ] ?? null;
		$nonce = is_string( $nonce ) ? sanitize_text_field( wp_unslash( $nonce ) ) : '';

		if ( ! wp_verify_nonce( $nonce, Plugin::NONCE_ACTION ) ) {
			wp_die(
				esc_html__( "Sorry, this feels not very secure.", 'a-little-more-secure' ),
				esc_html__( "🔒", 'a-little-more-secure' ),
				[
					"response" => 400,
					"link_text" => esc_html__( "Goto login form", 'a-little-more-secure' ),
					"link_url" => wp_login_url(),
				]
			);
		}
	}

	public function login_form_bottom( $content, $args ) {
		// other login forms that are not on /wp-login.php are ignored by this plugin
		ob_start();
		$this->nonceField();
		$field = ob_get_contents();
		ob_end_clean();

		return $content . $field;
	}
}
