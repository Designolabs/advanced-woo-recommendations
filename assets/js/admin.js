jQuery(document).ready(function ($) {
    // Initialize color picker and set up live preview for font family
    $('.awr-color-picker').wpColorPicker();

    $('#awr_font_family').change(function () {
        var selectedFont = $(this).val();
        $('#awr-font-preview').css('font-family', selectedFont);
    });
});
