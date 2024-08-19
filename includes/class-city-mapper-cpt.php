<?php

class City_Mapper_CPT {
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
                'slug' => '%main_category%/%sub_category%',
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

    public function custom_post_type_link($post_link, $post) {
        if ($post->post_type === 'city_location') {
            $sub_categories = wp_get_post_terms($post->ID, 'sub_category');
            if ($sub_categories && !is_wp_error($sub_categories)) {
                $sub_category = $sub_categories[0];
                $main_category_id = get_term_meta($sub_category->term_id, 'main_category', true);
                if ($main_category_id) {
                    $main_category = get_post($main_category_id);
                    if ($main_category && $main_category->post_type == 'page') {
                        $post_link = str_replace('%main_category%', $main_category->post_name, $post_link);
                        $post_link = str_replace('%sub_category%', $sub_category->slug, $post_link);
                    }
                }
            }
        }
        return $post_link;
    }

    public function save_post_taxonomies($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the post type
        if (get_post_type($post_id) !== 'city_location') {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save sub_category
        if (isset($_POST['tax_input']['sub_category'])) {
            $sub_categories = array_map('intval', $_POST['tax_input']['sub_category']);
            wp_set_object_terms($post_id, $sub_categories, 'sub_category');
        }
    }
}