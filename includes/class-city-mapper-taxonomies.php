<?php

class City_Mapper_Taxonomies {
    public function register_taxonomies() {
        // Main Category Taxonomy
        $this->register_taxonomy('main_category', 'Main Category', 'Main Categories');

        // Sub Category Taxonomy
        $this->register_taxonomy('sub_category', 'Sub Category', 'Sub Categories', [
            'meta_box_cb' => [$this, 'sub_category_meta_box']
        ]);
    }

    private function register_taxonomy($taxonomy, $singular, $plural, $additional_args = []) {
        $args = array_merge([
            'hierarchical'      => false,
            'labels'            => $this->get_taxonomy_labels($singular, $plural),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => strtolower(str_replace('_', '-', $taxonomy))]
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

    public function sub_category_meta_box($post, $box) {
        $tax_name = esc_attr($box['args']['taxonomy'] ?? 'sub_category');
        ?>
        <div id="taxonomy-<?php echo $tax_name; ?>" class="categorydiv">
            <div id="<?php echo $tax_name; ?>-all" class="tabs-panel">
                <ul id="<?php echo $tax_name; ?>checklist" class="categorychecklist form-no-clear">
                    <?php 
                    $terms = get_terms(['taxonomy' => $tax_name, 'hide_empty' => false]);
                    foreach ($terms as $term) {
                        $this->render_sub_category_checkbox($term, $tax_name, $post->ID);
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php
    }

    private function render_sub_category_checkbox($term, $tax_name, $post_id) {
        $main_category_id = get_term_meta($term->term_id, 'main_category', true);
        $main_category = get_term($main_category_id, 'main_category');
        $main_category_name = $main_category ? $main_category->name : 'Uncategorized';
        ?>
        <li id="<?php echo $tax_name; ?>-<?php echo $term->term_id; ?>">
            <label class="selectit">
                <input type="checkbox" name="tax_input[<?php echo $tax_name; ?>][]" 
                       id="in-<?php echo $tax_name; ?>-<?php echo $term->term_id; ?>" 
                       value="<?php echo $term->term_id; ?>"
                       <?php checked(has_term($term->term_id, $tax_name, $post_id)); ?>>
                <?php echo esc_html($term->name) . ' (' . esc_html($main_category_name) . ')'; ?>
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
            <label for="main-category"><?php _e('Main Category', 'city-mapper'); ?></label>
            <select name="main_category" id="main-category">
                <option value=""><?php _e('Select Main Category', 'city-mapper'); ?></option>
                <?php
                $main_categories = get_terms(array('taxonomy' => 'main_category', 'hide_empty' => false));
                foreach ($main_categories as $main_category) {
                    echo '<option value="' . esc_attr($main_category->term_id) . '">' . esc_html($main_category->name) . '</option>';
                }
                ?>
            </select>
            <p><?php _e('Select the Main Category this Sub Category belongs to.', 'city-mapper'); ?></p>
        </div>
        <?php
    }

    public function edit_main_category_field($term, $taxonomy) {
        $main_category_id = get_term_meta($term->term_id, 'main_category', true);
        ?>
        <tr class="form-field term-main-category-wrap">
            <th scope="row"><label for="main-category"><?php _e('Main Category', 'city-mapper'); ?></label></th>
            <td>
                <select name="main_category" id="main-category">
                    <option value=""><?php _e('Select Main Category', 'city-mapper'); ?></option>
                    <?php
                    $main_categories = get_terms(array('taxonomy' => 'main_category', 'hide_empty' => false));
                    foreach ($main_categories as $main_category) {
                        echo '<option value="' . esc_attr($main_category->term_id) . '"' . selected($main_category_id, $main_category->term_id, false) . '>' . esc_html($main_category->name) . '</option>';
                    }
                    ?>
                </select>
                <p class="description"><?php _e('Select the Main Category this Sub Category belongs to.', 'city-mapper'); ?></p>
            </td>
        </tr>
        <?php
    }
}