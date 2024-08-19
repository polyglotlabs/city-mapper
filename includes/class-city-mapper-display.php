<?php

class City_Mapper_Display {
   

    private function get_template($template_name) {
        $template_path = CITY_MAPPER_PLUGIN_DIR . 'templates/' . $template_name;
        if (file_exists($template_path)) {
            return $template_path;
        }
        return get_single_template(); // Fallback to default single post template
    }

    public function display_main_category($category, $posts_per_page, $orderby, $order) {
        $main_category = get_query_var('main_category') ?: $category;
        $sub_category = get_query_var('sub_category');
        $output = '';

        if ($main_category) {
            $output .= '<h2>' . esc_html($main_category) . '</h2>';
            $output .= $this->display_sub_categories_head($main_category);

            if ($sub_category) {
                $sub_category_term = get_term_by('slug', $sub_category, 'sub_category');
                if ($sub_category_term) {
                    $output .= '<h3>' . esc_html($sub_category_term->name) . '</h3>';
                }
                $output .= $this->display_posts($main_category, $sub_category, $posts_per_page, $orderby, $order);
            } else {
                $output .= $this->display_posts($main_category, '', $posts_per_page, $orderby, $order);
            }
        }

        echo $output;
    }

    public function display_sub_category($category, $sub_category, $posts_per_page, $orderby, $order) {
        $main_category = get_query_var('main_category') ?: $category;
        $sub_category_term = get_term_by('slug', $sub_category, 'sub_category');
        $output = '';
        
        if ($main_category && $sub_category_term) {
            $output .= '<h2>' . esc_html($main_category) . ' - ' . esc_html($sub_category_term->name) . '</h2>';
            $output .= $this->display_sub_categories_head($main_category);

            $output .= $this->display_posts($main_category, $sub_category, $posts_per_page, $orderby, $order);
        }

        echo $output;
    }

    private function display_posts($category, $sub_category, $posts_per_page, $orderby, $order) {
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;

        $args = array(
            'post_type' => 'city_location',
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
        );

        if (!empty($sub_category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'sub_category',
                    'field' => 'slug',
                    'terms' => $sub_category,
                ),
            );
        }

        // Add ordering
        if ($orderby === 'date') {
            $args['orderby'] = array(
                'date' => $order,
                'ID' => 'ASC'
            );
        } else {
            $args['orderby'] = array(
                $orderby => $order,
                'date' => $order,
                'ID' => 'ASC'
            );
        }

        $query = new WP_Query($args);

        $output = '';
        if ($query->have_posts()) {

            $output .= '<div class="city-mapper-posts">';
            while ($query->have_posts()) {
                $query->the_post();
                $output .= '<div class="city-mapper-post">';
                $output .= '<a href="' . esc_url(get_permalink()) . '">';
                if (has_post_thumbnail()) {
                    $output .= '<div class="city-mapper-thumbnail">';
                    $output .= get_the_post_thumbnail(null, 'full');
                    $output .= '</div>';
                }else{
                    $output .= '<div class="city-mapper-bg"></div>';
                }
                if (get_the_terms(get_the_ID(), 'sub_category')) {
                    $output .= '<div class="city-mapper--sub-categories">';
                    foreach (get_the_terms(get_the_ID(), 'sub_category') as $cat) {
                        $output .= '' . $cat->name . '<span class="comma">,</span>';
                    }
                    $output .= '</div>';
                }
                $output .= '<h3>' . get_the_title() . '</h3>';
                //$output .= '<div class="city-mapper-excerpt">' . get_the_excerpt() . '</div>';
                $output .= '</a>';
                $output .= '</div>';
            }
            $output .= '</div>';

            // Pagination
            $big = 999999999; // need an unlikely integer
            $output .= '<div class="city-mapper-pagination">';
            $output .= paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $query->max_num_pages,
                'next_text' => (''),
                'prev_text' => (''),
            ));
            $output .= '</div>';

            wp_reset_postdata();
        } else {
            $output .= '<p>No posts found.</p>';
        }

        return $output;
    }

    public function display_sub_categories_head($main_category) {
        $sub_categories = get_terms([
            'taxonomy' => 'sub_category',
            'hide_empty' => false,
        ]);
        $current_sub_category = get_query_var('sub_category');
        $output = '';
        if (!empty($sub_categories) && !is_wp_error($sub_categories)) {
            $output .= '<ul class="sub-categories-head">';
            foreach ($sub_categories as $sub_category) {
                $main_cat_terms = get_term_meta($sub_category->term_id, 'main_category', true);
                $main_cat_term = get_term($main_cat_terms);
                if (!is_wp_error($main_cat_term) && $main_cat_term && $main_cat_term->slug == $main_category) {
                    $url = home_url("/{$main_category}/{$sub_category->slug}/");
                    $active_class = ($current_sub_category == $sub_category->slug) ? ' class="active"' : '';
                    $output .= '<li' . $active_class . '><a href="' . esc_url($url) . '">' . esc_html($sub_category->name) . '</a></li>';
                }
            }
            $output .= '</ul>';
        }
        return $output;
    }
}