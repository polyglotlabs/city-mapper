<?php

class City_Mapper_Display {
    public function handle_custom_urls($template) {
        $city_mapper_type = get_query_var('city_mapper_type');
        $main_category = get_query_var('main_category');
        $sub_category = get_query_var('sub_category');

        if ($city_mapper_type === 'main') {
            return $this->get_template('main-category.php');
        } elseif ($city_mapper_type === 'sub') {
            return $this->get_template('sub-category.php');
        }

        return $template;
    }

    private function get_template($template_name) {
        $template_path = CITY_MAPPER_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($template_path)) {
            return $template_path;
        }
        return get_page_template(); // Fallback to default page template
    }

    public function display_main_category($category, $posts_per_page, $orderby, $order) {
        $main_category = get_term_by('slug', $category, 'main_category');
        $output = '';

        if ($main_category) {
            $output .= '<h2>' . esc_html($main_category->name) . '</h2>';

            $sub_categories = get_terms(array(
                'taxonomy' => 'sub_category',
                'hide_empty' => false,
                'meta_query' => array(
                    array(
                        'key' => 'main_category',
                        'value' => $main_category->term_id,
                        'compare' => '='
                    )
                )
            ));

            if (!empty($sub_categories) && !is_wp_error($sub_categories)) {
                $output .= '<div class="city-mapper-sub-categories">';
                foreach ($sub_categories as $sub_category) {
                    $output .= sprintf(
                        '<a href="%s" class="city-mapper-sub-category">%s</a>',
                        esc_url(get_term_link($sub_category)),
                        esc_html($sub_category->name)
                    );
                }
                $output .= '</div>';
            }

            $output .= $this->display_posts($category, '', $posts_per_page, $orderby, $order);
        }

        echo $output;
    }

    public function display_sub_category($category, $sub_category, $posts_per_page, $orderby, $order) {
        $main_category = get_term_by('slug', $category, 'main_category');
        $sub_category_term = get_term_by('slug', $sub_category, 'sub_category');
        $output = '';

        if ($main_category && $sub_category_term) {
            $output .= '<h2>' . esc_html($main_category->name) . ' - ' . esc_html($sub_category_term->name) . '</h2>';

            $output .= $this->display_posts($category, $sub_category, $posts_per_page, $orderby, $order);
        }

        echo $output;
    }

    private function display_posts($category, $sub_category, $posts_per_page, $orderby, $order) {
        $args = array(
            'post_type' => 'city_location',
            'posts_per_page' => $posts_per_page,
            'orderby' => $orderby,
            'order' => $order,
            'tax_query' => array(
                'relation' => 'AND',
                array(
                    'taxonomy' => 'main_category',
                    'field' => 'slug',
                    'terms' => $category,
                ),
            ),
        );

        if (!empty($sub_category)) {
            $args['tax_query'][] = array(
                'taxonomy' => 'sub_category',
                'field' => 'slug',
                'terms' => $sub_category,
            );
        }

        $query = new WP_Query($args);

        $output = '';
        if ($query->have_posts()) {
            $output .= '<div class="city-mapper-posts">';
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="city-mapper-post">';
                $output .= '<h3><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></h3>';
                if (has_post_thumbnail()) {
                    $output .= '<div class="city-mapper-thumbnail">';
                    $output .= get_the_post_thumbnail(null, 'thumbnail');
                    $output .= '</div>';
                }
                $output .= '<div class="city-mapper-excerpt">' . get_the_excerpt() . '</div>';
                $output .= '</div>';
            }
            $output .= '</div>';

            // Pagination
            $big = 999999999; // need an unlikely integer
            $output .= '<div class="city-mapper-pagination">';
            $output .= paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $query->max_num_pages
            ));
            $output .= '</div>';

            wp_reset_postdata();
        } else {
            $output .= '<p>No posts found.</p>';
        }

        return $output;
    }
}