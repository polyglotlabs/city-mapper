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
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ], $atts, 'city_mapper');

        ob_start();

        if (empty($atts['category'])) {
            $this->display_all_main_categories();
        } elseif (empty($atts['sub_category'])) {
            $this->display->display_main_category($atts['category'], $atts['posts_per_page'], $atts['orderby'], $atts['order']);
        } else {
            $this->display->display_sub_category($atts['category'], $atts['sub_category'], $atts['posts_per_page'], $atts['orderby'], $atts['order']);
        }

        return ob_get_clean();
    }

    private function display_all_main_categories() {
        $main_categories = get_terms([
            'taxonomy' => 'main_category',
            'hide_empty' => false,
        ]);

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
}