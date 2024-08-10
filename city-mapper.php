<?php 
/*
 * Plugin Name:       City Mapper
 * Plugin URI:        https://polyglotlabs.com
 * Description:       WordPress Plugin to categorize and display local businesses, attractions, and accommodations to provide users with a detailed city guide.
 * Version:           0.0.1
 * Requires at least: 6.1.1
 * Requires PHP:      8.0
 * Author:            Zachary Dodd
 * Author URI:        https://polyglotlabs.com
 * License:           All Rights Reserved.
 */

if (!defined('ABSPATH')) {
    exit;
}

class City_Mapper {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }


    public function enqueue_admin_scripts($hook) {
        wp_enqueue_script('jquery');
    }

}

function City_Mapper_init() {
    City_Mapper::get_instance();
}

add_action('plugins_loaded', 'City_Mapper_init');

