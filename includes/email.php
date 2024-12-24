<?php
// Function to send recommendation emails (basic implementation)
function awr_send_recommendation_email($user_id) {
    // Fetch recommendations
    $recommendations = awr_get_recommendations($user_id, 6);

    // Build email content
    if (!empty($recommendations)) {
        $email_content = "<h1>We have recommendations for you!</h1><ul>";
        foreach ($recommendations as $item) {
            $product = wc_get_product($item['id']);
            if ($product) {
                $email_content .= "<li><a href='" . get_permalink($product->get_id()) . "'>";
                $email_content .= $product->get_image('woocommerce_thumbnail') . ' ';
                $email_content .= $product->get_name() . ' - ' . $product->get_price_html();
                $email_content .= "</a></li>";
            }
        }
        $email_content .= "</ul>";

        // Send email (via PHPMailer or another method)
        $to = get_userdata($user_id)->user_email;
        $subject = "Personalized Product Recommendations for You";
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        wp_mail($to, $subject, $email_content, $headers);
    }
}

// Example: Send recommendations after a completed order
add_action('woocommerce_order_status_completed', 'awr_send_recommendation_email_on_order', 10, 1);
function awr_send_recommendation_email_on_order($order_id) {
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();
    if ($user_id) {
        awr_send_recommendation_email($user_id);
    }
}
add_action('woocommerce_order_status_completed', __NAMESPACE__ . '\awr_send_recommendation_email_on_order', 10, 1);
