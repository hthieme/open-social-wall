<?php
/**
 * Plugin Name:       Open Social Wall
 * Description:       Aggregate public Mastodon and Pixelfed feeds into a responsive masonry-style social wall.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Open Social Wall contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       open-social-wall
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OSW_VERSION', '0.1.0');
define('OSW_PLUGIN_FILE', __FILE__);
define('OSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OSW_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once OSW_PLUGIN_DIR . 'includes/class-osw-feed.php';
require_once OSW_PLUGIN_DIR . 'includes/class-osw-plugin.php';

OSW_Plugin::instance();
