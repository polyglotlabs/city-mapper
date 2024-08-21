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
        add_action('init', array($this, 'add_rewrite_tags'));
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
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);

        add_filter('post_type_link', [$this->cpt, 'custom_post_type_link'], 10, 2);
        add_filter('term_link', [$this->taxonomies, 'custom_term_link'], 10, 3);

        add_filter('template_include', [$this, 'handle_custom_urls']);
        add_shortcode('city_mapper', [$this->shortcode, 'city_mapper_shortcode']);

        add_action('admin_menu', [$this->admin, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this->admin, 'enqueue_admin_scripts']);
        add_action('wp_ajax_city_mapper_create_defaults', [$this->admin, 'import_defaults']);
        add_action('wp_ajax_city_mapper_delete_defaults', [$this->admin, 'delete_defaults']);

        add_action('wp', [$this, 'log_matched_rule']);
        add_filter('generate_rewrite_rules', [$this, 'log_rewrite_rules']);

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

    public function add_rewrite_rules() {
        // Rule for category with pagination
        add_rewrite_rule(
            '^(gather|eat|explore|stay)/page/([0-9]+)/?$',
            'index.php?pagename=$matches[1]&paged=$matches[2]',
            0
        );
        
        // Rule for single city location
        add_rewrite_rule(
            '^(gather|eat|explore|stay)/([^/]+)/([^/]+)/?$',
            'index.php?pagename=$matches[1]&sub_category=$matches[2]&city_location=$matches[3]',
            1
        );

        add_rewrite_rule(
            '^(gather|eat|explore|stay)/?$',
            'index.php?pagename=$matches[1]',
            'top'
        );

        
        
        // Rule for sub-category with pagination
        add_rewrite_rule(
            '^(gather|eat|explore|stay)/([^/]+)/page/([0-9]+)/?$',
            'index.php?pagename=$matches[1]&sub_category=$matches[2]&paged=$matches[3]',
            1
        );

        // Rule for sub-category without pagination
        add_rewrite_rule(
            '^(gather|eat|explore|stay)/([^/]+)/?$',
            'index.php?pagename=$matches[1]&sub_category=$matches[2]',
            1
        );
        add_rewrite_rule(
            '^(gather|eat|explore|stay)/([^/]+)/page/([0-9]+)/?$',
            'index.php?pagename=$matches[1]&sub_category=$matches[2]&paged=$matches[3]',
            'top'
        );
       

        
        
        
    }

    public function add_query_vars($vars) {
        $new_vars = ['main_category', 'sub_category', 'city_location', 'paged'];
        return array_merge($vars, $new_vars);
    }

    public function add_rewrite_tags() {
        // add_rewrite_tag('%main_category%', '([^&]+)');
        // add_rewrite_tag('%sub_category%', '([^&]+)');
        // add_rewrite_tag('%city_location%', '([^&]+)');
    }

    public function handle_custom_urls($template) {
        global $wp_query;

        $main_category = get_query_var('main_category');
        $sub_category = get_query_var('sub_category');
        $city_location = get_query_var('city_location');

        if ($main_category) {
            if ($sub_category) {
                if ($city_location) {
                    return CITY_MAPPER_PLUGIN_DIR . 'templates/single-city_location.php';
                }
                return CITY_MAPPER_PLUGIN_DIR . 'templates/sub-category.php';
            }
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
            wp_enqueue_style(
                'city-mapper',
                CITY_MAPPER_PLUGIN_URL . 'assets/city-mapper.css',
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
    public function log_rewrite_rules($wp_rewrite) {
        return $wp_rewrite;
    }

    public function log_matched_rule() {
        global $wp, $wp_rewrite;
        $matched_rule = $wp->matched_rule;
        $matched_query = $wp->matched_query;
        $request = $wp->request;
        // print_r($matched_rule);
        // print_r($matched_query);
        // print_r($request);
    }
}
