<?php

class City_Mapper_Taxonomies {
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
}