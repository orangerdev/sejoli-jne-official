<?php

namespace Sejoli_Jne_Official\Admin;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

final class Payment {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * All payment libraries
     * @since   1.0.0
     * @access  private
     * @var     null|array
     */
    private $libraries = NULL;

	/**
	 * Current order data
	 * @since 1.0.0
	 * @var array
	 */
	protected $order_data = [];

	/**
	 * Current order payment gateway
	 * @since 1.0.0
	 * @var string
	 */
	protected $used_module;

	/**
	 * Current payment subtype
	 * @since 	1.0.0
	 * @var 	false|string
	 */
	protected $payment_subtype = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

    /**
     * Read available payment libraries
     * @return [type] [description]
     */
    protected function read_libraries() {

        require_once ( SEJOLI_JNE_OFFICIAL_DIR . 'payments/main.php' );
        require_once ( SEJOLI_JNE_OFFICIAL_DIR . 'payments/cod.php' );

        $libraries['cod'] 	   = new \Sejoli_Jne_Official\Payment\Cod();

        $this->libraries = apply_filters('sejoli/payment/available-libraries', $libraries);
    }

    /**
     * Load payment libraries
     * Hooked via action plugins_loaded, priority 10
     * @return void
     */
    public function load_libraries() {

        $this->read_libraries();

    }

	/**
	 * Get available payment gateways
	 * Hooked via filter sejoli/payment/available-payment-gateway, priority 10
	 * @since 	1.0.0
	 * @param  	array  $payment_gateways
	 * @return	array
	 */
	public function get_available_payment_gateways(array $payment_gateways) {

		foreach($this->libraries as $key => $library) :

			$payment_gateways[$key] = [
				'id'	=> $key,
				'title'	=> $library->get_title(),
				'image'	=> $library->get_logo()
			];
		endforeach;

		return $payment_gateways;
	}

	/**
	 * Set each payment method setup field
	 * Hooked via filter sejoli/general/fields, priority 40
	 * @param  array  $fields
	 * @return array
	 */
	public function setup_payment_setting_fields(array $fields) {

		foreach($this->libraries as $module => $library) :
			$fields[] = [
				'title'		=> $library->get_title(),
				'fields'	=> $library->get_setup_fields()
			];
		endforeach;

		return $fields;
	}

	/**
	 * Set order data
	 * @param array $order_data
	 */
	protected function set_order(array $order_data) {

		$this->order_data        = $order_data;

		list(
			$used_module,
			$this->payment_subtype
		) = wp_parse_args([
			0 => 'manual',
			1 => NULL
		], explode(':::', $order_data['payment_gateway']));

		$this->used_module = isset($this->libraries[$used_module]) ?
								$this->libraries[$used_module] :
								false;
	}

	/**
	 * Get payment module from payment gateway
	 * Hooked via filter sejoli/payment/module, priority 1
	 * @since 	1.0.0
	 * @param  	string $payment_gateway [description]
	 * @return 	string                  [description]
	 */
	public function get_payment_module($payment_gateway) {

		list($payment_module) = explode(':::', $payment_gateway);

		return $payment_module;
	}

	/**
	 * Set order price
	 * Hooked via filter sejoli/order/grand-total, priority 100
	 * @since 1.0.0
	 * @param float $price
	 * @param array $order_data
	 * @return float;
	 */
	public function set_price($price, array $order_data) {

		if(0.0 === floatval($price)) :
			return $price;
		endif;

		$this->set_order($order_data);

		if(false !== $this->used_module) :
			return $this->used_module->set_price($price, $order_data);
		endif;

		return $price;
	}

	/**
	 * Add transaction fee to cart detaild
	 * Hooked via filter sejoli/order/cart-detail, priority 10
	 * @param array $cart_detail
	 * @param array $order_data
	 * @return array
	 */
	public function set_cart_detail(array $cart_detail, array $order_data) {

		if( false !== $this->used_module && method_exists($this->used_module, 'add_transaction_fee') ) :
			$cart_detail['transaction_fee'] = $this->used_module->add_transaction_fee($order_data);
		endif;

		return $cart_detail;
	}

	/**
	 * Set order meta data
	 * Hooked via filter sejoli/order/meta-data, priority 100
	 * @param array $meta_data
	 * @param array $order_data
	 */
	public function set_meta_data($meta_data = [], array $order_data) {

		$this->set_order($order_data);

		if(false !== $this->used_module) :
			return $this->used_module->set_meta_data($meta_data, $order_data, $this->payment_subtype);
		endif;

		return $meta_data;
	}

	/**
	 * Set product data to order
	 * Hooked via filter sejoli/order/order-detail, priority 20
	 * @since 	1.0.0
	 * @param 	array $order_detail
	 * @return 	array
	 */
	public function set_payment_data_to_order_detail( array $order_data ) {

		$used_module = $order_data['payment_gateway'];

		if( isset($this->libraries[$used_module]) ) :

			$order_data['payment_info'] = $this->libraries[$used_module]->set_payment_info($order_data);

		endif;

		return $order_data;
	}

	/**
	 * Display payment instruction
	 * Hooked via sejoli/notification/content/order-meta
	 * @param  string $content      	[description]
	 * @param  string $media        	[description]
	 * @param  string $recipient_type   [description]
	 * @param  array  $invoice_data 	[description]
	 * @return string
	 */
	public function display_payment_instruction(string $content, string $media, $recipient_type, array $invoice_data) {

		$used_module = $invoice_data['order_data']['payment_gateway'];

		if(
			isset($this->libraries[$used_module]) &&
			method_exists($this->libraries[$used_module], 'display_payment_instruction') &&
			'buyer' === $recipient_type
		) :
			$content .= $this->libraries[$used_module]->display_payment_instruction($invoice_data, $media);
		endif;

		return $content;
	}

	/**
	 * Display simple payment instruction
	 * Hooked via sejoli/notification/content/payment-gateway
	 * @param  string $content      	[description]
	 * @param  string $media        	[description]
	 * @param  string $recipient_type   [description]
	 * @param  array  $invoice_data 	[description]
	 * @return string
	 */
	public function display_simple_payment_instruction(string $content, string $media, $recipient_type, array $invoice_data) {

		$used_module = $invoice_data['order_data']['payment_gateway'];

		if(
			isset($this->libraries[$used_module]) &&
			method_exists($this->libraries[$used_module], 'display_simple_payment_instruction') &&
			'buyer' === $recipient_type
		) :
			$content .= $this->libraries[$used_module]->display_simple_payment_instruction($invoice_data, $media);
		endif;

		return $content;
	}

	/**
	 * Get payment fee
	 * @since 	1.1.6
	 * @param  	float 	$fee   			Fee
	 * @param  	array  	$invoice_data 	Order data
	 * @return 	float
	 */
	public function get_payment_fee($fee, array $invoice_data) {

		$used_module = $invoice_data['payment_gateway'];

		if(isset($this->libraries[$used_module])) :
			$operational = $this->libraries[$used_module]->get_operational_method();
			$fee = $operational.$invoice_data['meta_data'][$used_module]['unique_code'];
		endif;

		return $fee;
	}
}
