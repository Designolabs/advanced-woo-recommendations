jQuery(document).ready(function ($) {
    // Initialize color picker with custom options
    $('.awr-color-picker').wpColorPicker({
        defaultColor: '#ffffff',
        change: function(event, ui) {
            $(this).trigger('change'); // Trigger change event for any listeners
        },
        clear: function() {
            $(this).trigger('change'); // Trigger change event when cleared
        }
    });

    // Live preview for font family selection with debouncing
    let fontChangeTimeout;
    $('#awr_font_family').on('change', function () {
        const $preview = $('#awr-font-preview');
        const selectedFont = $(this).val();

        clearTimeout(fontChangeTimeout);
        fontChangeTimeout = setTimeout(() => {
            $preview.css('font-family', selectedFont);
        }, 150);
    });

    // Handle form submission with validation and nonce verification
    $('#awr-settings-form').on('submit', function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $loadingIndicator = $form.find('.loading');
        const requiredFields = $form.find('[required]');
        let isValid = true;

        // Reset error states
        requiredFields.removeClass('error');

        // Validate required fields
        requiredFields.each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            }
        });

        if (!isValid) {
            alert(awrAdmin.strings.validationError || 'Please fill in all required fields');
            return;
        }


        const formData = $form.serialize();


        $.ajax({
            url: awrAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            beforeSend: function () {
                $loadingIndicator.fadeIn();
                $submitButton.prop('disabled', true);
            },
            success: function (response) {
                if (response.success) {
                    // Show success message with fade
                    const $message = $('<div class="notice notice-success"></div>')
                        .text(awrAdmin.strings.saveSuccess)
                        .insertBefore($form)
                        .hide()
                        .fadeIn();

                    setTimeout(() => $message.fadeOut(function() {
                        $(this).remove(); // Remove the message after fade out
                    }), 3000);
                } else {
                    console.error('Save Error:', response.data);
                    alert(awrAdmin.strings.saveError);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', {
                    status: textStatus,
                    error: errorThrown,
                    response: jqXHR.responseText
                });
                alert(awrAdmin.strings.networkError);
            },
            complete: function () {
                $loadingIndicator.fadeOut();
                $submitButton.prop('disabled', false);
            }
        });
    });

    // Toggle visibility of settings section with animation
    $('#toggle-section').on('click', function () {
        const $section = $('#settings-section');
        const $icon = $(this).find('i.dashicons');

        $section.slideToggle(300, function() {
            $icon.toggleClass('dashicons-arrow-down dashicons-arrow-up');
        });
    });

    // Enhanced event listener for dynamically added elements with data handling
    $(document).on('click', '.dynamic-element', function (e) {
        e.preventDefault();

        const $element = $(this);
        const elementId = $element.data('id');
        const elementType = $element.data('type');

        // Handle element based on type
        if (elementId && elementType) {
            console.log(`Processing ${elementType} element with ID: ${elementId}`);
            // Add specific handling logic here
        }
    });
});
