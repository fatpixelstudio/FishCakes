<?php
/**
 * Plugin Name:       FishCakes
 * Plugin URI:        https://github.com/fatpixelstudio/FishCakes
 * Description:       GDPR/AVG-proof, accessible cookie consent for WordPress: consent banner, gated scripts and blocked embeds.
 * Version:           2.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Fat Pixel Studio
 * Author URI:        https://fatpixel.studio
 * License:           MPL-2.0
 * License URI:       https://www.mozilla.org/en-US/MPL/2.0/
 * Text Domain:       fishcakes
 * Domain Path:       /languages
 *
 * @package FishCakes
 */

defined( 'ABSPATH' ) || exit;

define( 'FISHCAKES_VERSION', '2.0.0' );
define( 'FISHCAKES_FILE', __FILE__ );
define( 'FISHCAKES_PATH', plugin_dir_path( __FILE__ ) );
define( 'FISHCAKES_URL', plugin_dir_url( __FILE__ ) );

require_once FISHCAKES_PATH . 'includes/class-fc-settings.php';
require_once FISHCAKES_PATH . 'includes/class-fc-consent.php';
require_once FISHCAKES_PATH . 'includes/class-fc-blocker.php';
require_once FISHCAKES_PATH . 'includes/class-fc-frontend.php';
require_once FISHCAKES_PATH . 'includes/class-fc-admin.php';
require_once FISHCAKES_PATH . 'includes/class-fc-plugin.php';

FC_Plugin::init();
