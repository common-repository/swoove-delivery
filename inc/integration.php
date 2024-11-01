<?php
	defined('ABSPATH') or die;

	if (!class_exists('SwooveIntegration')) {

		class SwooveIntegration extends WC_Integration
		{

			public function __construct() {
				$this->id = 'swoove-integration';
				$this->method_title = __('Swoove', 'swoove');
				$this->method_description = __('Transforming e-commerce logistics in Africa', 'swoove');
				$this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';

				$this->init_form_fields();
				$this->init_settings();

				// Define user set variables.
				$this->swoove_key = $this->get_option('swoove_key');
				$this->debug_mode = $this->get_option('debug_mode');
				$this->enabled = $this->get_option('enabled');

				update_option('swoove_key', $this->swoove_key);
				update_option('debug_mode', $this->debug_mode);
				update_option('enabled', $this->enabled);
				add_action('woocommerce_update_options_integration_swoove-integration', [$this, 'process_admin_options']);
			}

			public function init_form_fields() {
				$this->form_fields = [
					'enabled' => [
						'title' => __('Enabled', 'swoove'),
						'type' => 'checkbox',
						'description' => __('Enable / Disable Swoove.', 'swoove'),
						'desc_tip' => true,
						'default' => 'yes'
					],
					'debug_mode' => [
						'title' => __('Enable Test Mode', 'swoove'),
						'type' => 'checkbox',
						'description' => __('Enable this to use Swoove with a test app key', 'swoove'),
						'desc_tip' => true,
					],
					'swoove_key' => [
						'title' => __('Swoove APP Key', 'swoove'),
						'type' => 'text',
						'description' => __('Grants your store access to the Swoove API. Find this on your Swoove developer dashboard.', 'swoove'),
						'desc_tip' => true,
					],
					'swoove_callbacks' => array(
						'title' => __('Callback URL', 'swoove'),
						'type' => 'title',
						'description' => __('Set <code>' . get_bloginfo('url') . '?wc-api=swooveco</code> as the callback URL on your Swoove developer dashboard', 'swoove'),
						'default' => '',
					),
					'swoove_portal' => array(
						'title' => __('Swoove Panel', 'swoove'),
						'type' => 'title',
						'description' => __('Visit your <a target="_blank" href="https://portal.swooveapi.com">Swoove Panel</a> to manage your app configuration.', 'swoove'),
						'default' => '',
					),
				];
			}
		}
	}