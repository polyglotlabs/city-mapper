<?php 
/*
 * Plugin Name:       City Mapper
 * Plugin URI:        https://polyglotlabs.com
 * Description:       WordPress Plugin to categorize and display local businesses, attractions, and accommodations to provide users with a detailed city guide.
 * Version:           0.1.1
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
            'rewrite'            => array('slug' => 'city-location'),
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
        $main_category_labels = array(
            'name'              => esc_html('Main Categories'),
            'singular_name'     => esc_html('Main Category'),
            'search_items'      => esc_html('Search Main Categories'),
            'all_items'         => esc_html('All Main Categories'),
            'parent_item'       => esc_html('Parent Main Category'),
            'parent_item_colon' => esc_html('Parent Main Category:'),
            'edit_item'         => esc_html('Edit Main Category'),
            'update_item'       => esc_html('Update Main Category'),
            'add_new_item'      => esc_html('Add New Main Category'),
            'new_item_name'     => esc_html('New Main Category Name'),
            'menu_name'         => esc_html('Main Categories')
        );

        $main_category_args = array(
            'hierarchical'      => true,
            'labels'            => $main_category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'main-category')
        );

        register_taxonomy('main_category', array('city_location'), $main_category_args);

        // Sub Category Taxonomy
        $sub_category_labels = array(
            'name'              => esc_html('Sub Categories'),
            'singular_name'     => esc_html('Sub Category'),
            'search_items'      => esc_html('Search Sub Categories'),
            'all_items'         => esc_html('All Sub Categories'),
            'parent_item'       => esc_html('Parent Sub Category'),
            'parent_item_colon' => esc_html('Parent Sub Category:'),
            'edit_item'         => esc_html('Edit Sub Category'),
            'update_item'       => esc_html('Update Sub Category'),
            'add_new_item'      => esc_html('Add New Sub Category'),
            'new_item_name'     => esc_html('New Sub Category Name'),
            'menu_name'         => esc_html('Sub Categories')
        );

        $sub_category_args = array(
            'hierarchical'      => true,
            'labels'            => $sub_category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'sub-category')
        );

        register_taxonomy('sub_category', array('city_location'), $sub_category_args);
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
        $main_categories = array(
            'Gather', 'Eat', 'Explore', 'Stay'
        );

        $sub_categories = array(
            'Gather' => array('Venues', 'Sports', 'Tours', 'Entertainment'),
            'Eat' => array('Restaurants', 'Coffee Shops', 'Wine, Beer and Cocktails'),
            'Explore' => array('Attractions', 'Recreation & Nature', 'Farmers Markets', 'Music', 'Art', 'Shopping'),
            'Stay' => array('Hotels', 'Motels', 'RV Sites & Campgrounds')
        );

        foreach ($main_categories as $main_category) {
            if (!term_exists($main_category, 'main_category')) {
                $main_term = wp_insert_term(sanitize_text_field($main_category), 'main_category');
            } else {
                $main_term = get_term_by('name', $main_category, 'main_category');
            }
            
            if (!is_wp_error($main_term)) {
                foreach ($sub_categories[$main_category] as $sub_category) {
                    if (!term_exists($sub_category, 'sub_category')) {
                        $sub_term = wp_insert_term(sanitize_text_field($sub_category), 'sub_category', array(
                            'description' => sanitize_text_field('Sub-category of ' . $main_category)
                        ));
                        
                        if (!is_wp_error($sub_term)) {
                            // Create a relationship between main category and sub-category
                            wp_set_object_terms($sub_term['term_id'], $main_term->term_id, 'main_category');
                        }
                    }
                }
            }
        }
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
                    
                    // Check if the post already exists
                    $existing_post = get_page_by_title($post_title, OBJECT, 'city_location');
                    
                    if (!$existing_post) {
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
}

function City_Mapper_init() {
    City_Mapper::get_instance();
}

add_action('plugins_loaded', 'City_Mapper_init');
