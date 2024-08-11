<?php get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <?php
        $main_category = get_query_var('main_category');
        $city_mapper = new City_Mapper_Display();
        $city_mapper->display_main_category($main_category, 10, 'date', 'DESC');
        ?>
    </main>
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>