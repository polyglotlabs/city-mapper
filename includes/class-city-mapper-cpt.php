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
                'slug' => '%associated_page%/%sub_category%',
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
            $sub_category = wp_get_post_terms($post->ID, 'sub_category');
            
            if ($sub_category && !is_wp_error($sub_category)) {
                $sub_slug = $sub_category[0]->slug;
                $main_category_id = get_term_meta($sub_category[0]->term_id, 'main_category', true);
                
                if ($main_category_id) {
                    $main_category = get_post($main_category_id);
                    if ($main_category && $main_category->post_type == 'page') {
                        $associated_page_slug = $main_category->post_name;
                        $post_link = str_replace('%associated_page%', $associated_page_slug, $post_link);
                        $post_link = str_replace('%sub_category%', $sub_slug, $post_link);
                    }
                }
            }
        }
        return $post_link;
    }
}