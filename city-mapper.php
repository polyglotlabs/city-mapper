<?php
/*
 * Plugin Name: City Mapper
 * Plugin URI: https://polyglotlabs.com
 * Description: WordPress Plugin to categorize and display local businesses, attractions, and accommodations to provide users with a detailed city guide.
 * Version: 0.5.0
 * Requires at least: 6.1.1
 * Requires PHP: 8.0
 * Author: Zachary Dodd
 * Author URI: https://polyglotlabs.com
 * License: All Rights Reserved.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('CITY_MAPPER_VERSION', '0.5.0');
define('CITY_MAPPER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CITY_MAPPER_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper.php';

register_activation_hook(__FILE__, ['City_Mapper', 'activate']);
register_deactivation_hook(__FILE__, ['City_Mapper', 'deactivate']);

// Initialize the plugin
function run_city_mapper(): void {
    $plugin = new City_Mapper();
    $plugin->run();
}
add_action('plugins_loaded', 'run_city_mapper');

function city_mapper_check_version(): void {
    if (get_option('city_mapper_version') !== CITY_MAPPER_VERSION) {
        flush_rewrite_rules();
        update_option('city_mapper_version', CITY_MAPPER_VERSION);
    }
}
add_action('plugins_loaded', 'city_mapper_check_version');

require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-shortcode.php';

require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-display.php';