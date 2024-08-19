<?php

require_once CITY_MAPPER_PLUGIN_DIR . 'includes/class-city-mapper-display.php';

class City_Mapper_Shortcode {
    private $display;

    public function __construct() {
        $this->display = new City_Mapper_Display();
    }

    public function city_mapper_shortcode($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'sub_category' => '',
            'posts_per_page' => 9,
            'orderby' => 'date',
            'order' => 'DESC'
        ], $atts, 'city_mapper');

        ob_start();

        $main_category = get_query_var('main_category') ?: $atts['category'];
        $sub_category = get_query_var('sub_category') ?: $atts['sub_category'];

        echo '<div class="city-mapper-container">';
        
        // Display sub-categories
        $this->display_sub_categories($main_category, $sub_category);

        // Display content
        
        if ($sub_category) {
            $this->display->display_sub_category($main_category, $sub_category, $atts['posts_per_page'], $atts['orderby'], $atts['order']);
        } else {
            $this->display->display_main_category($main_category, $atts['posts_per_page'], $atts['orderby'], $atts['order']);
        }

        echo '</div>';

        return ob_get_clean();
    }

    private function display_sub_categories($main_category, $current_sub_category = '') {
        // Check if $main_category is a slug or an ID
        if (!is_numeric($main_category)) {
            // It's a slug, so we need to get the corresponding page
            $main_page = get_page_by_path($main_category);
            if ($main_page) {
                $main_category_id = $main_page->ID;
            } else {
                return; // Exit if we can't find the page
            }
        } else {
            $main_category_id = $main_category;
        }

        $sub_categories = get_terms([
            'taxonomy' => 'sub_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'main_category',
                    'value' => $main_category_id,
                    'compare' => '='
                ]
            ]
        ]);

        if (!empty($sub_categories) && !is_wp_error($sub_categories)) {
            echo '<div class="city-mapper-tabs">';
            foreach ($sub_categories as $sub_category) {
                $url = home_url("/{$main_category}/{$sub_category->slug}/");
                $active_class = ($current_sub_category === $sub_category->slug) ? ' active' : '';
                printf(
                    '<a href="%s" class="city-mapper-tab%s">%s</a>',
                    esc_url($url),
                    esc_attr($active_class),
                    esc_html($sub_category->name)
                );
            }
            echo '</div>';
        }
    }
}