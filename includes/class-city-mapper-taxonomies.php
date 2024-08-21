<?php

class City_Mapper_Taxonomies {
    public function register_taxonomies() {
        // Sub Category Taxonomy
        $this->register_taxonomy('sub_category', 'Sub Category', 'Sub Categories', [ ]);
    }

    private function register_taxonomy($taxonomy, $singular, $plural, $additional_args = []) {
        $args = array_merge([
            'has_archive'       => false,
            'hierarchical'      => true,
            'labels'            => $this->get_taxonomy_labels($singular, $plural),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => false,
        ], $additional_args);

        register_taxonomy($taxonomy, ['city_location'], $args);
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
        if ($taxonomy === 'sub_category') {
            $main_category_id = get_term_meta($term->term_id, 'main_category', true);
            $main_category = get_post($main_category_id);
            if ($main_category) {
                return home_url($main_category->post_name . '/' . $term->slug . '/');
            }
        }
        return $termlink;
    }

    public function sub_category_meta_box($post, $box) {
        $tax_name = esc_attr($box['args']['taxonomy'] ?? 'sub_category');
        ?>
        <div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
            <div id="<?php echo $tax_name; ?>-all" class="tabs-panel">
                <ul id="<?php echo $tax_name; ?>checklist" class="categorychecklist form-no-clear">
                    <?php 
                    $terms = get_terms(['taxonomy' => $tax_name, 'hide_empty' => false]);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        foreach ($terms as $term) {
                            $this->render_sub_category_checkbox($term, $tax_name, $post->ID);
                        }
                    } else {
                        echo '<li>No sub-categories found.</li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php
    }

    private function render_sub_category_checkbox($term, $tax_name, $post_id) {
        $main_category_id = get_term_meta($term->term_id, 'main_category', true);
        $main_category_name = 'Uncategorized';
        
        if ($main_category_id) {
            $main_category = get_post($main_category_id);
            if ($main_category && $main_category->post_type == 'page') {
                $main_category_name = $main_category->post_title;
            }
        }
        
        ?>
        <li id="<?php echo $tax_name; ?>-<?php echo $term->term_id; ?>">
            <label class="selectit">
                <input type="checkbox" name="tax_input[<?php echo $tax_name; ?>][]" 
                       id="in-<?php echo $tax_name; ?>-<?php echo $term->term_id; ?>" 
                       value="<?php echo $term->term_id; ?>"
                       <?php checked(has_term($term->term_id, $tax_name, $post_id)); ?>>
                <?php echo esc_html($term->name); ?>
                <?php if ($main_category_name !== 'Uncategorized'): ?>
                    <span class="main-category-name">(<?php echo esc_html($main_category_name); ?>)</span>
                <?php endif; ?>
            </label>
        </li>
        <?php
    }

    public function save_sub_category_main_category($term_id) {
        if (isset($_POST['main_category'])) {
            update_term_meta($term_id, 'main_category', absint($_POST['main_category']));
        }
    }

    public function add_main_category_field($taxonomy) {
        ?>
        <div class="form-field term-main-category-wrap">
            <label for="main-category"><?php _e('Main Category Page', 'city-mapper'); ?></label>
            <select name="main_category" id="main-category">
                <option value=""><?php _e('Select Main Category Page', 'city-mapper'); ?></option>
                <?php
                $pages = get_pages();
                foreach ($pages as $page) {
                    echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
                }
                ?>
            </select>
            <p><?php _e('Select the Page that represents the Main Category. Each Main Category must be associated with its own unique page.', 'city-mapper'); ?></p>
        </div>
        <?php
    }

    public function edit_main_category_field($term, $taxonomy) {
        $main_category_id = get_term_meta($term->term_id, 'main_category', true);
        ?>
        <tr class="form-field term-main-category-wrap">
            <th scope="row"><label for="main-category"><?php _e('Main Category Page', 'city-mapper'); ?></label></th>
            <td>
                <select name="main_category" id="main-category">
                    <option value=""><?php _e('Select Main Category Page', 'city-mapper'); ?></option>
                    <?php
                    $pages = get_pages();
                    foreach ($pages as $page) {
                        echo '<option value="' . esc_attr($page->ID) . '"' . selected($main_category_id, $page->ID, false) . '>' . esc_html($page->post_title) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select the Page that represents the Main Category. Each Main Category must be associated with its own unique page.', 'city-mapper'); ?></p>
            </td>
        </tr>
        <?php
    }
}