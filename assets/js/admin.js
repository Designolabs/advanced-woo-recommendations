jQuery(document).ready(function ($) {
    $('.awr-color-picker').wpColorPicker();
});

jQuery(document).ready(function ($) {
    // Color Picker initialization
    $('.awr-color-picker').wpColorPicker();

    // Live preview for font family
    $('#awr_font_family').change(function () {
        var selectedFont = $(this).val();
        $('#awr-font-preview').css('font-family', selectedFont);
    });
});
