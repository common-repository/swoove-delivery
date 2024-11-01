<?php

new SwooveDone();

class SwooveDone
{

	public function __construct()
	{
		add_action('woocommerce_thankyou', array($this, 'display_info'), 20);
		add_action('woocommerce_view_order', array($this, 'customer_order'), 20);
	}

	public function customer_order($order_id)
	{
		echo esc_html("<h2 class='woocommerce-column__title' style='margin-bottom: 0'>Delivery</h2>");

		$sd_code = get_post_meta($order_id, 'swoove_id', true);
		$status = get_post_meta($order_id, 'swoove_delivery_status', true);
		$track = get_post_meta($order_id, 'swoove_tracking_link', true);
		$address = wc_get_order($order_id)->get_billing_address_1();

		echo esc_html('<p style="margin-bottom: 0"><b>' . __('Delivery Code', 'swoove') . ': </b>' . (empty($sd_code) ? 'Not Created' : $sd_code) . '</p>');
		echo esc_html('<p style="margin-bottom: 0"><b>' . __('Status', 'swoove') . ': </b>' . $status . '</p>');
		echo esc_html('<p><b>' . __('DropOff', 'swoove') . ': </b>' . $address . '</p>');
		if (!empty($track))
			echo esc_html('<a href="' . $track . '"  target="_blank" class="button">
						Track Delivery
						<span class="dashicons dashicons-external" style="font-size: 17px;margin-top: 4px;"></span></a>');
	}

	public function display_info($order_id)
	{
	}
}
