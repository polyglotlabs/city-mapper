<?php

class City_Mapper_Admin {
    private $main_categories = [
        'Gather' => ['Venues', 'Sports', 'Tours', 'Entertainment'],
        'Eat' => ['Restaurants', 'Coffee Shops', 'Wine, Beer and Cocktails'],
        'Explore' => ['Attractions', 'Recreation & Nature', 'Farmers Markets', 'Music', 'Art', 'Shopping'],
        'Stay' => ['Hotels', 'Motels', 'RV Sites & Campgrounds']
    ];

    public function add_admin_menu() {
        add_menu_page(
            'City Mapper Settings',
            'City Mapper',
            'manage_options',
            'city-mapper-settings',
            [$this, 'render_settings_page'],
            'dashicons-location-alt',
            30
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        ?>
        <div class="wrap">
            <h1>City Mapper Settings</h1>
            <div class="card">
                <h2 class="title">Import Defaults & Deletion Tool</h2>
                <form method="post" action="" id="city-mapper-admin-form">
                    <?php wp_nonce_field('city_mapper_action', 'city_mapper_nonce'); ?>
                    <div id="city-mapper-message" class="notice" style="display: none;"></div>
                    <p class="submit">
                        <input type="submit" name="create_defaults" id="create_defaults" class="button button-primary" value="Create Defaults">
                        <input type="submit" name="delete_defaults" id="delete_defaults" class="button" value="Delete Defaults">
                    </p>
                </form>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#city-mapper-admin-form').on('submit', function(e) {
                e.preventDefault();
                var action = $(document.activeElement).attr('name');
                var nonce = $('#city_mapper_nonce').val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'city_mapper_' + action,
                        nonce: nonce
                    },
                    success: function(response) {
                        var messageDiv = $('#city-mapper-message');
                        messageDiv.removeClass('notice-success notice-error').hide();
                        
                        if (response.success) {
                            messageDiv.addClass('notice-success').html('<p>' + response.data + '</p>').show();
                        } else {
                            messageDiv.addClass('notice-error').html('<p>Error: ' + response.data + '</p>').show();
                        }
                    },
                    error: function() {
                        $('#city-mapper-message').removeClass('notice-success').addClass('notice-error')
                            .html('<p>An error occurred. Please try again.</p>').show();
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function enqueue_admin_scripts($hook) {
        wp_enqueue_script('jquery');
    }

    private function import_default_terms() {
        foreach ($this->main_categories as $main_category => $sub_categories) {
            // Check if a page with the main category name already exists
            $existing_page = get_page_by_path(sanitize_title($main_category));
            
            if ($existing_page) {
                $main_page = $existing_page->ID;
            } else {
                $main_page = wp_insert_post([
                    'post_title' => $main_category,
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_type' => 'page'
                ]);
            }

            if (!is_wp_error($main_page)) {
                foreach ($sub_categories as $sub_category) {
                    $sub_term = wp_insert_term($sub_category, 'sub_category');
                    if (!is_wp_error($sub_term)) {
                        update_term_meta($sub_term['term_id'], 'main_category', $main_page);
                    }
                }
            }
        }
    }

    private function import_example_posts() {
        foreach ($this->main_categories as $main_category => $sub_categories) {
            foreach ($sub_categories as $sub_category) {
                for ($i = 1; $i <= 15; $i++) {
                    $post_id = wp_insert_post([
                        'post_title' => "{$main_category} {$sub_category} Example Post {$i}",
                        'post_content' => "This is example post {$i} for {$main_category} - {$sub_category}.",
                        'post_status' => 'publish',
                        'post_type' => 'city_location'
                    ]);

                    if ($post_id) {
                        wp_set_object_terms($post_id, $main_category, 'main_category');
                        wp_set_object_terms($post_id, $sub_category, 'sub_category');
                    }
                }
            }
        }
    }

    private function delete_default_terms() {
        $taxonomies = ['main_category', 'sub_category'];
        foreach ($taxonomies as $taxonomy) {
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }

    private function delete_example_posts() {
        $posts = get_posts([
            'post_type' => 'city_location',
            'posts_per_page' => -1,
        ]);
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true);
        }
    }

    public function import_defaults() {
        $this->check_permissions();
        $this->import_default_terms();
        $this->import_example_posts();
        wp_send_json_success('Default terms and example posts imported successfully.');
    }

    public function delete_defaults() {
        $this->check_permissions();
        $this->delete_default_terms();
        $this->delete_example_posts();
        wp_send_json_success('Default terms and example posts deleted successfully.');
    }

    private function check_permissions() {
        check_ajax_referer('city_mapper_action', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('You do not have sufficient permissions to perform this action.');
        }
    }
}