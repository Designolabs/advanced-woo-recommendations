<?php
namespace AdvancedWooRecommendations;

use WP_Error;
use WP_REST_Request;

// Function to get product recommendations from Recombee
function awr_get_recombee_recommendations(string $user_id, int $count = 12): array {
    $api_key = get_option('awr_recombee_api_key');
    if (!$api_key) return [];

    $url = "https://rapi.recombee.com/db/YOUR_DB_ID/recommend/items/$user_id?count=$count";
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
    return json_decode($body, true);
}

// Function to get product recommendations from Gemini
function awr_get_gemini_recommendations(string $user_id, int $count = 12): array {
    $api_key = get_option('awr_gemini_api_key');
    if (!$api_key) return [];

    // Placeholder for Gemini API call - Replace with actual Gemini API logic
    $url = "https://generativeai.googleapis.com/v1beta/models/gemini-pro:generateContent?key=" . $api_key;
    $args = [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => "Recommend $count products for user $user_id based on their purchase history.",
                        ],
                    ],
                ],
            ],
        ]),
        'method' => 'POST',
        'timeout' => 15,
    ];

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('Gemini API Error: ' . $response->get_error_message());
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $decoded_body = json_decode($body, true);

    // Placeholder for parsing Gemini response - Replace with actual parsing logic
    if (isset($decoded_body['candidates'][0]['content']['parts'][0]['text'])) {
        $recommendations_text = $decoded_body['candidates'][0]['content']['parts'][0]['text'];
        // Example: Assuming Gemini returns a comma-separated list of product IDs
        $product_ids = explode(',', $recommendations_text);
        $product_ids = array_map('trim', $product_ids);
        return array_map(function($id) { return ['product_id' => $id]; }, $product_ids);
    }

    return [];
}


add_action('rest_api_init', function () {
    register_rest_route('awr/v1', '/recommendations', [
        'methods' => 'GET',
        'callback' => 'awr_get_recommendations_api',
        'permission_callback' => '__return_true', // Adjust permissions as needed
    ]);
});

function awr_get_recommendations_api(WP_REST_Request $request) {
    $user_id = isset($request['user_id']) ? sanitize_text_field($request['user_id']) : '';
    $source = isset($request['source']) ? sanitize_text_field($request['source']) : 'recombee'; // Default to Recombee

    if ($source === 'gemini') {
        $recommendations = awr_get_gemini_recommendations($user_id);
    } else {
        $recommendations = awr_get_recombee_recommendations($user_id);
    }


    if (empty($recommendations)) {
        return new WP_Error('no_recommendations', 'No recommendations found.', ['status' => 404]);
    }

    return rest_ensure_response($recommendations);
}