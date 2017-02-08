<?php
/**
 * Plugin Name: PaymentExpress - WooCommerce Gateway
 * Plugin URI: https://github.com/mikespook/xxiyy-woocommerce/tree/master/xxiyy-woocommerce-pxpay
 * Description: Extends WooCommerce by Adding the PaymentExpress Gateway
 * Version: 1.0.0
 * Author: Xing Xing
 * Author URI: https://mikespook.com
 * License: The MIT License
 * License URI: https://raw.githubusercontent.com/mikespook/xxiyy-woocommerce/master/LICENSE
 * Domain Path: /xxiyy
 * Text Domain: xxiyy
 */

// Include our Gateway Class and Register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'xxiyy_pxpay_loaded', 0 );
function xxiyy_pxpay_loaded() {

	// If the parent WC_Payment_Gateway class doesn't exist
	// it means WooCommerce is not installed on the site
	// so do nothing
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	// 				
	// If we made it this far, then include our Gateway Class
	include_once( 'pxpay.php' );
	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'xxiyy_add_pxpay_gateway' );
	function xxiyy_add_pxpay_gateway( $methods ) {
		$methods[] = 'XXIYY_PxPay';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'xxiyy_pxpay_action_links' );
function xxiyy_pxpay_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'xxiyy-pxpay' ) . '</a>',
	);
	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );
}
