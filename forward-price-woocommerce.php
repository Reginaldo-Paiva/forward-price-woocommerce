<?php
/*
Plugin Name: Forward Price WooCommerce
Description: Adiciona a opção de preço a prazo para produtos simples e variáveis no WooCommerce.
Version: 1.0
Author: Fr byte
*/

// Adicionar campo de preço a prazo no formulário de produto
function fpw_add_price_field() {
    woocommerce_wp_text_input( 
        array( 
            'id' => '_price_a_prazo', 
            'label' => __( 'Preço a Prazo', 'woocommerce' ), 
            'placeholder' => '', 
            'desc_tip' => 'true',
            'description' => __( 'Digite o preço a prazo do produto.', 'woocommerce' ),
            'type' => 'text',
        )
    );
}
add_action('woocommerce_product_options_pricing', 'fpw_add_price_field');

// Adicionar campo de preço a prazo no formulário de variações de produto
function fpw_add_price_field_variation($loop, $variation_data, $variation) {
    woocommerce_wp_text_input( 
        array( 
            'id' => '_price_a_prazo_' . $loop, 
            'name' => 'variable_price_a_prazo[' . $loop . ']', 
            'value' => get_post_meta($variation->ID, '_price_a_prazo', true), 
            'label' => __( 'Preço a Prazo', 'woocommerce' ), 
            'type' => 'text',
            'description' => __( 'Digite o preço a prazo da variação do produto.', 'woocommerce' ),
            'desc_tip' => 'true'
        )
    );
}
add_action('woocommerce_variation_options_pricing', 'fpw_add_price_field_variation', 10, 3);

// Salvar o campo de preço a prazo
function fpw_save_price_field($post_id) {
    $price_a_prazo = isset($_POST['_price_a_prazo']) ? sanitize_text_field($_POST['_price_a_prazo']) : '';
    if (!empty($price_a_prazo)) {
        update_post_meta($post_id, '_price_a_prazo', $price_a_prazo);
    } else {
        delete_post_meta($post_id, '_price_a_prazo');
    }
}
add_action('woocommerce_process_product_meta', 'fpw_save_price_field');

// Salvar o campo de preço a prazo para variações
function fpw_save_price_field_variation($variation_id, $i) {
    if (isset($_POST['variable_price_a_prazo'][$i])) {
        $price_a_prazo = sanitize_text_field($_POST['variable_price_a_prazo'][$i]);
        if (!empty($price_a_prazo)) {
            update_post_meta($variation_id, '_price_a_prazo', $price_a_prazo);
        } else {
            delete_post_meta($variation_id, '_price_a_prazo');
        }
    }
}
add_action('woocommerce_save_product_variation', 'fpw_save_price_field_variation', 10, 2);

// Adicionar validação para o campo de preço a prazo
function fpw_validate_price_field($post_id) {
    $price_a_prazo = isset($_POST['_price_a_prazo']) ? sanitize_text_field($_POST['_price_a_prazo']) : '';
    if (!empty($price_a_prazo) && !is_numeric($price_a_prazo)) {
        wc_add_notice(__('Preço a prazo deve ser um número válido.'), 'error');
    }
}
add_action('woocommerce_process_product_meta', 'fpw_validate_price_field');

// Adicionar preço a prazo como opção na página do produto
function fpw_display_price_option() {
    global $product;
    $price_a_prazo = get_post_meta($product->get_id(), '_price_a_prazo', true);
    if ($price_a_prazo) {
        echo '<div class="price-a-prazo-option" style="color: #ff6e98; border: 1px solid #ff6e98; border-radius: 5px; padding: 10px;">
            <input type="checkbox" id="use_price_a_prazo" name="use_price_a_prazo">
            <label for="use_price_a_prazo">' . __('Usar preço a prazo?') . ' (' . wc_price($price_a_prazo) . ')</label>
        </div>';
    }
}
add_action('woocommerce_single_product_summary', 'fpw_display_price_option', 15);

// Processar a seleção de preço a prazo na página do produto
function fpw_add_to_cart_price_a_prazo($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['use_price_a_prazo'])) {
        $price_a_prazo = get_post_meta($product_id, '_price_a_prazo', true);
        if ($variation_id) {
            $price_a_prazo = get_post_meta($variation_id, '_price_a_prazo', true);
        }
        if ($price_a_prazo) {
            $cart_item_data['price_a_prazo'] = $price_a_prazo;
        }
    }
    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'fpw_add_to_cart_price_a_prazo', 10, 3);

// Ajustar o preço no carrinho se a opção de preço a prazo for selecionada
function fpw_adjust_cart_price($cart_object) {
    if (!WC()->session->__isset("reload_checkout")) {
        foreach ($cart_object->get_cart() as $cart_item) {
            if (isset($cart_item['price_a_prazo'])) {
                $cart_item['data']->set_price($cart_item['price_a_prazo']);
            }
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'fpw_adjust_cart_price');

// Exibir preço a prazo no frontend
function fpw_display_price_html($price, $product) {
    $price_a_prazo = get_post_meta($product->get_id(), '_price_a_prazo', true);

    if (!empty($price_a_prazo)) {
        $price .= sprintf('<p>Preço a Prazo: %s</p>', wc_price($price_a_prazo));
    }

    return $price;
}
add_filter('woocommerce_get_price_html', 'fpw_display_price_html', 10, 2);

// Shortcode para exibir o preço a prazo
function fpw_price_shortcode() {
    global $product;
    if (is_product()) {
        $price_a_prazo = get_post_meta($product->get_id(), '_price_a_prazo', true);
        if ($price_a_prazo) {
            return sprintf('<div class="price-a-prazo-shortcode" style="color: #ff6e98; background: #e1c8cb; padding: 10px;">Preço a Prazo: %s</div>', wc_price($price_a_prazo));
        }
    }
    return '';
}
add_shortcode('price_a_prazo', 'fpw_price_shortcode');

// AJAX handler para atualizar o preço a prazo
function fpw_update_price_a_prazo() {
    $product_id = intval($_POST['product_id']);
    $use_price_a_prazo = filter_var($_POST['use_price_a_prazo'], FILTER_VALIDATE_BOOLEAN);

    $price_html = '';

    if ($use_price_a_prazo) {
        $price_a_prazo = get_post_meta($product_id, '_price_a_prazo', true);
        if ($price_a_prazo) {
            $price_html = wc_price($price_a_prazo);
        }
    } else {
        $product = wc_get_product($product_id);
        $price_html = $product->get_price_html();
    }

    wp_send_json_success(array('price_html' => $price_html));
}
add_action('wp_ajax_update_price_a_prazo', 'fpw_update_price_a_prazo');
add_action('wp_ajax_nopriv_update_price_a_prazo', 'fpw_update_price_a_prazo');

// Adicionar opção de preço a prazo no carrinho
function fpw_cart_price_option($product_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['price_a_prazo'])) {
        $product_name .= '<p><label><input type="checkbox" class="use_price_a_prazo_cart" data-cart_item_key="' . $cart_item_key . '" ' . checked(true, isset($cart_item['price_a_prazo']), false) . '> ' . __('Usar preço a prazo', 'woocommerce') . ' (' . wc_price($cart_item['price_a_prazo']) . ')</label></p>';
    }
    return $product_name;
}
add_filter('woocommerce_cart_item_name', 'fpw_cart_price_option', 10, 3);

// Atualizar preço do item no carrinho via AJAX
function fpw_update_cart_price() {
    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
    $use_price_a_prazo = filter_var($_POST['use_price_a_prazo'], FILTER_VALIDATE_BOOLEAN);

    $cart = WC()->cart->get_cart();
    if (isset($cart[$cart_item_key])) {
        if ($use_price_a_prazo && isset($cart[$cart_item_key]['price_a_prazo'])) {
            $cart[$cart_item_key]['data']->set_price($cart[$cart_item_key]['price_a_prazo']);
        } else {
            $product_id = $cart[$cart_item_key]['product_id'];
            $product = wc_get_product($product_id);
            $cart[$cart_item_key]['data']->set_price($product->get_price());
        }
    }

    WC()->cart->calculate_totals();
    wp_send_json_success();
}
add_action('wp_ajax_update_cart_item_price', 'fpw_update_cart_price');
add_action('wp_ajax_nopriv_update_cart_item_price', 'fpw_update_cart_price');

// Adicionar campo de preço a prazo na finalização do pedido
function fpw_add_checkout_price_option() {
    echo '<div id="price-a-prazo-checkout" style="color: #ff6e98; background: #e1c8cb; padding: 10px;">
        <input type="checkbox" id="use_price_a_prazo_checkout" name="use_price_a_prazo_checkout">
        <label for="use_price_a_prazo_checkout">' . __('Usar preço a prazo?') . '</label>
        <div id="price_a_prazo_value" style="margin-top: 10px; display: none;">
            <label>' . __('Preço a Prazo: ') . '<span id="price_a_prazo_amount"></span></label>
            <input type="hidden" name="price_a_prazo_amount_hidden" id="price_a_prazo_amount_hidden">
        </div>
    </div>';
}
add_action('woocommerce_review_order_before_payment', 'fpw_add_checkout_price_option');

// Atualizar o preço no checkout se a opção de preço a prazo for selecionada
function fpw_update_checkout_price() {
    if (isset($_POST['use_price_a_prazo_checkout'])) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['price_a_prazo'])) {
                $cart_item['data']->set_price($cart_item['price_a_prazo']);
                $_POST['price_a_prazo_amount_hidden'] = $cart_item['price_a_prazo'];
            }
        }
    } else {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product_id = $cart_item['product_id'];
            $product = wc_get_product($product_id);
            $cart_item['data']->set_price($product->get_price());
        }
    }
    WC()->cart->calculate_totals();
}
add_action('woocommerce_checkout_update_order_review', 'fpw_update_checkout_price');

// Salvar o preço a prazo como meta dos itens do pedido
function fpw_add_order_item_meta($item_id, $values, $cart_item_key) {
    if (isset($values['price_a_prazo'])) {
        wc_add_order_item_meta($item_id, '_price_a_prazo', $values['price_a_prazo']);
    }
}
add_action('woocommerce_add_order_item_meta', 'fpw_add_order_item_meta', 10, 3);

// Salvar campo de preço a prazo no pedido
function fpw_save_order_meta($order_id) {
    if (isset($_POST['price_a_prazo_amount_hidden'])) {
        update_post_meta($order_id, '_price_a_prazo_amount', sanitize_text_field($_POST['price_a_prazo_amount_hidden']));
    }
}
add_action('woocommerce_checkout_update_order_meta', 'fpw_save_order_meta');

// Exibir campo de preço a prazo no admin do pedido
function fpw_display_order_data_in_admin($order) {
    $price_a_prazo_amount = get_post_meta($order->get_id(), '_price_a_prazo_amount', true);
    if ($price_a_prazo_amount) {
        echo '<p><strong>' . __('Preço a Prazo: ') . '</strong>' . wc_price($price_a_prazo_amount) . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'fpw_display_order_data_in_admin', 10, 1);

// Adicionar o preço a prazo nos dados do pedido para o Webhook
function fpw_add_price_a_prazo_to_webhook($order_data, $order, $order_meta) {
    $price_a_prazo_amount = get_post_meta($order->get_id(), '_price_a_prazo_amount', true);
    if ($price_a_prazo_amount) {
        $order_data['meta_data'][] = array(
            'key'   => '_price_a_prazo_amount',
            'value' => $price_a_prazo_amount,
        );
    }
    return $order_data;
}
add_filter('woocommerce_webhook_payload', 'fpw_add_price_a_prazo_to_webhook', 10, 3);
