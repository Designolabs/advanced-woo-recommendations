<?php
// Function to send recommendation emails with enhanced features and error handling
function awr_send_recommendation_email($user_id) {
    // Validate user ID
    if (!$user_id || !is_numeric($user_id)) {
        error_log('Invalid user ID provided to awr_send_recommendation_email()');
        return false;
    }

    // Get user data
    $user_data = get_userdata($user_id);
    if (!$user_data || !$user_data->user_email) {
        error_log('Could not find valid user email for ID: ' . $user_id);
        return false;
    }

    // Fetch recommendations with error handling
    try {
        $recommendations = awr_get_recommendations($user_id, 6);
    } catch (Exception $e) {
        error_log('Error fetching recommendations: ' . $e->getMessage());
        return false;
    }

    // Build email content if we have recommendations
    if (!empty($recommendations)) {
        $email_content = '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif;">';
        $email_content .= '<h1 style="color: #333; text-align: center;">Personalized Recommendations Just for You</h1>';
        $email_content .= '<div style="padding: 20px;">';

        foreach ($recommendations as $item) {
            $product = wc_get_product($item['id']);
            if ($product && $product->is_visible()) {
                $email_content .= '<div style="margin-bottom: 20px; border: 1px solid #eee; padding: 10px; border-radius: 5px;">';
                $email_content .= '<a href="' . esc_url(get_permalink($product->get_id())) . '" style="text-decoration: none; color: #333;">';
                $email_content .= '<div style="text-align: center;">' . $product->get_image('woocommerce_thumbnail') . '</div>';
                $email_content .= '<h2 style="margin: 10px 0; font-size: 16px;">' . esc_html($product->get_name()) . '</h2>';
                $email_content .= '<p style="color: #e03; margin: 5px 0;">' . wp_kses_post($product->get_price_html()) . '</p>';
                $email_content .= '</a>';
                $email_content .= '</div>';
            }
        }

        $email_content .= '</div>';
        $email_content .= '<div style="text-align: center; margin-top: 20px;">';
        $email_content .= '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Shop Now</a>';
        $email_content .= '</div>';
        $email_content .= '</div>';

        // Email settings
        $to = $user_data->user_email;
        $subject = sprintf(__('Personalized Product Recommendations for %s', 'advanced-woo-recommendations'), $user_data->display_name);
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Send email with error handling
        try {
            $sent = wp_mail($to, $subject, $email_content, $headers);
            if (!$sent) {
                throw new Exception('Email failed to send');
            }
            return true;
        } catch (Exception $e) {
            error_log('Error sending recommendation email: ' . $e->getMessage());
            return false;
        }
    }
    
    return false;
}

// Send recommendations after a completed order with enhanced error handling
add_action('woocommerce_order_status_completed', 'awr_send_recommendation_email_on_order', 10, 1);
function awr_send_recommendation_email_on_order($order_id) {
    if (!$order_id) {
        return;
    }

    try {
        $order = wc_get_order($order_id);
        if (!$order) {
            throw new Exception('Invalid order ID: ' . $order_id);
        }

        $user_id = $order->get_user_id();
        if ($user_id) {
            awr_send_recommendation_email($user_id);
        }
    } catch (Exception $e) {
        error_log('Error in awr_send_recommendation_email_on_order: ' . $e->getMessage());
    }
}
