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
            'posts_per_page' => 21,
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
        echo '<div class="city-mapper-container--top-bar">';
        // Display Results Text
        $this->display_results_text($main_category, $sub_category);

        // Display sorting dropdown
        $this->display_sorting_dropdown($orderby, $order);

        // Display map View
        $this->display_map_view($main_category);
        echo '</div>';
        // Display content
   

        if ($sub_category) {
            $this->display->display_sub_category($main_category, $sub_category, $atts['posts_per_page'], $orderby, $order, $paged);
        } else {
            $this->display->display_main_category($main_category, $atts['posts_per_page'], $orderby, $order, $paged);
        }

        echo '</div>';

        return ob_get_clean();
    }

    public function display_sub_categories($main_category, $current_sub_category = '') {
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
            $first_sub_category = true;
            $active_set = false;
            foreach ($sub_categories as $sub_category) {

                if($current_sub_category == "" && !$active_set) {
                    $active_class = " active"; 
                    $active_set = true; 
                } else {
                    $active_class = ""; 
                }
                
                $url = home_url("/{$main_category}/{$sub_category->slug}/");
                printf(
                    '<a href="%s" class="city-mapper-tab%s">%s</a>',
                    esc_url($url),
                    esc_attr($active_class),
                    esc_html($sub_category->name)
                );
                $active_class = ""; 
            }

            echo '</div>';

        }
    }

    public function display_results_text($main_category, $current_sub_category = '') {
    
            // To show text
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $first_cat = $this->display->get_first_sub_category($main_category)->slug;
            $args = array(
              'post_type' => 'city_location', 
              'showposts' => '21',
              'paged' => $paged,  
            );
            if($current_sub_category == ''){
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'sub_category',
                        'field' => 'slug',
                        'terms' => $first_cat,
                    ),
                );
            }else{
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'sub_category',
                        'field' => 'slug',
                        'terms' => $current_sub_category,
                    ),
                );
            }
            $wp_query = new WP_Query($args);
            if($paged == 1){
                $page_initial = 1;
                $page_last = $wp_query->post_count;
            }else{
                $page_initial = 1 + ($wp_query->post_count * ($paged - 1));
                $page_last = ($wp_query->post_count * $paged);
            }
            echo "<div class='category-showing'>Showing {$page_initial}-{$page_last} of {$wp_query->found_posts} results</div>";
            // End
    }

    private function display_sorting_dropdown($orderby, $order) {
        $options = [
            'date_DESC' => 'Latest',
            'date_ASC' => 'Oldest',
            'title_ASC' => 'Name (A-Z)',
            'title_DESC' => 'Name (Z-A)',
        ];

        echo '<div class="city-mapper-sorting">';
        //echo '<label for="city-mapper-sort">Sort By: </label>';
        echo '<label class="select"><select id="city-mapper-sort" onchange="cityMapperSort(this.value)">';
        
        foreach ($options as $value => $label) {
            list($option_orderby, $option_order) = explode('_', $value);
            $selected = ($option_orderby === $orderby && $option_order === $order) ? ' selected' : '';
            echo "<option value=\"{$value}\"{$selected}>Sort By: {$label}</option>";
        }
        
        echo '</select></label>';
        echo '</div>';

        // Add JavaScript for sorting
        ?>
        <script>
        function cityMapperSort(value) {
            const [orderby, order] = value.split('_');
            const url = new URL(window.location);
            url.searchParams.set('orderby', orderby);
            url.searchParams.set('order', order);
            url.searchParams.delete('paged'); // Remove the 'paged' parameter
            window.location.href = url.toString();
        }
        </script>
        <?php
    }

    public function display_map_view($main_category) {
        $map_link = "";
        $map_icon = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 384 512'><!--!Font Awesome Pro 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2024 Fonticons, Inc.--><path d='M368 192c0-97.2-78.8-176-176-176S16 94.8 16 192c0 18.7 6.4 42.5 17.8 69.6c11.3 26.9 27.1 55.8 44.7 84.3c35.2 57 76.8 111.4 102.3 143.2c5.9 7.3 16.6 7.3 22.4 0c25.5-31.8 67.1-86.2 102.3-143.2c17.6-28.5 33.4-57.4 44.7-84.3C361.6 234.5 368 210.7 368 192zm16 0c0 87.4-117 243-168.3 307.2c-12.3 15.3-35.1 15.3-47.4 0C117 435 0 279.4 0 192C0 86 86 0 192 0S384 86 384 192zM192 112a80 80 0 1 1 0 160 80 80 0 1 1 0-160zm64 80a64 64 0 1 0 -128 0 64 64 0 1 0 128 0z'/></svg>";
        if($main_category == "gather"){
            $map_link = get_field('gather_url', 'option');
        }elseif($main_category == "eat"){
            $map_link = get_field('eat_url', 'option');
        }elseif($main_category == "explore"){
            $map_link = get_field('explore_url', 'option');
        }elseif($main_category == "stay"){
            $map_link = get_field('stay_url', 'option');
        }else{}
        
        if($map_link == ""){}else{
            echo "<a class='city-mapper-map' href='{$map_link}' target='_blank'>{$map_icon} MAP VIEW</a>";
        }
    }
}

