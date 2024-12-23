// Enqueue Slick Carousel assets
function awr_enqueue_slick_carousel() {
    wp_enqueue_style('slick-carousel-css', AWR_PLUGIN_URL + 'assets/css/slick.css', array(), '1.8.1');
    wp_enqueue_script('slick-carousel-js', AWR_PLUGIN_URL + 'assets/js/slick.min.js', array('jquery'), '1.8.1', true);
}
add_action('wp_enqueue_scripts', 'awr_enqueue_slick_carousel');

// Initialize Slick Carousel
jQuery(document).ready(function ($) {
    const layoutStyle = '<?php echo esc_js(get_option("awr_layout_style", "grid")); ?>';
    const productColumns = parseInt('<?php echo esc_js(get_option("awr_product_columns", 4)); ?>', 10);
    
    if (layoutStyle === 'carousel') {
        const $recommendationsGrid = $('.awr-recommendations-grid');
        
        $recommendationsGrid.slick({
            infinite: true,
            slidesToShow: productColumns,
            slidesToScroll: 1,
            arrows: true,
            dots: true,
            autoplay: true,
            autoplaySpeed: 3000,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        slidesToShow: Math.min(3, productColumns),
                    }
                },
                {
                    breakpoint: 768,
                    settings: {
                        slidesToShow: Math.min(2, productColumns),
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        slidesToShow: 1,
                        arrows: false
                    }
                }
            ],
            accessibility: true,
            adaptiveHeight: true,
            pauseOnHover: true,
            swipeToSlide: true
        });

        // Reinitialize carousel on window resize
        let resizeTimer;
        $(window).on('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                $recommendationsGrid.slick('refresh');
            }, 250);
        });
    }
});
