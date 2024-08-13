<?php

class City_Mapper {
    private $cpt;
    private $taxonomies;
    private $shortcode;
    private $admin;
    private $display;

    public function __construct() {
        // grimacing
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
    }

    public function run() {
        $this->load_dependencies();
        $this->define_hooks();
    }

    private function load_dependencies() {
        $files = [
            'cpt', 'taxonomies', 'shortcode', 'admin', 'display'
        ];

        foreach ($files as $file) {
            require_once CITY_MAPPER_PLUGIN_DIR . "includes/class-city-mapper-{$file}.php";
            $class_name = 'City_Mapper_' . ucfirst($file);
            $this->$file = new $class_name();
        }
    }

    private function define_hooks() {
        add_action('init', [$this->cpt, 'register_cpt']);
        add_action('init', [$this->taxonomies, 'register_taxonomies']);
        add_action('init', [$this, 'custom_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);

        add_filter('post_type_link', [$this->cpt, 'custom_post_type_link'], 10, 2);
        add_filter('term_link', [$this->taxonomies, 'custom_term_link'], 10, 3);

        add_filter('template_include', [$this->display, 'handle_custom_urls']);
        add_shortcode('city_mapper', [$this->shortcode, 'city_mapper_shortcode']);

        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_scripts']);
        add_action('wp_ajax_city_mapper_create_defaults', [$this->admin, 'import_defaults']);
        add_action('wp_ajax_city_mapper_delete_defaults', [$this->admin, 'delete_defaults']);

        $taxonomy_hooks = [
            'created_sub_category' => 'save_sub_category_main_category',
            'edited_sub_category' => 'save_sub_category_main_category',
            'sub_category_add_form_fields' => 'add_main_category_field',
            'sub_category_edit_form_fields' => 'edit_main_category_field'
        ];

        foreach ($taxonomy_hooks as $hook => $method) {
            add_action($hook, [$this->taxonomies, $method], 10, 2);
        }
    }

    public function custom_rewrite_rules() {
        $rules = [
            '^([^/]+)/([^/]+)/([^/]+)/?$' => 'index.php?post_type=city_location&main_category=$matches[1]&sub_category=$matches[2]&city_location=$matches[3]&city_mapper_rule=location',
            '^([^/]+)/([^/]+)/?$' => 'index.php?main_category=$matches[1]&sub_category=$matches[2]&city_mapper_rule=sub_category',
            '^([^/]+)/([^/]+)/page/([0-9]+)/?$' => 'index.php?main_category=$matches[1]&sub_category=$matches[2]&paged=$matches[3]&city_mapper_rule=sub_category_paged',
            '^([^/]+)/page/([0-9]+)/?$' => 'index.php?main_category=$matches[1]&paged=$matches[2]&city_mapper_rule=main_category_paged',
            '^([^/]+)/?$' => 'index.php?main_category=$matches[1]&city_mapper_rule=main_category'
        ];

        foreach ($rules as $regex => $query) {
            add_rewrite_rule($regex, $query, 'top');
        }
    }

    public function add_query_vars($vars) {
        $new_vars = ['city_mapper_type', 'main_category', 'sub_category', 'paged', 'city_mapper_rule'];
        $vars = array_merge($vars, $new_vars);
        
        return $vars;
    }

    public function handle_custom_urls($template) {
        $city_mapper_rule = get_query_var('city_mapper_rule');
        $main_category = get_query_var('main_category');
        $sub_category = get_query_var('sub_category');
        
        if ($city_mapper_rule) {
            switch ($city_mapper_rule) {
                case 'main_category':
                case 'main_category_paged':
                    return CITY_MAPPER_PLUGIN_DIR . 'templates/main-category.php';
                case 'sub_category':
                case 'sub_category_paged':
                    return CITY_MAPPER_PLUGIN_DIR . 'templates/sub-category.php';
                case 'location':
                    return CITY_MAPPER_PLUGIN_DIR . 'templates/single-city_location.php';
            }
        } elseif ($main_category && $sub_category) {
            return CITY_MAPPER_PLUGIN_DIR . 'templates/sub-category.php';
        } elseif ($main_category) {
            return CITY_MAPPER_PLUGIN_DIR . 'templates/main-category.php';
        }
        
        return $template;
    }

    public function enqueue_frontend_styles() {
            wp_enqueue_style(
                'city-mapper-frontend',
                CITY_MAPPER_PLUGIN_URL . 'assets/city-mapper-frontend.css',
                array(),
                CITY_MAPPER_VERSION
            );
    }

    public static function activate() {
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}