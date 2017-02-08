<?php
include_once("PxPay_OpenSSL.inc.php");

/* PaymentExpress Payment Gateway Class */
class XXIYY_PxPay extends WC_Payment_Gateway {
	const API = "https://sec.paymentexpress.com/pxaccess/pxpay.aspx";
	const testAPI = "https://uat.paymentexpress.com/pxaccess/pxpay.aspx";

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "xxiyy_pxpay";

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "PaymentExpress", 'xxiyy-pxpay' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "PaymentExpress Payment Gateway Plug-in for WooCommerce", 'xxiyy-pxpay' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( "PaymentExpress", 'xxiyy-pxpay' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = "https://www.paymentexpress.com/Image/paynow-red-120x29-png.png";

		// Bool. Can be set to true if you want payment fields to show on the checkout 
		// if doing a direct integration, which we are doing in this case
		$this->has_fields = false;

		// Supports the default credit card form
		//		$this->supports = array( 'default_credit_card_form' );

		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();

		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
		}

		// Lets check for SSL
		add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
		add_action( 'woocommerce_api_xxiyy_pxpay', array( $this, 'handle_itn_request' ) );	

		// Save settings
		if ( is_admin() ) {
			// Versions over 2.0
			// Save our administration options. Since we are not going to be doing anything special
			// we have not defined 'process_admin_options' in this class so the method in the parent
			// class will be used instead
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}		
	} // End __construct()

	// Build the administration fields for this specific Gateway
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'		=> __( 'Enable / Disable', 'xxiyy-pxpay' ),
				'label'		=> __( 'Enable this payment gateway', 'xxiyy-pxpay' ),
				'type'		=> 'checkbox',
				'default'	=> 'no',
			),
			'title' => array(
				'title'		=> __( 'Title', 'xxiyy-pxpay' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.', 'xxiyy-pxpay' ),
				'default'	=> __( 'Credit card', 'xxiyy-pxpay' ),
			),
			'description' => array(
				'title'		=> __( 'Description', 'xxiyy-pxpay' ),
				'type'		=> 'textarea',
				'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.', 'xxiyy-pxpay' ),
				'default'	=> __( 'Pay securely using your credit card.', 'xxiyy-pxpay' ),
				'css'		=> 'max-width:350px;'
			),
			'pxpay_userid' => array(
				'title'		=> __( 'PxPay UserId', 'xxiyy-pxpay' ),
				'type'		=> 'text',
				'desc_tip'	=> __( 'This is the UserId provided by PaymentExpress when you signed up for an account.', 'xxiyy-pxpay' ),
			),
			'pxpay_key' => array(
				'title'		=> __( 'PxPay Key', 'xxiyy-pxpay' ),
				'type'		=> 'password',
				'desc_tip'	=> __( 'This is the Key provided by PaymentExpress when you signed up for an account.', 'xxiyy-pxpay' ),
			),
			'testing' => array(
				'title'		=> __( 'Test Mode', 'xxiyy-pxpay' ),
				'label'		=> __( 'Enable Test Mode', 'xxiyy-pxpay' ),
				'type'		=> 'checkbox',
				'description' => __( 'Place the payment gateway in test mode.', 'xxiyy-pxpay' ),
				'default'	=> 'no',				
			)
		);		
	}

	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;

		// Get this Order's information so that we know
		// who to charge and how much
		$customer_order = new WC_Order( $order_id );

		$url = self::API;
		if ($this->testing != 'no') {
			$url = self::testAPI;
		}

		$pxpay = new PxPay_OpenSSL( $url, $this->pxpay_userid, $this->pxpay_key );

		$request = new PxPayRequest();

		$orderId = str_replace( "#", "", $customer_order->get_order_number() );
		$total = $customer_order->order_total;
		$name = sprintf("%s %s", $customer_order->billing_first_name, $customer_order->billing_last_name);
		$addr3 = sprintf("%s, %s, %s, %s %s", $customer_order->billing_address_1, $customer_order->billing_city, $customer_order->billing_state, 
			$customer_order->billing_country, $customer_order->billing_postcode);
		$email = $customer_order->billing_email;
		$currency = $customer_order->get_order_currency();

		#Set PxPay properties
		$request->setMerchantReference($customer_order->order_key);
		$request->setAmountInput($total);
		$request->setTxnData1($orderId);
		$request->setTxnData2($name);
		$request->setTxnData3($addr);
		$request->setTxnType("Purchase");
		$request->setCurrencyInput($currency);
		$request->setEmailAddress($email);
		$script_url = home_url() . "/wc-api/XXIYY_PxPay/";
		$request->setUrlFail($script_url);			# can be a dedicated failure page
		$request->setUrlSuccess($script_url);			# can be a dedicated success page
		$txnId = uniqid($orderId);
		$customer_order->add_order_note(sprintf('PxPay TxnId: %s', $txnId));
		$request->setTxnId($txnId);  

		#The following properties are not used in this case
		# $request->setEnableAddBillCard($EnableAddBillCard);    
		# $request->setBillingId($BillingId);
		# $request->setOpt($Opt);



		#Call makeRequest function to obtain input XML
		$request_string = $pxpay->makeRequest($request);

		#Obtain output XML
		$response = new MifMessage($request_string);
		#Parse output XML
		if ($response->get_attribute("valid")) {
			return array(
				'result'   => 'success',
				'redirect' => $response->get_element_text("URI"),
			);
		}
		throw new Exception( __( 'We are currently experiencing problems trying to connect to this payment gateway. Sorry for the inconvenience.', 'xxiyy-pxpay' ) );
	}

	// Validate fields
	public function validate_fields() {
		return true;
	}

	// Check if we are forcing SSL on checkout pages
	// Custom function not required by the Gateway
	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
	}

	public function handle_itn_request() {
		$this->log('Callback Data: '. print_r( $_REQUEST, true ));
		$userid = wc_clean(stripslashes($_REQUEST['userid']));
		$enc_hex = wc_clean(stripslashes($_REQUEST["result"]));
		if ($this->pxpay_userid !== $userid) {
			WC()->cart->empty_cart();
			wc_add_notice(__('Fatal error, please contact the webadmin', 'xxiyy-pxpay'), 'error');
			wp_redirect(home_url());			
			return;
		}
		$url = self::API;
		if ($this->testing != 'no') {
			$url = self::testAPI;
		}
		$pxpay = new PxPay_OpenSSL( $url, $this->pxpay_userid, $this->pxpay_key );
		#getResponse method in PxPay object returns PxPayResponse object
		#which encapsulates all the response data
		$rsp = $pxpay->getResponse($enc_hex);
		$orderId = $rsp->getTxnData1();
		$order = new WC_Order($orderId);
		if ($rsp->getSuccess() != 1) {			
			WC()->cart->empty_cart();
			wc_add_notice(__('Payment Unsuccessful. We\'re sorry, but we are not able to process your payment for service: ' . $rsp->getResponseText(), 'xxiyy-pxpay'), 'error');
			wp_redirect($order->get_view_order_url());
			return;
		}
		if ($order->order_key != $rsp->getMerchantReference()) {
			WC()->cart->empty_cart();
			wc_add_notice(__('Fatal error, please contact the webadmin', 'xxiyy-pxpay'), 'error');
			wp_redirect($order->get_view_order_url());
			return;
		}
		$amount = $rsp->getAmountSettlement();
		$currency = $rsp->getCurrencyInput();
		if ($order->order_total != $amount ||
			$order->order_currency != $currency) {
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: PxPay amounts do not match (amt %s: %s).', 'xxiyy-pxpay' ), $currency, $amount) );
			$order->reduce_order_stock();
			WC()->cart->empty_cart();
			wc_add_notice(__('The payment is not fully completed, thus the order was set to on-hold.', 'xxiyy-pxpay'), 'error');
			wp_redirect($order->get_view_order_url());
			return;	
		}

		# the following are the fields available in the PxPayResponse object
		$note = htmlspecialchars($rsp->toXml());
		$order->add_order_note($note);
		$order->payment_complete($rsp->getBillingId());
		wp_redirect($order->get_checkout_order_received_url());
	}


	/**
	 * Log system processes.
	 * @since 1.0.0
	 */
	public function log( $message ) {
		if ( 'yes' === $this->get_option( 'testing' ) ) {
			if ( ! $this->logger ) {
				$this->logger = new WC_Logger();
			}
			$this->logger->add( 'pxpay', $message );
		}
	}

} // End of XXIYY_PxPay
