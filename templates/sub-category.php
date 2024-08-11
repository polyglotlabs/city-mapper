<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        $main_category = get_query_var('main_category');
        $sub_category = get_query_var('sub_category');
        $city_mapper = new City_Mapper_Display();
        $city_mapper->display_sub_category($main_category, $sub_category, 10, 'date', 'DESC');
        ?>
    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>