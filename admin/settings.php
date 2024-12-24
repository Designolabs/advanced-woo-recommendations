<?php
namespace AdvancedWooRecommendations;
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
    // Register settings with validation callbacks
    awr_register_api_setting('awr_recombee_api_key', 'awr_validate_recombee_api_key');
    awr_register_api_setting('awr_gemini_api_key', 'awr_validate_gemini_api_key');

    // Add settings section
    add_settings_section(
        'awr_main_settings_section',
        __('Main Settings', 'advanced-woo-recommendations'),
        'awr_main_settings_section_callback',
        'awr-settings-page'
    );

    // Add individual settings fields
    awr_add_settings_field('awr_recombee_api_key', __('Recombee API Key', 'advanced-woo-recommendations'), 'awr_recombee_api_key_callback');
    awr_add_settings_field('awr_gemini_api_key', __('Gemini API Key', 'advanced-woo-recommendations'), 'awr_gemini_api_key_callback');
}
add_action('admin_init', 'awr_register_settings');

// Helper function to register settings
function awr_register_api_setting($option_name, $sanitize_callback) {
    register_setting(
        'awr_settings_group',
        $option_name,
        [
            'sanitize_callback' => $sanitize_callback,
            'default' => ''
        ]
    );
}

// Helper function to add settings field
function awr_add_settings_field($id, $title, $callback) {
    add_settings_field(
        $id,
        $title,
        $callback,
        'awr-settings-page',
        'awr_main_settings_section'
    );
}

// Callback for section description
function awr_main_settings_section_callback() {
    echo __('Enter your API keys and manage plugin settings.', 'advanced-woo-recommendations');
}

// Generic API key input callback
function awr_api_key_input_callback($option_name, $description) {
    $api_key = get_option($option_name);
    ?>
    <input
        type="text"
        name="<?php echo esc_attr($option_name); ?>"
        id="<?php echo esc_attr($option_name); ?>"
        value="<?php echo esc_attr($api_key); ?>"
        class="regular-text"
    />
    <p class="description">
        <?php echo esc_html($description); ?>
    </p>
    <?php
}

// Callback for Recombee API key field
function awr_recombee_api_key_callback() {
    awr_api_key_input_callback('awr_recombee_api_key', __('Enter your Recombee API key. You can find this in your Recombee dashboard.', 'advanced-woo-recommendations'));
}

// Callback for Gemini API key field
function awr_gemini_api_key_callback() {
    awr_api_key_input_callback('awr_gemini_api_key', __('Enter your Gemini API key. You can find this in your Google AI Studio.', 'advanced-woo-recommendations'));
}

// Validate API keys
function awr_validate_api_key($input, $option_name, $test_callback, $api_name) {
    $api_key = sanitize_text_field($input);

    if (empty($api_key)) {
        add_settings_error(
            $option_name,
            'empty_api_key',
            sprintf(__('%s API key cannot be empty.', 'advanced-woo-recommendations'), $api_name),
            'error'
        );
        return get_option($option_name); // Return existing value
    }

    if (!preg_match('/^[a-zA-Z0-9\-\_]{20,}$/', $api_key)) {
        add_settings_error(
            $option_name,
            'invalid_api_key',
            sprintf(__('Invalid %s API key format. Please enter a valid API key.', 'advanced-woo-recommendations'), $api_name),
            'error'
        );
        return get_option($option_name);
    }

    // Test API connection
    $test_result = $test_callback($api_key);
    if (is_wp_error($test_result)) {
        add_settings_error(
            $option_name,
            'api_connection_failed',
            sprintf(
                __('Failed to connect to %s API: %s', 'advanced-woo-recommendations'),
                $api_name,
                $test_result->get_error_message()
            ),
            'error'
        );
        return get_option($option_name);
    }

    return $api_key;
}

// Specific API key validation
function awr_validate_recombee_api_key($input) {
    return awr_validate_api_key($input, 'awr_recombee_api_key', 'awr_test_recombee_api_connection', 'Recombee');
}

function awr_validate_gemini_api_key($input) {
    return awr_validate_api_key($input, 'awr_gemini_api_key', 'awr_test_gemini_api_connection', 'Gemini');
}

// Test Recombee API connection
function awr_test_recombee_api_connection($api_key) {
    $url = 'https://api.recombee.com/test-endpoint'; // Replace with actual test endpoint

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
        ],
    ]);

    return awr_handle_api_response($response, 'Recombee');
}

// Test Gemini API connection
function awr_test_gemini_api_connection($api_key) {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent'; // Replace with actual test endpoint

    $response = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $api_key,
        ],
        'body' => json_encode(['contents' => [['parts' => [['text' => 'test']]]]])
    ]);

    return awr_handle_api_response($response, 'Gemini');
}

// Generic function to handle API responses
function awr_handle_api_response($response, $api_name) {
    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error(
            'api_connection_failed',
            sprintf(__('Failed to connect to %s API. Please check your API key and try again.', 'advanced-woo-recommendations'), $api_name)
        );
    }

    return true;
}
