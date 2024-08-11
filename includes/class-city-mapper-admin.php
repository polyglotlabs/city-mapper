<?php

class City_Mapper_Admin {
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
}