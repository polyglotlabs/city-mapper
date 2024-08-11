<?php
/*
 * Plugin Name: City Mapper
 * Plugin URI: https://polyglotlabs.com
 * Description: WordPress Plugin to categorize and display local businesses, attractions, and accommodations to provide users with a detailed city guide.
 * Version: 0.3.0
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

// Define plugin constants
define('CITY_MAPPER_VERSION', '0.3.0');
define('CITY_MAPPER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CITY_MAPPER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the main City_Mapper class
require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('City_Mapper', 'activate'));
register_deactivation_hook(__FILE__, array('City_Mapper', 'deactivate'));

// Initialize the plugin
function run_city_mapper() {
    $plugin = new City_Mapper();
    $plugin->run();
}
add_action('plugins_loaded', 'run_city_mapper');