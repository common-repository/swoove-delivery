<?php

/**
 * Plugin Name: WooCommerce Swoove
 * Plugin URI: https://swoove.delivery/devs
 * Description:
 * Version: 1.0.3
 * Author: Swoove
 * Author URI: https://swoove.delivery/devs
 *
 * Requires at least: 5.8
 * WC requires at least: 5.0
 * WC tested up to: 6.9.4
 * Requires PHP: 7.2
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') or die;
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;


if (!class_exists('WC_Swoove')) {
	class WC_Swoove
	{

		protected static $instance = null;

		function __construct()
		{
			if (class_exists('WC_Integration')) {
				$this->includeExtras();
				add_filter('woocommerce_shipping_methods', array($this, 'add_swoove_shipping_method'), 2);
				add_action('woocommerce_shipping_init', array($this, 'load_shipping'), 1);
				add_filter('woocommerce_integrations', array($this, 'load_integration'));
			}
		}

		function add_swoove_shipping_method($methods)
		{
			$methods['swoove'] = 'SwooveSM';
			return $methods;
		}

		function load_shipping()
		{
			include_once dirname(__FILE__) . '/inc/shipping.php';
		}

		function load_integration($integrations)
		{
			$integrations[] = 'SwooveIntegration';
			return $integrations;
		}

		function includeExtras()
		{
			include_once dirname(__FILE__) . '/inc/integration.php';
			include_once dirname(__FILE__) . '/inc/office.php';
			include_once dirname(__FILE__) . '/inc/checkout.php';
			include_once dirname(__FILE__) . '/inc/done.php';
		}

		public static function get_instance()
		{
			if (null === self::$instance)
				self::$instance = new self;
			return self::$instance;
		}
	}
}

add_action('plugins_loaded', array('WC_Swoove', 'get_instance'));
