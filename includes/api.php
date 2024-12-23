<?php
namespace AdvancedWooRecommendations;

use WP_Error;
use WP_REST_Request;

// Function to get product recommendations from Recombee
function awr_get_recommendations(string $user_id, int $count = 12): array {
    $api_key = get_option('awr_recombee_api_key');
    if (!$api_key) {
        return [];
    }

    $db_id = get_option('awr_recombee_db_id');
    if (!$db_id) {
        return [];
    }

    $url = sprintf(
        'https://rapi.recombee.com/db/%s/recommend/items/%s?count=%d',
        $db_id,
        $user_id,
        $count
    );

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 15,
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        error_log('Recombee API Error: ' . $response->get_error_message());
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Recombee API Error: Invalid JSON response: ' . $body);
        return [];
    }

    return is_array($data) ? $data : [];
}

add_action('rest_api_init', function () {
    register_rest_route('awr/v1', '/recommendations', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\awr_get_recommendations_api',
        'permission_callback' => '__return_true',
    ]);
});

function awr_get_recommendations_api(WP_REST_Request $request) {
    $user_id = isset($request['user_id']) ? sanitize_text_field($request['user_id']) : '';

    if (empty($user_id)) {
         return new WP_Error('missing_user_id', 'User ID is required.', ['status' => 400]);
    }

    $recommendations = awr_get_recommendations($user_id);

    if (empty($recommendations)) {
        return new WP_Error('no_recommendations', 'No recommendations found.', ['status' => 404]);
    }

    return rest_ensure_response($recommendations);
}
