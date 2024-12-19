function awr_enqueue_slick_carousel() {
    wp_enqueue_style('slick-carousel-css', AWR_PLUGIN_URL . 'assets/css/slick.css');
    wp_enqueue_script('slick-carousel-js', AWR_PLUGIN_URL . 'assets/js/slick.min.js', array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'awr_enqueue_slick_carousel');

jQuery(document).ready(function ($) {
    var layoutStyle = '<?php echo esc_js(get_option("awr_layout_style", "grid")); ?>';

    if (layoutStyle === 'carousel') {
        $('.awr-recommendations-grid').slick({
            infinite: true,
            slidesToShow: <?php echo esc_js(get_option("awr_product_columns", 4)); ?>,
            slidesToScroll: 1,
            arrows: true,
            dots: true,
        });
    }
});

jQuery(document).ready(function ($) {
    var layoutStyle = '<?php echo esc_js(get_option("awr_layout_style", "grid")); ?>';

    if (layoutStyle === 'carousel') {
        $('.awr-recommendations-grid').slick({
            infinite: true,
            slidesToShow: <?php echo esc_js(get_option("awr_product_columns", 4)); ?>,
            slidesToScroll: 1,
            arrows: true,
            dots: true,
            autoplay: true,
            autoplaySpeed: 3000,
        });
    }
});
