<?php
// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Render the settings page
function awr_render_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'advanced-woo-recommendations'));
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <?php settings_errors(); ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('awr_settings_group');
            do_settings_sections('awr-settings-page');
            submit_button(__('Save Settings', 'advanced-woo-recommendations'));
            ?>
        </form>
    </div>
    <?php
}

// Register and define plugin settings
function awr_register_settings() {
    register_setting(
        'awr_settings_group',
        'awr_recombee_api_key',
        [
            'sanitize_callback' => 'awr_validate_recombee_api_key',
            'default' => ''
        ]
    );
    register_setting(
        'awr_settings_group',
        'awr_gemini_api_key',
        [
            'sanitize_callback' => 'awr_validate_gemini_api_key',
            'default' => ''
        ]
    );


    // Add settings section
    add_settings_section(
        'awr_main_settings_section',
        __('Main Settings', 'advanced-woo-recommendations'),
        'awr_main_settings_section_callback',
        'awr-settings-page'
    );

    // Recombee API key setting
    add_settings_field(
        'awr_recombee_api_key',
        __('Recombee API Key', 'advanced-woo-recommendations'),
        'awr_recombee_api_key_callback',
        'awr-settings-page',
        'awr_main_settings_section'
    );
    // Gemini API key setting
    add_settings_field(
        'awr_gemini_api_key',
        __('Gemini API Key', 'advanced-woo-recommendations'),
        'awr_gemini_api_key_callback',
        'awr-settings-page',
        'awr_main_settings_section'
    );
}
add_action('admin_init', 'awr_register_settings');

// Callback for section description
function awr_main_settings_section_callback() {
    echo __('Enter your API keys and manage plugin settings.', 'advanced-woo-recommendations');
}

// Callback for Recombee API key field
function awr_recombee_api_key_callback() {
    $api_key = get_option('awr_recombee_api_key');
    ?>
    <input
        type="text"
        name="awr_recombee_api_key"
        id="awr_recombee_api_key"
        value="<?php echo esc_attr($api_key); ?>"
        class="regular-text"
    />
    <p class="description">
        <?php _e('Enter your Recombee API key. You can find this in your Recombee dashboard.', 'advanced-woo-recommendations'); ?>
    </p>
    <?php
}
// Callback for Gemini API key field
function awr_gemini_api_key_callback() {
    $api_key = get_option('awr_gemini_api_key');
    ?>
    <input
        type="text"
        name="awr_gemini_api_key"
        id="awr_gemini_api_key"
        value="<?php echo esc_attr($api_key); ?>"
        class="regular-text"
    />
    <p class="description">
        <?php _e('Enter your Gemini API key. You can find this in your Google AI Studio.', 'advanced-woo-recommendations'); ?>
    </p>
    <?php
}

// Validate Recombee API key before saving
function awr_validate_recombee_api_key($input) {
    $api_key = sanitize_text_field($input);

    if (empty($api_key)) {
        add_settings_error(
            'awr_recombee_api_key',
            'empty_api_key',
            __('Recombee API key cannot be empty.', 'advanced-woo-recommendations'),
            'error'
        );
        return get_option('awr_recombee_api_key'); // Return existing value
    }

    // Basic format validation
    if (!preg_match('/^[a-zA-Z0-9\-\_]{20,}$/', $api_key)) {
        add_settings_error(
            'awr_recombee_api_key',
            'invalid_api_key',
            __('Invalid Recombee API key format. Please enter a valid Recombee API key.', 'advanced-woo-recommendations'),
            'error'
        );
        return get_option('awr_recombee_api_key');
    }

    // Test API connection
    $test_result = awr_test_recombee_api_connection($api_key);
    if (is_wp_error($test_result)) {
        add_settings_error(
            'awr_recombee_api_key',
            'api_connection_failed',
            sprintf(
                __('Failed to connect to Recombee API: %s', 'advanced-woo-recommendations'),
                $test_result->get_error_message()
            ),
            'error'
        );
        return get_option('awr_recombee_api_key');
    }

    return $api_key;
}
// Validate Gemini API key before saving
function awr_validate_gemini_api_key($input) {
    $api_key = sanitize_text_field($input);

    if (empty($api_key)) {
        add_settings_error(
            'awr_gemini_api_key',
            'empty_api_key',
            __('Gemini API key cannot be empty.', 'advanced-woo-recommendations'),
            'error'
        );
        return get_option('awr_gemini_api_key'); // Return existing value
    }

    // Basic format validation
    if (!preg_match('/^[a-zA-Z0-9\-\_]{20,}$/', $api_key)) {
         add_settings_error(
            'awr_gemini_api_key',
            'invalid_api_key',
            __('Invalid Gemini API key format. Please enter a valid Gemini API key.', 'advanced-woo-recommendations'),
            'error'
        );
        return get_option('awr_gemini_api_key');
    }


    // Test API connection
    $test_result = awr_test_gemini_api_connection($api_key);
    if (is_wp_error($test_result)) {
        add_settings_error(
            'awr_gemini_api_key',
            'api_connection_failed',
            sprintf(
                __('Failed to connect to Gemini API: %s', 'advanced-woo-recommendations'),
                $test_result->get_error_message()
            ),
            'error'
        );
        return get_option('awr_gemini_api_key');
    }

    return $api_key;
}

// Test Recombee API connection
function awr_test_recombee_api_connection($api_key) {
    $url = 'https://api.recombee.com/test-endpoint'; // Replace with actual test endpoint

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
        ],
    ]);

    if (is_wp_error($response)) {
        return $response; // Return the WP_Error object
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error(
            'api_connection_failed',
            __('Failed to connect to Recombee API. Please check your API key and try again.', 'advanced-woo-recommendations')
        );
    }

    return true; // Connection successful
}
// Test Gemini API connection
function awr_test_gemini_api_connection($api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent'; // Replace with actual test endpoint

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
             'x-goog-api-key' => $api_key,
        ],
         'body' => json_encode([
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => 'test'
                        ]
                    ]
                ]
            ]
        ])
    ]);


    if (is_wp_error($response)) {
        return $response; // Return the WP_Error object
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error(
            'api_connection_failed',
            __('Failed to connect to Gemini API. Please check your API key and try again.', 'advanced-woo-recommendations')
        );
    }

    return true; // Connection successful
}