<?php
// Create settings page
function awr_create_settings_page() {
    add_menu_page(
        'Advanced Woo Recommendations',
        'Woo Recommendations',
        'manage_options',
        'awr-settings',
        'awr_render_settings_page',
        'dashicons-admin-generic',
        80
    );
}
add_action('admin_menu', 'awr_create_settings_page');

// Render settings page
function awr_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Advanced Woo Recommendations Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('awr-settings-group');
            do_settings_sections('awr-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function awr_register_settings() {
    // API Settings
    register_setting('awr-settings-group', 'awr_recombee_api_key');
    add_settings_section(
        'awr_main_settings',
        'API Settings',
        'awr_main_settings_callback',
        'awr-settings'
    );
    add_settings_field(
        'awr_recombee_api_key',
        'Recombee API Key',
        'awr_api_key_callback',
        'awr-settings',
        'awr_main_settings'
    );

    // Appearance Customization
    register_setting('awr-settings-group', 'awr_primary_color');
    register_setting('awr-settings-group', 'awr_secondary_color');
    register_setting('awr-settings-group', 'awr_font_family');
    add_settings_section(
        'awr_customization_section',
        __('Recommendations Customization', 'advanced-woo-recommendations'),
        'awr_customization_section_callback',
        'awr-settings'
    );
    add_settings_field(
        'awr_primary_color',
        __('Primary Color', 'advanced-woo-recommendations'),
        'awr_primary_color_callback',
        'awr-settings',
        'awr_customization_section'
    );
    add_settings_field(
        'awr_secondary_color',
        __('Secondary Color', 'advanced-woo-recommendations'),
        'awr_secondary_color_callback',
        'awr-settings',
        'awr_customization_section'
    );
    add_settings_field(
        'awr_font_family',
        __('Font Family', 'advanced-woo-recommendations'),
        'awr_font_family_callback',
        'awr-settings',
        'awr_customization_section'
    );

    // Layout Settings
    register_setting('awr-settings-group', 'awr_layout_style');
    register_setting('awr-settings-group', 'awr_product_columns');
    register_setting('awr-settings-group', 'awr_product_spacing');
    add_settings_section(
        'awr_layout_section',
        __('Recommendations Layout', 'advanced-woo-recommendations'),
        'awr_layout_section_callback',
        'awr-settings'
    );
    add_settings_field(
        'awr_layout_style',
        __('Layout Style', 'advanced-woo-recommendations'),
        'awr_layout_style_callback',
        'awr-settings',
        'awr_layout_section'
    );
    add_settings_field(
        'awr_product_columns',
        __('Number of Columns', 'advanced-woo-recommendations'),
        'awr_product_columns_callback',
        'awr-settings',
        'awr_layout_section'
    );
    add_settings_field(
        'awr_product_spacing',
        __('Product Spacing (px)', 'advanced-woo-recommendations'),
        'awr_product_spacing_callback',
        'awr-settings',
        'awr_layout_section'
    );

    // Color Settings
    register_setting('awr-settings-group', 'awr_bg_color');
    register_setting('awr-settings-group', 'awr_text_color');
    register_setting('awr-settings-group', 'awr_button_color');
    register_setting('awr-settings-group', 'awr_button_hover_color');
    add_settings_section(
        'awr_color_section',
        __('Recommendations Colors', 'advanced-woo-recommendations'),
        'awr_color_section_callback',
        'awr-settings'
    );
    add_settings_field(
        'awr_bg_color',
        __('Background Color', 'advanced-woo-recommendations'),
        'awr_bg_color_callback',
        'awr-settings',
        'awr_color_section'
    );
    add_settings_field(
        'awr_text_color',
        __('Text Color', 'advanced-woo-recommendations'),
        'awr_text_color_callback',
        'awr-settings',
        'awr_color_section'
    );
    add_settings_field(
        'awr_button_color',
        __('Button Color', 'advanced-woo-recommendations'),
        'awr_button_color_callback',
        'awr-settings',
        'awr_color_section'
    );
    add_settings_field(
        'awr_button_hover_color',
        __('Button Hover Color', 'advanced-woo-recommendations'),
        'awr_button_hover_color_callback',
        'awr-settings',
        'awr_color_section'
    );
}
add_action('admin_init', 'awr_register_settings');

// Settings section callback
function awr_main_settings_callback() {
    echo 'Enter your Recombee API key to enable personalized recommendations.';
}

// API key field callback
function awr_api_key_callback() {
    $api_key = get_option('awr_recombee_api_key');
    echo '<input type="text" name="awr_recombee_api_key" value="' . esc_attr($api_key) . '" />';
}


// Callback functions for settings
function awr_customization_section_callback() {
    echo __('Customize the look and feel of your recommendations section to match your brand.', 'advanced-woo-recommendations');
}

function awr_primary_color_callback() {
    $primary_color = get_option('awr_primary_color', '#0071a1');
    echo '<input type="text" name="awr_primary_color" value="' . esc_attr($primary_color) . '" class="awr-color-picker" />';
}

function awr_secondary_color_callback() {
    $secondary_color = get_option('awr_secondary_color', '#f1f1f1');
    echo '<input type="text" name="awr_secondary_color" value="' . esc_attr($secondary_color) . '" class="awr-color-picker" />';
}

function awr_font_family_callback() {
    $selected_font = get_option('awr_font_family', 'Arial, sans-serif');
    $google_fonts = array(
        'Arial, sans-serif' => 'Arial',
        'Roboto, sans-serif' => 'Roboto',
        'Open Sans, sans-serif' => 'Open Sans',
        'Lato, sans-serif' => 'Lato',
        'Montserrat, sans-serif' => 'Montserrat',
        'Oswald, sans-serif' => 'Oswald',
        'Poppins, sans-serif' => 'Poppins',
        'Raleway, sans-serif' => 'Raleway',
        'Playfair Display, serif' => 'Playfair Display',
    );

    echo '<select name="awr_font_family" id="awr_font_family">';
    foreach ($google_fonts as $font_css => $font_name) {
        $selected = selected($selected_font, $font_css, false);
        echo "<option value='" . esc_attr($font_css) . "' $selected>" . esc_html($font_name) . "</option>";
    }
    echo '</select>';
    echo '<p id="awr-font-preview" style="font-family:' . esc_attr($selected_font) . ';">This is a live preview of the selected font.</p>';
}


// Callback functions for the new fields
function awr_layout_section_callback() {
    echo __('Customize the layout of your recommendations section.', 'advanced-woo-recommendations');
}

function awr_layout_style_callback() {
    $layout_style = get_option('awr_layout_style', 'grid');
    $options = array('grid' => 'Grid', 'carousel' => 'Carousel');
    echo '<select name="awr_layout_style">';
    foreach ($options as $value => $label) {
        $selected = selected($layout_style, $value, false);
        echo "<option value='" . esc_attr($value) . "' $selected>" . esc_html($label) . "</option>";
    }
    echo '</select>';
}

function awr_product_columns_callback() {
    $columns = get_option('awr_product_columns', 4);
    echo '<input type="number" name="awr_product_columns" value="' . esc_attr($columns) . '" min="1" max="6" />';
}

function awr_product_spacing_callback() {
    $spacing = get_option('awr_product_spacing', 20);
    echo '<input type="number" name="awr_product_spacing" value="' . esc_attr($spacing) . '" min="0" max="50" />';
}


// Callback functions for the new color fields
function awr_color_section_callback() {
    echo __('Customize the colors of your recommendations section to match your branding.', 'advanced-woo-recommendations');
}

function awr_bg_color_callback() {
    $color = get_option('awr_bg_color', '#ffffff');
    echo '<input type="text" name="awr_bg_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#ffffff" />';
}

function awr_text_color_callback() {
    $color = get_option('awr_text_color', '#000000');
    echo '<input type="text" name="awr_text_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#000000" />';
}

function awr_button_color_callback() {
    $color = get_option('awr_button_color', '#0071a1');
    echo '<input type="text" name="awr_button_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#0071a1" />';
}

function awr_button_hover_color_callback() {
    $color = get_option('awr_button_hover_color', '#005077');
    echo '<input type="text" name="awr_button_hover_color" value="' . esc_attr($color) . '" class="awr-color-field" data-default-color="#005077" />';
}
