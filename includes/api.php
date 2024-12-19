<?php
// Function to get product recommendations from Recombee
function awr_get_recommendations(string $user_id, int $count = 12): array {
    $api_key = get_option('awr_recombee_api_key');
    if (!$api_key) {
        return [];
    }

    $url = sprintf(
        'https://rapi.recombee.com/db/YOUR_DB_ID/recommend/items/%s?count=%d',
        urlencode($user_id),
        $count
    );

    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
    ];

    $response = wp_remote_get($url, $args);
    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true) ?? [];
}

add_action('rest_api_init', function () {
    register_rest_route('awr/v1', '/recommendations', [
        'methods' => 'GET',
        'callback' => 'awr_get_recommendations_api',
        'permission_callback' => '__return_true', // Adjust permissions as needed
    ]);
});

function awr_get_recommendations_api(WP_REST_Request $request) {
    $user_id = sanitize_text_field($request->get_param('user_id') ?? '');
    $recommendations = awr_get_recommendations($user_id);

    if (empty($recommendations)) {
        return new WP_Error('no_recommendations', 'No recommendations found.', ['status' => 404]);
    }

    return rest_ensure_response($recommendations);
}