<?php
// Get current user ID (WooCommerce user or guest)
$user_id = get_current_user_id();
if (!$user_id) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = 'guest_' . session_id(); // For guests, use a session-based ID
}

// Fetch recommendations from Recombee API
$cache_key = 'recommendations_' . $user_id;
$recommendations = wp_cache_get($cache_key);

if ($recommendations === false) {
    $recommendations = awr_get_recommendations($user_id);
    wp_cache_set($cache_key, $recommendations, '', 3600); // Cache for 1 hour
}

if (!empty($recommendations)) : ?>
    <div class="awr-recommendations-grid">
        <?php foreach ($recommendations as $item) :
            // Get WooCommerce product by ID
            $product = wc_get_product($item['id']);
            if ($product) : ?>
                <div class="awr-product">
                    <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>">
                        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
                        <h2><?php echo esc_html($product->get_name()); ?></h2>
                        <p class="awr-price"><?php echo wp_kses_post($product->get_price_html()); ?></p>
                    </a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <p>No recommendations available at this time.</p>
<?php endif; ?>

<div id="awr-recommendations-root">
    <!-- React app will be rendered here -->
</div>