<?php
new SwooveCO();

class SwooveCO
{
    public function __construct()
    {
        add_filter('woocommerce_checkout_fields', [$this, 'customize_checkout_fields']);
        add_action('woocommerce_after_order_notes', [$this, 'display_custom_checkout_fields']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_fields']);

        wp_enqueue_script('swoovemap-js', plugins_url('../assets/js/swoove_map.js', __FILE__), ['jquery'], date("h:i:s"));
        wp_enqueue_style('swoove-css', plugins_url('../assets/css/swoove.css', __FILE__));
        add_action('woocommerce_review_order_before_cart_contents', [$this, 'validate_order'], 10);
        add_action('woocommerce_checkout_process', [$this, 'validate_order'], 10);
        add_filter('woocommerce_cart_shipping_method_full_label', array($this, 'change_shipping_label'), 10, 2);
        add_action('woocommerce_checkout_update_order_review', [$this, 'action_woocommerce_checkout_update_order_review'], 10);
        add_filter('wc_ajax_save_customer_point', [$this, 'save_customer_point']);
        add_filter('transient_shipping-transient-version', function ($value, $name) {
            return false;
        }, 10, 2);
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'webhook'));
        add_filter('woocommerce_shipping_packages', function ($r) {
            error_log('chosen shipping: ' . print_r(esc_attr($_POST['shipping_method']) ?? '', true));
            return $r;
        });
    }

    public function webhook()
    {
        $data = file_get_contents('php://input');
        $j_res = json_decode($data, true);
        if ($j_res['success']) {
            $responses = $j_res['responses'];
            $delivery_status = $responses['status'];
            $orderId = $responses['reference'];
            $tracking_link = isset($responses['tracking_link']) ? $responses['tracking_link'] : '';
            update_post_meta($orderId, 'swoove_delivery_status', sanitize_text_field($delivery_status));
            update_post_meta($orderId, 'swoove_tracking_link', sanitize_text_field($tracking_link));
        }
    }

    function customize_checkout_fields($fields)
    {
        $fields["swoove"] = [
            'swoove_customer_lat' => [
                'type' => 'text',
                'class' => ['form-row form-row-wide sv_cust_hd update_totals_on_change'],
            ],
            'swoove_customer_lng' => [
                'type' => 'text',
                'class' => ['form-row form-row-wide sv_cust_hd update_totals_on_change'],
            ],
            'swoove_estimate_id' => [
                'type' => 'text',
                'class' => ['form-row form-row-wide sv_cust_hd'],
            ],
            'swoove_delivery_status' => [
                'type' => 'text',
                'default' => 'NOT REQUESTED',
                'class' => ['form-row form-row-wide sv_cust_hd'],
            ]
        ];
        return $fields;
    }

    function display_custom_checkout_fields($checkout)
    {
        foreach ($checkout->checkout_fields['swoove'] as $key => $field) :
            woocommerce_form_field($key, $field, $checkout->get_value($key));
        endforeach;
        WC()->cart->calculate_shipping();
    }

    function save_fields($order_id)
    {
        if (!empty($_POST['swoove_customer_instructions']))
            update_post_meta($order_id, 'swoove_customer_instructions', sanitize_text_field($_POST['swoove_customer_instructions']));
        if (!empty($_POST['swoove_customer_lat']))
            update_post_meta($order_id, 'swoove_customer_lat', sanitize_text_field($_POST['swoove_customer_lat']));
        if (!empty($_POST['swoove_customer_lng']))
            update_post_meta($order_id, 'swoove_customer_lng', sanitize_text_field($_POST['swoove_customer_lng']));
        if (!empty($_POST['swoove_estimate_id']))
            update_post_meta($order_id, 'swoove_estimate_id', sanitize_text_field($_POST['swoove_estimate_id']));

        update_post_meta($order_id, 'swoove_delivery_status', sanitize_text_field($_POST['swoove_delivery_status']));
        update_post_meta($order_id, 'contact_person', sanitize_text_field($_COOKIE['contact_person']));
        update_post_meta($order_id, 'contact_mobile', sanitize_text_field($_COOKIE['contact_mobile']));
        update_post_meta($order_id, 'contact_email', sanitize_text_field($_COOKIE['contact_email']));
        update_post_meta($order_id, 'store_location_type', sanitize_text_field($_COOKIE['store_location_type']));
        update_post_meta($order_id, 'store_location_value', sanitize_text_field($_COOKIE['store_location_value']));
        update_post_meta($order_id, 'store_address', sanitize_text_field($_COOKIE['store_address']));
    }

    function change_shipping_label($full_label, $method)
    {
        if (is_cart())
            return "Swoove: " . __('Proceed to <b>Checkout</b> to calculate delivery', 'swoove');
        return $full_label;
    }

    function action_woocommerce_checkout_update_order_review($postData)
    {
        WC()->cart->calculate_shipping();
        return;
    }

    function save_customer_point()
    {
        wc_setcookie('swoove_customer_lat', sanitize_text_field($_POST['lat']));
        wc_setcookie('swoove_customer_lng', sanitize_text_field($_POST['lng']));
        wc_setcookie('swoove_customer_lng', sanitize_text_field($_POST['lng']));
        wc_setcookie('swoove_customer_name', sanitize_text_field($_POST['name']));
        wc_setcookie('swoove_customer_mobile', sanitize_text_field($_POST['mobile']));
        wc_setcookie('swoove_customer_email', sanitize_text_field($_POST['email']));
        wc_setcookie('marker_shifted', true);
    }

    function validate_order()
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $estimateId = $chosen_methods[0];

        //Set estimate id
        echo "<script>document.getElementById('swoove_estimate_id').value='" . $estimateId . "'</script>";
    }
}
