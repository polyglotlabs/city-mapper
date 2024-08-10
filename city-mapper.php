<?php 
/*
 * Plugin Name:       City Mapper
 * Plugin URI:        https://polyglotlabs.com
 * Description:       WordPress Plugin to categorize and display local businesses, attractions, and accommodations to provide users with a detailed city guide.
 * Version:           0.2.0
 * Requires at least: 6.1.1
 * Requires PHP:      8.0
 * Author:            Zachary Dodd
 * Author URI:        https://polyglotlabs.com
 * License:           All Rights Reserved.
 */

// Exit if accessed directly
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
        add_action('init', array($this, 'register_cpt'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_shortcode('city_mapper', array($this, 'city_mapper_shortcode'));
        add_filter('post_type_link', array($this, 'custom_post_type_link'), 10, 2);
        add_action('init', array($this, 'custom_rewrite_rules'));
        add_filter('term_link', array($this, 'custom_term_link'), 10, 3);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('template_include', array($this, 'handle_custom_urls'));
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'city_mapper_type';
        return $vars;
    }

    public function register_cpt() {
        $labels = array(
            'name'               => esc_html('City Locations'),
            'singular_name'      => esc_html('City Location'),
            'menu_name'          => esc_html('City Locations'),
            'name_admin_bar'     => esc_html('City Location'),
            'add_new'            => esc_html('Add New'),
            'add_new_item'       => esc_html('Add New City Location'),
            'new_item'           => esc_html('New City Location'),
            'edit_item'          => esc_html('Edit City Location'),
            'view_item'          => esc_html('View City Location'),
            'all_items'          => esc_html('All City Locations'),
            'search_items'       => esc_html('Search City Locations'),
            'parent_item_colon'  => esc_html('Parent City Locations:'),
            'not_found'          => esc_html('No city locations found.'),
            'not_found_in_trash' => esc_html('No city locations found in Trash.')
        );
    
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug' => '%main_category%/%sub_category%', // Custom slug with placeholders
                'with_front' => false
            ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments')
        );
    
        register_post_type('city_location', $args);
    }

    public function register_taxonomies() {
        // Main Category Taxonomy
        $main_category_args = array(
            'hierarchical'      => true,
            'labels'            => $this->get_taxonomy_labels('Main Category', 'Main Categories'),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'main-category')
        );

        register_taxonomy('main_category', array('city_location'), $main_category_args);

        // Sub Category Taxonomy
        $sub_category_args = array(
            'hierarchical'      => true,
            'labels'            => $this->get_taxonomy_labels('Sub Category', 'Sub Categories'),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'sub-category')
        );

        register_taxonomy('sub_category', array('city_location'), $sub_category_args);
    }

    private function get_taxonomy_labels($singular, $plural) {
        return array(
            'name'              => esc_html($plural),
            'singular_name'     => esc_html($singular),
            'search_items'      => esc_html("Search $plural"),
            'all_items'         => esc_html("All $plural"),
            'parent_item'       => esc_html("Parent $singular"),
            'parent_item_colon' => esc_html("Parent $singular:"),
            'edit_item'         => esc_html("Edit $singular"),
            'update_item'       => esc_html("Update $singular"),
            'add_new_item'      => esc_html("Add New $singular"),
            'new_item_name'     => esc_html("New $singular Name"),
            'menu_name'         => esc_html($plural)
        );
    }

    public function custom_post_type_link($post_link, $post) {
        if ($post->post_type === 'city_location') {
            $main_category = wp_get_post_terms($post->ID, 'main_category');
            $sub_category = wp_get_post_terms($post->ID, 'sub_category');
            
            if ($main_category && !is_wp_error($main_category)) {
                $main_slug = $main_category[0]->slug;
                $post_link = str_replace('%main_category%', $main_slug, $post_link);
            }
            
            if ($sub_category && !is_wp_error($sub_category)) {
                $sub_slug = $sub_category[0]->slug;
                $post_link = str_replace('%sub_category%', $sub_slug, $post_link);
            }
        }
        return $post_link;
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

    public function custom_term_link($termlink, $term, $taxonomy) {
        if ($taxonomy === 'main_category') {
            return home_url($term->slug . '/');
        } elseif ($taxonomy === 'sub_category') {
            // Get all associated main categories
            $main_categories = get_terms(array(
                'taxonomy' => 'main_category',
                'object_ids' => get_objects_in_term($term->term_id, 'sub_category'),
                'fields' => 'slugs',
            ));

            if (!empty($main_categories) && !is_wp_error($main_categories)) {
                // Use the first associated main category
                return home_url($main_categories[0] . '/' . $term->slug . '/');
            }
        }
        return $termlink;
    }

    public function add_admin_menu() {
        add_menu_page(
            esc_html('City Mapper Settings'),
            esc_html('City Mapper'),
            'manage_options',
            'city-mapper-settings',
            array($this, 'render_settings_page'),
            'dashicons-location-alt',
            30
        );
    }

    public function render_settings_page() {
        // only allow access to users with manage_options capability
        if (!current_user_can('manage_options')) {
            wp_die(esc_html('You do not have sufficient permissions to access this page. '));
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html('City Mapper Settings'); ?></h1>
            <div class="card">
                <h2 class="title"><?php echo esc_html('Import Defaults & Deletion Tool'); ?></h2>
                <form method="post" action="">
                    <?php
                    wp_nonce_field('city_mapper_action', 'city_mapper_nonce');
                    if (isset($_POST['create_defaults']) && check_admin_referer('city_mapper_action', 'city_mapper_nonce')) {
                        $this->add_default_terms();
                        $this->add_example_posts();
                        echo '<div class="notice notice-success"><p>' . esc_html('Default terms and example posts created successfully!') . '</p></div>';
                    }
                    if (isset($_POST['delete_defaults']) && check_admin_referer('city_mapper_action', 'city_mapper_nonce')) {
                        $this->delete_default_terms();
                        $this->delete_example_posts();
                        echo '<div class="notice notice-success"><p>' . esc_html('Default terms and example posts deleted successfully!') . '</p></div>';
                    }
                    ?>
                    <p class="submit">
                        <input type="submit" name="create_defaults" class="button button-primary" value="<?php echo esc_attr('Create Defaults'); ?>">
                        <input type="submit" name="delete_defaults" class="button" value="<?php echo esc_attr('Delete Defaults'); ?>">
                    </p>
                </form>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        wp_enqueue_script('jquery');
    }

    private function add_default_terms() {
        $categories = array(
            'Gather' => array('Venues', 'Sports', 'Tours', 'Entertainment'),
            'Eat' => array('Restaurants', 'Coffee Shops', 'Wine, Beer and Cocktails'),
            'Explore' => array('Attractions', 'Recreation & Nature', 'Farmers Markets', 'Music', 'Art', 'Shopping'),
            'Stay' => array('Hotels', 'Motels', 'RV Sites & Campgrounds')
        );

        foreach ($categories as $main_category => $sub_categories) {
            $main_term = $this->get_or_create_term($main_category, 'main_category');
            
            if (!is_wp_error($main_term)) {
                foreach ($sub_categories as $sub_category) {
                    $sub_term = $this->get_or_create_term($sub_category, 'sub_category', "Sub-category of $main_category");
                    
                    if (!is_wp_error($sub_term)) {
                        wp_set_object_terms($sub_term->term_id, $main_term->term_id, 'main_category');
                    }
                }
            }
        }
    }

    private function get_or_create_term($name, $taxonomy, $description = '') {
        $term = get_term_by('name', $name, $taxonomy);
        if (!$term) {
            $result = wp_insert_term(sanitize_text_field($name), $taxonomy, array('description' => sanitize_text_field($description)));
            $term = is_wp_error($result) ? $result : get_term($result['term_id'], $taxonomy);
        }
        return $term;
    }

    private function add_example_posts() {
        $sub_categories = array(
            'Gather' => array('Venues', 'Sports', 'Tours', 'Entertainment'),
            'Eat' => array('Restaurants', 'Coffee Shops', 'Wine, Beer and Cocktails'),
            'Explore' => array('Attractions', 'Recreation & Nature', 'Farmers Markets', 'Music', 'Art', 'Shopping'),
            'Stay' => array('Hotels', 'Motels', 'RV Sites & Campgrounds')
        );

        foreach ($sub_categories as $main_category => $sub_category_list) {
            foreach ($sub_category_list as $sub_category) {
                for ($i = 1; $i <= 3; $i++) {
                    $post_title = sanitize_text_field($sub_category . ' Example ' . $i);
                    
                    // Check if the post already exists using WP_Query
                    $existing_post_query = new WP_Query(array(
                        'post_type' => 'city_location',
                        'post_status' => 'any',
                        'title' => $post_title,
                        'posts_per_page' => 1,
                    ));
                    
                    if (!$existing_post_query->have_posts()) {
                        $post_content = sanitize_text_field('This is an example post for the ' . $sub_category . ' sub-category.');
                        
                        $post_id = wp_insert_post(array(
                            'post_title'   => $post_title,
                            'post_content' => $post_content,
                            'post_status'  => 'publish',
                            'post_type'    => 'city_location',
                        ));

                        if ($post_id) {
                            // Set the main category
                            wp_set_object_terms($post_id, sanitize_text_field($main_category), 'main_category');
                            
                            // Set the sub-category
                            wp_set_object_terms($post_id, sanitize_text_field($sub_category), 'sub_category');
                        }
                    }
                }
            }
        }
    }

    private function delete_default_terms() {
        $taxonomies = array('main_category', 'sub_category');
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }

    private function delete_example_posts() {
        $args = array(
            'post_type' => 'city_location',
            'posts_per_page' => -1,
        );
        $posts = get_posts($args);
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }

    public function city_mapper_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'sub_category' => '',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ), $atts, 'city_mapper');

        ob_start();

        if (empty($atts['category'])) {
            $this->display_all_main_categories();
        } elseif (empty($atts['sub_category'])) {
            $this->display_main_category($atts['category'], $atts['posts_per_page'], $atts['orderby'], $atts['order']);
        } else {
            $this->display_sub_category($atts['category'], $atts['sub_category'], $atts['posts_per_page'], $atts['orderby'], $atts['order']);
        }

        return ob_get_clean();
    }

    private function display_main_category($category, $posts_per_page, $orderby, $order) {
        $main_category = get_term_by('slug', $category, 'main_category');
        $output = '';

        if ($main_category) {
            $output .= '<h2>' . esc_html($main_category->name) . '</h2>';

            $sub_categories = get_terms(array(
                'taxonomy' => 'sub_category',
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => 'main_category',
                        'value' => $main_category->term_id,
                        'compare' => '='
                    )
                )
            ));

            if (!empty($sub_categories) && !is_wp_error($sub_categories)) {
                $output .= '<div class="city-mapper-sub-categories">';
                foreach ($sub_categories as $sub_category) {
                    $output .= sprintf(
                        '<a href="%s" class="city-mapper-sub-category">%s</a>',
                        esc_url(get_term_link($sub_category)),
                        esc_html($sub_category->name)
                    );
                }
                $output .= '</div>';
            }

            $output .= $this->display_posts($category, '', $posts_per_page, $orderby, $order);
        }

        echo $output;
    }

    private function display_sub_category($category, $sub_category, $posts_per_page, $orderby, $order) {
        $main_category = get_term_by('slug', $category, 'main_category');
        $sub_category_term = get_term_by('slug', $sub_category, 'sub_category');
        $output = '';

        if ($main_category && $sub_category_term) {
            $output .= '<h2>' . esc_html($main_category->name) . ' - ' . esc_html($sub_category_term->name) . '</h2>';

            $output .= $this->display_posts($category, $sub_category, $posts_per_page, $orderby, $order);
        }

        echo $output;
    }

    private function display_posts($category, $sub_category, $posts_per_page, $orderby, $order) {
        $args = array(
            'post_type' => 'city_location',
            'posts_per_page' => $posts_per_page,
            'orderby' => $orderby,
            'order' => $order,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'main_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            ),
        );

        if (!empty($sub_category)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'sub_category',
                'field' => 'slug',
                'terms' => $sub_category,
            );
        }

        $query = new WP_Query($args);

        $output = '';
        if ($query->have_posts()) {
            $output .= '<div class="city-mapper-posts">';
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="city-mapper-post">';
                $output .= '<h3><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></h3>';
                if (has_post_thumbnail()) {
                    $output .= '<div class="city-mapper-thumbnail">';
                    $output .= get_the_post_thumbnail(null, 'thumbnail');
                    $output .= '</div>';
                }
                $output .= '<div class="city-mapper-excerpt">' . get_the_excerpt() . '</div>';
                $output .= '</div>';
            }
            $output .= '</div>';

            // Pagination
            $big = 999999999; // need an unlikely integer
            $output .= '<div class="city-mapper-pagination">';
            $output .= paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $query->max_num_pages
            ));
            $output .= '</div>';

            wp_reset_postdata();
        } else {
            $output .= '<p>No posts found.</p>';
        }

        return $output;
    }

    private function display_all_main_categories() {
        $main_categories = get_terms(array(
            'taxonomy' => 'main_category',
            'hide_empty' => false,
        ));

        if (!empty($main_categories) && !is_wp_error($main_categories)) {
            echo '<div class="city-mapper-tabs">';
            foreach ($main_categories as $category) {
                printf(
                    '<a href="%s" class="city-mapper-tab">%s</a>',
                    esc_url(get_term_link($category)),
                    esc_html($category->name)
                );
            }
            echo '</div>';
        }
    }

    public function handle_custom_urls($template) {
        $city_mapper_type = get_query_var('city_mapper_type');
        $main_category = get_query_var('main_category');
        $sub_category = get_query_var('sub_category');

        if ($city_mapper_type === 'main') {
            return $this->get_template('main-category.php');
        } elseif ($city_mapper_type === 'sub') {
            return $this->get_template('sub-category.php');
        }

        return $template;
    }

    private function get_template($template_name) {
        $template_path = plugin_dir_path(__FILE__) . 'templates/' . $template_name;
        if (file_exists($template_path)) {
            return $template_path;
        }
        return get_page_template(); // Fallback to default page template
    }
}

function City_Mapper_init() {
    City_Mapper::get_instance();
}

add_action('plugins_loaded', 'City_Mapper_init');


function City_Mapper_flush_rewrite_rules() {
    City_Mapper::get_instance();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'City_Mapper_flush_rewrite_rules');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');