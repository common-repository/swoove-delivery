<?php

defined('ABSPATH') or die;
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

if (!class_exists('SwooveSM')) {

    class SwooveSM extends WC_Shipping_Method
    {
        public $baseURL = 'https://live.swooveapi.com/estimates/create-estimate?platform=swoove_woocommerce_plugin&app_key=';

        public function __construct($instance_id = 0)
        {
            $this->id = 'swoove';
            $this->instance_id = absint($instance_id);
            $this->method_title = __('Swoove', 'woo-swoove');
            $this->method_description = __('Transforming e-commerce logistics in Africa', 'swoove');
            $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
            $this->supports = [
                'shipping-zones',
                'instance-settings',
            ];
            $this->title = __('Swoove');

            $this->init_form_fields();

            //Get instance values
            $this->contact_person = $this->get_instance_option('contact_person');
            $this->contact_mobile = $this->get_instance_option('contact_mobile');
            $this->contact_email = $this->get_instance_option('contact_email');
            $this->store_location_type = $this->get_instance_option('store_location_type');
            $this->store_location_value = $this->get_instance_option('store_location_value');
            $this->store_address = $this->get_instance_option('store_address');


            //Set store data globally for checkout
            if (0 != $this->instance_id && !is_cart()) {
                setcookie('contact_person', $this->contact_person);
                setcookie('contact_mobile', $this->contact_mobile);
                setcookie('contact_email', $this->contact_email);
                setcookie('store_location_type', $this->store_location_type);
                setcookie('store_location_value', $this->store_location_value);
                setcookie('store_address', $this->store_address);
            }

            // Save all settings
            add_action('woocommerce_update_options_shipping_swoove' . $this->id, [$this, 'process_admin_options'], 3);
        }

        public function init_form_fields()
        {
            $this->instance_form_fields = [
                'contact_person' => [
                    'title' => __('Contact Person', 'swoove'),
                    'type' => 'text',
                    'description' => __('Contact person for the delivery pickup.', 'swoove'),
                    'desc_tip' => true,
                ],
                'contact_mobile' => [
                    'title' => __('Contact Number', 'swoove'),
                    'type' => 'text',
                    'description' => __('Contact mobile number for the pickup location in international format.', 'swoove'),
                    'desc_tip' => true,
                ],
                'contact_email' => [
                    'title' => __('Email Address', 'swoove'),
                    'type' => 'text',
                    'description' => __('Email Address for the pickup location.', 'swoove'),
                    'desc_tip' => true,
                ],
                'store_address' => [
                    'title' => __('Store Address', 'swoove'),
                    'type' => 'text',
                    'description' => __('The public address of your store / warehouse', 'swoove'),
                    'desc_tip' => true,
                ],
                'store_location_type' => [
                    'title' => __('Pick Up location type', 'swoove'),
                    'type' => 'select',
                    'description' => __('The type of location you\'ll be using for this store.', 'swoove'),
                    'default' => 'html',
                    'class' => 'email_type wc-enhanced-select',
                    'options' => [
                        'Select an option',
                        'LATLNG' => __('Latitude / Longitude', 'swoove'),
                        'GHPOST' => __('Ghana Post GPS', 'swoove'),
                        'WHATTW' => __('What3Words', 'swoove'),
                    ],
                    'desc_tip' => true,
                ],
                'store_location_value' => [
                    'title' => __('Pick Up location', 'swoove'),
                    'type' => 'text',
                    'description' => __('The respective value for the type selected above. E.g: 5.3433,-3232 for LATLNG', 'swoove'),
                    'desc_tip' => true,
                ],
            ];
        }

        public function calculate_shipping($packages = [])
        {
            $custy = WC()->cart->get_customer();
            $swoove_key = get_option('swoove_key');
            $isDebug = get_option('debug_mode');

            if ($isDebug == 'yes') $this->baseURL = 'https://test.swooveapi.com/estimates/create-estimate?platform=swoove_woocommerce_plugin&app_key=';

            $customerLat = sanitize_text_field($_COOKIE['swoove_customer_lat']);
            $customerLng = sanitize_text_field($_COOKIE['swoove_customer_lng']);
            $customerName = empty($custy->get_display_name()) ? sanitize_text_field($_COOKIE['swoove_customer_name']) : $custy->get_display_name();
            $customerMobile = empty($custy->get_shipping_phone()) ? $custy->get_billing_phone() : $custy->get_shipping_phone();
            if (empty($customerMobile)) $customerMobile = sanitize_text_field($_COOKIE['swoove_customer_mobile']);
            $customerEmail = sanitize_text_field($_COOKIE['swoove_customer_email']);

            if ('' == $swoove_key || !$customerLat || !$customerLng)
                return;

            $itemLines = $packages['contents'];
            $items = [];
            foreach ($itemLines as $itemLine => $values) {
                $arr = [
                    'itemName' => $values['data']->get_name(),
                    'itemQuantity' => $values['quantity'],
                    'itemCost' => $values['line_total'],
                ];
                $items[] = $arr;
            }
            $options = [
                'method' => 'POST',
                'body' => json_encode([
                    "pickup" => [
                        "type" => $this->store_location_type,
                        "value" => $this->store_location_type != 'LATLNG' ? $this->store_location_value : '',
                        "contact" => [
                            "name" => $this->contact_person,
                            "mobile" => $this->contact_mobile,
                            "email" => $this->contact_email
                        ],
                        "country_code" => "GH",
                        "lat" => $this->store_location_type == 'LATLNG' ? trim(substr($this->store_location_value, 0, strpos($this->store_location_value, ','))) : null,
                        "lng" => $this->store_location_type == 'LATLNG' ? trim(substr($this->store_location_value, strpos($this->store_location_value, ',') + 1)) : null,
                        "location" => $this->store_address,
                    ],
                    "dropoff" => [
                        "type" => "LATLNG",
                        "value" => "",
                        "contact" => [
                            "name" => $customerName,
                            "mobile" => $customerMobile,
                            "email" => $customerEmail,
                        ],
                        "country_code" => "GH",
                        "lat" => $customerLat,
                        "lng" => $customerLng,
                        "location" => $custy->get_shipping_address_1() . ' ' . $custy->get_shipping_address_2(),
                    ],
                    "items" => $items,
                ]),
            ];

            if (sanitize_text_field($_COOKIE['marker_shifted']) || (empty(sanitize_text_field($_COOKIE['rates'])) && !is_cart())) {
                $est_response = wp_remote_post($this->baseURL . $swoove_key, $options);
                if (!is_wp_error($est_response)) {
                    $j_res = json_decode(wp_remote_retrieve_body($est_response));
                    if ($j_res->success) {
                        $estimates = $j_res->responses->estimates;

                        //prevent further calls and save response
                        wc_setcookie('marker_shifted', false);
                        $rates = [];

                        foreach ($estimates as $estimate) {
                            $rate = [
                                'id' => $estimate->estimate_id,
                                'label' => 'Swoove (' . $estimate->agency_details->name . ')',
                                'cost' => $estimate->full_price,
                                'calc_tax' => 'per_item'
                            ];
                            $rates[] = $rate;
                            $this->add_rate($rate);
                        }
                        wc_setcookie('rates', json_encode($rates));
                    } else {
                        if (!wc_has_notice($j_res->message, 'error'))
                            wc_add_notice($j_res->message, 'error');

                        $this->add_rate([
                            'id' => 7,
                            'label' => 'Fill all necessary billing/shipping details to display estimates',
                            'cost' => 0,
                            'calc_tax' => 'per_item'
                        ]);
                    }
                }
            } else {
                if (!empty(sanitize_text_field($_COOKIE['rates'])) && !is_cart()) {
                    $raw_rates = str_replace('\\', '', sanitize_text_field($_COOKIE['rates']));
                    $stored_rates = json_decode($raw_rates, true);
                    foreach ($stored_rates as $rate) {
                        $this->add_rate($rate);
                    }
                }
            }
        }
    }
}
