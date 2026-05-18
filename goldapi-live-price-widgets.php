<?php
/**
 * Plugin Name: GoldAPI Live Gold Price Widgets
 * Plugin URI: https://www.goldapi.io/
 * Description: Embed live precious metals prices from GoldAPI.io with shortcodes, widgets, and a lightweight REST-powered refresh script.
 * Version: 1.0.0
 * Author: GoldAPI.io
 * Author URI: https://www.goldapi.io/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: goldapi-live-price-widgets
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package GoldAPILivePriceWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOLDAPI_LIVE_PRICE_WIDGETS_VERSION', '1.0.0' );
define( 'GOLDAPI_LIVE_PRICE_WIDGETS_FILE', __FILE__ );
define( 'GOLDAPI_LIVE_PRICE_WIDGETS_DIR', plugin_dir_path( __FILE__ ) );
define( 'GOLDAPI_LIVE_PRICE_WIDGETS_URL', plugin_dir_url( __FILE__ ) );

require_once GOLDAPI_LIVE_PRICE_WIDGETS_DIR . 'includes/class-goldapi-api-client.php';
require_once GOLDAPI_LIVE_PRICE_WIDGETS_DIR . 'includes/class-goldapi-settings.php';
require_once GOLDAPI_LIVE_PRICE_WIDGETS_DIR . 'includes/class-goldapi-shortcodes.php';
require_once GOLDAPI_LIVE_PRICE_WIDGETS_DIR . 'includes/class-goldapi-rest.php';

add_action(
	'plugins_loaded',
	static function (): void {
		GoldAPI_Settings::init();
		GoldAPI_Shortcodes::init();
		GoldAPI_REST::init();
	}
);
