jQuery(document).ready(function($) {
    $('#price-a-prazo-button').on('click', function() {
        $('#price-a-prazo-popup').show();
    });

    $('.close-popup').on('click', function() {
        $('#price-a-prazo-popup').hide();
    });

    $('#use_price_a_prazo').on('change', function() {
        var product_id = $(this).closest('form.cart').find('input[name="add-to-cart"]').val();
        var use_price_a_prazo = $(this).is(':checked');

        $.ajax({
            url: forward_price_woocommerce.ajax_url,
            type: 'POST',
            data: {
                action: 'update_price_a_prazo',
                product_id: product_id,
                use_price_a_prazo: use_price_a_prazo
            },
            success: function(response) {
                if (response.success) {
                    $('.woocommerce-Price-amount').html(response.data.price_html);
                }
            }
        });
    });

    $('.use_price_a_prazo_cart').on('change', function() {
        var cart_item_key = $(this).data('cart_item_key');
        var use_price_a_prazo = $(this).is(':checked');

        $.ajax({
            url: forward_price_woocommerce.ajax_url,
            type: 'POST',
            data: {
                action: 'update_cart_item_price',
                cart_item_key: cart_item_key,
                use_price_a_prazo: use_price_a_prazo
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    });

    $('#use_price_a_prazo_checkout').on('change', function() {
        var use_price_a_prazo_checkout = $(this).is(':checked');

        if (use_price_a_prazo_checkout) {
            $('#price_a_prazo_value').show();
            var price_a_prazo_amount = $('input[name="price_a_prazo_amount_hidden"]').val();
            $('#price_a_prazo_amount').text(price_a_prazo_amount);
        } else {
            $('#price_a_prazo_value').hide();
        }

        $.ajax({
            url: wc_checkout_params.ajax_url,
            type: 'POST',
            data: {
                action: 'woocommerce_update_order_review',
                use_price_a_prazo_checkout: use_price_a_prazo_checkout
            },
            success: function(response) {
                if (response.success) {
                    $('body').trigger('update_checkout');
                }
            }
        });
    });
});
