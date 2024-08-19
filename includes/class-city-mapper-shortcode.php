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
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : $atts['orderby'];
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : $atts['order'];

        // Reset paged to 1 if sorting has changed
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        if (isset($_GET['orderby']) || isset($_GET['order'])) {
            $paged = 1;
        }

        echo '<div class="city-mapper-container">';
        
        // Display sub-categories
        $this->display_sub_categories($main_category, $sub_category);

        // Display sorting dropdown
        $this->display_sorting_dropdown($orderby, $order);

        // Display content
        if ($sub_category) {
            $this->display->display_sub_category($main_category, $sub_category, $atts['posts_per_page'], $orderby, $order, $paged);
        } else {
            $this->display->display_main_category($main_category, $atts['posts_per_page'], $orderby, $order, $paged);
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

    private function display_sorting_dropdown($current_orderby, $current_order) {
        $options = [
            'date_DESC' => 'Latest',
            'date_ASC' => 'Oldest',
            'title_ASC' => 'Name (A-Z)',
            'title_DESC' => 'Name (Z-A)',
        ];

        echo '<div class="city-mapper-sorting">';
        echo '<label for="city-mapper-sort">Sort By: </label>';
        echo '<select id="city-mapper-sort" onchange="cityMapperSort(this.value)">';
        
        foreach ($options as $value => $label) {
            list($orderby, $order) = explode('_', $value);
            $selected = ($orderby === $current_orderby && $order === $current_order) ? ' selected' : '';
            echo "<option value=\"{$value}\"{$selected}>{$label}</option>";
        }
        
        echo '</select>';
        echo '</div>';

        // Add JavaScript for sorting
        ?>
        <script>
        function cityMapperSort(value) {
            const [orderby, order] = value.split('_');
            const url = new URL(window.location);
            url.searchParams.set('orderby', orderby);
            url.searchParams.set('order', order);
            console.log(url.searchParams); 
            url.searchParams.delete('paged'); // Remove the 'paged' parameter
            window.location.href = url.toString();
        }
        </script>
        <?php
    }
}