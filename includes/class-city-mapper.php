<?php

class City_Mapper {
    private $cpt;
    private $taxonomies;
    private $shortcode;
    private $admin;
    private $display;

    public function __construct() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies() {
        require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-cpt.php';
        require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-taxonomies.php';
        require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-shortcode.php';
        require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-admin.php';
        require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-display.php';

        $this->cpt = new City_Mapper_CPT();
        $this->taxonomies = new City_Mapper_Taxonomies();
        $this->shortcode = new City_Mapper_Shortcode();
        $this->admin = new City_Mapper_Admin();
        $this->display = new City_Mapper_Display();
    }

    private function define_hooks() {
        add_action('init', array($this->cpt, 'register_cpt'));
        add_action('init', array($this->taxonomies, 'register_taxonomies'));
        add_action('init', array($this, 'custom_rewrite_rules'));
        add_filter('post_type_link', array($this->cpt, 'custom_post_type_link'), 10, 2);
        add_filter('term_link', array($this->taxonomies, 'custom_term_link'), 10, 3);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('template_include', array($this->display, 'handle_custom_urls'));
        add_shortcode('city_mapper', array($this->shortcode, 'city_mapper_shortcode'));
        add_action('admin_menu', array($this->admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this->admin, 'enqueue_admin_scripts'));
    }

    public function run() {
        $this->define_hooks();
    }

    public function custom_rewrite_rules() {
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?post_type=city_location&main_category=$matches[1]&sub_category=$matches[2]&city_location=$matches[3]',
            'top'
        );
        add_rewrite_rule(
            '^([^/]+)/?$',
            'index.php?main_category=$matches[1]&city_mapper_type=main',
            'top'
        );
        add_rewrite_rule(
            '^([^/]+)/([^/]+)/?$',
            'index.php?main_category=$matches[1]&sub_category=$matches[2]&city_mapper_type=sub',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'city_mapper_type';
        return $vars;
    }

    public static function activate() {
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}