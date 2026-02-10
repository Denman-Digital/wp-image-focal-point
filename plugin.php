<?php

 /**
 * Plugin Name: Image Focal Point
 * Plugin URI: https://github.com/Denman-Digital/image-focal-point
 * Update URI: image-focal-point
 * Description: Set background focus position for media images.
 * Author: Denman Digital
 * Author URI: https://denman.digital/
 * Version: 2.3.7
 * Tested up to: 6.8
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: img-focal-point
 * Domain Path: /languages/
 * @package image-focal-point
 */

namespace Image_Focal_Point;

// Exit if accessed directly.
defined('ABSPATH') ||	exit;

if (!defined("WP_DEBUG")) {
	define("WP_DEBUG", false);
}
if (!defined("WP_DEBUG_LOG")) {
	define("WP_DEBUG_LOG", false);
}

define("IFP_PLUGIN_BASENAME", plugin_basename(__FILE__));
define("IFP_PLUGIN_FILE", basename(__FILE__));
define("IFP_PLUGIN_URI", plugin_dir_url(__FILE__));
define("IFP_PLUGIN_PATH", plugin_dir_path(__FILE__));

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

require_once plugin_dir_path(__FILE__) . 'src/init.php';

require_once plugin_dir_path(__FILE__) . 'update.php';

/**
 * Load plugin textdomain.
 */
function load_textdomain()
{
	load_plugin_textdomain('img-focal-point', false, dirname(IFP_PLUGIN_BASENAME) . '/languages');
}
add_action('init', __NAMESPACE__ . '\load_textdomain');












