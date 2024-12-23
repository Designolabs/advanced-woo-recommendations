jQuery(document).ready(function ($) {
    // Update cart quantity
    $('.cart').on('change', '.quantity input.qty', function () {
        const $this = $(this);
        let newQuantity = parseInt($this.val(), 10);
        const cartItemKey = $this.closest('.cart_item').data('cart_item_key');

        // Validate quantity
        if (isNaN(newQuantity) || newQuantity < 0) {
            alert('Quantity must be a non-negative number.');
            $this.val($this.data('original-value')); // Reset to original value
            return;
        }

        // Store the current value before AJAX call
        $this.data('original-value', newQuantity);


        // AJAX request to update cart
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'update_cart_item',
                cart_item_key: cartItemKey,
                quantity: newQuantity,
                security: wc_cart_params.update_cart_nonce
            },
            success: function (response) {
                if (response.success) {
                    // Update cart totals
                    $('.cart_totals').html(response.data.cart_totals);
                    // Optionally refresh the cart item
                    $this.closest('.cart_item').replaceWith(response.data.cart_item_html);
                } else {
                    alert(response.data.message || 'An error occurred while updating the cart.');
                    $this.val($this.data('original-value')); // Reset to original value on error
                }
            },
            error: function () {
                alert('An error occurred while processing your request. Please try again.');
                $this.val($this.data('original-value')); // Reset to original value on error
            }
        });
    });

    // Remove item from cart
    $('.cart').on('click', '.remove', function (e) {
        e.preventDefault();
        const $this = $(this);
        const cartItemKey = $this.closest('.cart_item').data('cart_item_key');

        // AJAX request to remove item
        $.ajax({
            url: wc_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'remove_cart_item',
                cart_item_key: cartItemKey,
                security: wc_cart_params.remove_cart_nonce
            },
            success: function (response) {
                if (response.success) {
                    // Remove the cart item from the DOM
                    $this.closest('.cart_item').fadeOut(300, function () {
                        $(this).remove();
                    });
                    // Update cart totals
                    $('.cart_totals').html(response.data.cart_totals);
                    // Trigger updated_cart_totals event to refresh recommendations
                    $(document.body).trigger('updated_cart_totals');
                } else {
                    alert(response.data.message || 'An error occurred while removing the item.');
                }
            },
            error: function () {
                alert('An error occurred while processing your request. Please try again.');
            }
        });
    });

    // Function to fetch and display recommendations when the cart is updated
    function fetchCartRecommendations() {
        const $recommendationsContainer = $('#awr-cart-recommendations');
        $.ajax({
            url: awrAdmin.ajaxurl,
            method: 'POST',
            data: {
                action: 'awr_fetch_cart_recommendations',
                nonce: awrAdmin.nonce
            },
            beforeSend: function() {
                // Show a loading indicator
                $recommendationsContainer.html('<div class="awr-loading-spinner" role="alert" aria-busy="true"><div class="spinner" aria-hidden="true"></div><p>Loading recommendations...</p></div>');
            },
            success: function(response) {
                if (response.success) {
                    $recommendationsContainer.html(response.data.html);
                } else {
                    console.error('Error fetching recommendations:', response.data);
                    displayError('Failed to load recommendations. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                displayError('Network error. Please check your connection and try again.');
            },
            complete: function() {
                // Optionally hide the loading indicator
            }
        });
    }

    // Function to display error messages
    function displayError(message) {
        $('#awr-cart-recommendations').html(
            `<div class="awr-error-message" role="alert">${message}</div>`
        );
    }

    // Bind to WooCommerce cart updated event
    $(document.body).on('updated_cart_totals', fetchCartRecommendations);

    // Initial call on page load
    fetchCartRecommendations();
});