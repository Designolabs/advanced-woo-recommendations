<?php
// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check WooCommerce dependency.
 *
 * @return bool
 */
function awr_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Advanced Woo Recommendations requires WooCommerce to be installed and active.', 'advanced-woo-recommendations'); ?></p>
            </div>
            <?php
        });
        return false;
    }
    return true;
}

/**
 * Create settings page.
 */
function awr_create_settings_page() {
    if (!awr_check_woocommerce()) {
        return;
    }

    add_menu_page(
        __('Advanced Woo Recommendations', 'advanced-woo-recommendations'),
        __('Woo Recommendations', 'advanced-woo-recommendations'),
        'manage_options',
        'awr-settings',
        'awr_render_settings_page',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'awr_create_settings_page');

/**
 * Render settings page.
 */
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
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register plugin settings.
 */
function awr_register_settings() {
    // API Key Setting
    register_setting('awr_settings_group', 'awr_recombee_api_key', [
        'sanitize_callback' => 'awr_validate_api_key',
        'default' => ''
    ]);

    add_settings_section(
        'awr_main_settings',
        __('API Settings', 'advanced-woo-recommendations'),
        'awr_main_settings_callback',
        'awr-settings-page'
    );

    add_settings_field(
        'awr_recombee_api_key',
        __('Recombee API Key', 'advanced-woo-recommendations'),
        'awr_api_key_callback',
        'awr-settings-page',
        'awr_main_settings'
    );

    // Appearance Settings
    register_setting('awr_settings_group', 'awr_primary_color', [
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#0071a1'
    ]);
    register_setting('awr_settings_group', 'awr_secondary_color', [
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#f1f1f1'
    ]);
    register_setting('awr_settings_group', 'awr_font_family');

    add_settings_section(
        'awr_customization_section',
        __('Recommendations Customization', 'advanced-woo-recommendations'),
        'awr_customization_section_callback',
        'awr-settings-page'
    );

    add_settings_field(
        'awr_primary_color',
        __('Primary Color', 'advanced-woo-recommendations'),
        'awr_primary_color_callback',
        'awr-settings-page',
        'awr_customization_section'
    );

    add_settings_field(
        'awr_secondary_color',
        __('Secondary Color', 'advanced-woo-recommendations'),
        'awr_secondary_color_callback',
        'awr-settings-page',
        'awr_customization_section'
    );

    add_settings_field(
        'awr_font_family',
        __('Font Family', 'advanced-woo-recommendations'),
        'awr_font_family_callback',
        'awr-settings-page',
        'awr_customization_section'
    );
}
add_action('admin_init', 'awr_register_settings');

/**
 * API Settings Callback.
 */
function awr_main_settings_callback() {
    echo __('Enter your Recombee API key to enable personalized recommendations.', 'advanced-woo-recommendations');
}

/**
 * Customization Section Callback.
 */
function awr_customization_section_callback() {
    echo __('Customize the look and feel of your recommendations section to match your brand.', 'advanced-woo-recommendations');
}

/**
 * API Key Field Callback.
 */
function awr_api_key_callback() {
    $api_key = get_option('awr_recombee_api_key');
    echo '<input type="text" name="awr_recombee_api_key" value="' . esc_attr($api_key) . '" />';
}

/**
 * Primary Color Field Callback.
 */
function awr_primary_color_callback() {
    $primary_color = get_option('awr_primary_color', '#0071a1');
    echo '<input type="text" name="awr_primary_color" value="' . esc_attr($primary_color) . '" class="awr-color-picker" />';
}

/**
 * Secondary Color Field Callback.
 */
function awr_secondary_color_callback() {
    $secondary_color = get_option('awr_secondary_color', '#f1f1f1');
    echo '<input type="text" name="awr_secondary_color" value="' . esc_attr($secondary_color) . '" class="awr-color-picker" />';
}

/**
 * Font Family Field Callback.
 */
function awr_font_family_callback() {
    $selected_font = get_option('awr_font_family', 'Arial, sans-serif');
    $google_fonts = [
        'Arial, sans-serif' => 'Arial',
        'Roboto, sans-serif' => 'Roboto',
        'Open Sans, sans-serif' => 'Open Sans',
        'Lato, sans-serif' => 'Lato',
        'Montserrat, sans-serif' => 'Montserrat',
        'Oswald, sans-serif' => 'Oswald',
        'Poppins, sans-serif' => 'Poppins',
        'Raleway, sans-serif' => 'Raleway',
        'Playfair Display, serif' => 'Playfair Display',
    ];

    echo '<select name="awr_font_family" id="awr_font_family">';
    foreach ($google_fonts as $font_css => $font_name) {
        $selected = selected($selected_font, $font_css, false);
        echo "<option value='" . esc_attr($font_css) . "' $selected>" . esc_html($font_name) . "</option>";
    }
    echo '</select>';
    echo '<p id="awr-font-preview" style="font-family:' . esc_attr($selected_font) . ';">' . __('This is a live preview of the selected font.', 'advanced-woo-recommendations') . '</p>';
}

/**
 * Enqueue color picker and custom scripts.
 */
function awr_enqueue_color_picker($hook_suffix) {
    if ('toplevel_page_awr-settings' !== $hook_suffix) {
        return;
    }
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('awr-script', plugin_dir_url(__FILE__) . 'awr-script.js', ['wp-color-picker'], false, true);
}
add_action('admin_enqueue_scripts', 'awr_enqueue_color_picker');

/**
 * Validate API Key.
 */
function awr_validate_api_key($key) {
    $key = sanitize_text_field($key);
    if (strlen($key) !== 32) {
        add_settings_error('awr_recombee_api_key', 'invalid_api_key', __('Invalid API key. Please provide a valid Recombee API key.', 'advanced-woo-recommendations'));
        return '';
    }
    return $key;
}
