<?php
/**
 * Plugin Name: See Finalised Invoice shipping - WooCommerce Shipping Method
 * Plugin URI: https://github.com/mikespook/xxiyy-woocommerce/tree/master/xxiyy-woocommerce-sfi
 * Description: SFI(See Finalised Invoice) Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Xing Xing
 * Author URI: https://mikespook.com
 * License: The MIT License
 * License URI: https://raw.githubusercontent.com/mikespook/xxiyy-woocommerce/master/LICENSE
 * Domain Path: /xxiyy
 * Text Domain: xxiyy
 */

if (!defined('WPINC')) {
	die;
}

/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function xxiyy_sfi_shipping_method() {
		if (!class_exists('XXIYY_SFI_Shipping_Method')) {
			class XXIYY_SFI_Shipping_Method extends WC_Shipping_Method {
				/**
				 * Constructor for your shipping class
				 *
				 * @access public
				 * @return void
				 */
				public function __construct() {
					$this->id                 = 'xxiyy_on_invoice'; 
					$this->method_title       = __( 'On Invoice Shipping', 'xxiyy-sfi' );  
					$this->method_description = __( 'On Invoice Shipping Method for WooCommerce', 'xxiyy-sfi' ); 
					$this->init();
					$this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
					$this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Will be finalised on the invoice', 'xxiyy-sfi' );
				}

				/**
				 * Init your settings
				 *
				 * @access public
				 * @return void
				 */
				function init() {
					// Load the settings API
					$this->init_form_fields(); 
					$this->init_settings(); 

					// Save settings in admin if you have any defined
					add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
				}

				/**
				 * Define settings field for this shipping
				 * @return void 
				 */
				function init_form_fields() { 

					$this->form_fields = array(
						'enabled' => array(
							'title' => __( 'Enable', 'xxiyy-sfi' ),
							'type' => 'checkbox',
							'description' => __( 'Enable this shipping.', 'xxiyy-sfi' ),
							'default' => 'yes'
						),

						'title' => array(
							'title' => __( 'Title', 'xxiyy-sfi' ),
							'type' => 'text',
							'description' => __( 'Title to be display on site', 'xxiyy-sfi' ),
							'default' => __( 'On Invoice Shipping', 'xxiyy-sfi' )
						),
					);
				}

				/**
				 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
				 *
				 * @access public
				 * @param mixed $package
				 * @return void
				 */
				public function calculate_shipping( $package ) {
					$rate = array(
						'id' => $this->id,
						'label' => $this->title,
						'cost' => 0
					);
					$this->add_rate( $rate );

				}
			}
		}
	}

	add_action( 'woocommerce_shipping_init', 'xxiyy_sfi_shipping_method' );

	function add_xxiyy_sfi_shipping_method( $methods ) {
		$methods[] = 'XXIYY_SFI_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_xxiyy_sfi_shipping_method' );
}
