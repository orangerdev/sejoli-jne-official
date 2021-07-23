<?php
namespace Sejoli_Jne_Official\Admin;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Order {

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
	 * All available order statuses
	 *
	 * @since 	1.0.0
	 * @access 	protected
	 * @var 	array 	   $status 		Order status
	 */
	protected $status = [];

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		// $this->status      = [
  //           'on-hold'     	  => __('Menunggu pembayaran', 'sejoli-jne-official'),
		// 	'payment-confirm' => __('Pembayaran dikonfirmasi', 'sejoli-jne-official'),
  //           'in-progress' 	  => __('Pesanan diproses', 'sejoli-jne-official'),
  //           'pickup'		  => __('Proses Pickup - COD', 'sejoli-jne-official'),
  //           'shipping'    	  => __('Proses pengiriman', 'sejoli-jne-official'),
  //           'completed'   	  => __('Selesai', 'sejoli-jne-official'),
		// 	'refunded'    	  => __('Refund', 'sejoli-jne-official'),
		// 	'cancelled'   	  => __('Batal', 'sejoli-jne-official')
  //       ];
	
	}

	/**
	 * Register cron jobs
	 * Hooked via action admin_init, priority 100
	 * @since 	1.4.1
	 * @return 	void
	 */
	// public function register_cron_jobs() {

	// 	// delete coupon post
	// 	if(false === wp_next_scheduled('sejoli/order/cancel-incomplete-order')) :

	// 		wp_schedule_event(time(), 'quarterdaily', 'sejoli/order/cancel-incomplete-order');

	// 	else :

	// 		$recurring 	= wp_get_schedule('sejoli/order/cancel-incomplete-order');

	// 		if('quarterdaily' !== $recurring) :
	// 			wp_reschedule_event(time(), 'quarterdaily', 'sejoli/order/cancel-incomplete-order');
	// 		endif;

	// 	endif;

	// }

	/**
	 * Add JS Vars for localization
	 * Hooked via sejoli/admin/js-localize-data, priority 1
	 * @since 	1.0.0
	 * @param 	array 	$js_vars 	Array of js vars
	 * @return 	array
	 */
	// public function set_localize_js_cod_var(array $js_vars) {
	
	// 	$js_vars['order'] = [
	// 		'pickup' => [
	// 			'ajaxurl' => add_query_arg([
	// 				'action' => 'sejoli-order-pickup'
	// 			], admin_url('admin-ajax.php')),
	// 			'nonce' => wp_create_nonce('sejoli-order-pickup')
	// 		],
	// 		'pickup_generate_resi' => [
	// 			'ajaxurl' => add_query_arg([
	// 				'action' => 'sejoli-order-pickup-generate-resi'
	// 			], admin_url('admin-ajax.php')),
	// 			'nonce' => wp_create_nonce('sejoli-order-pickup-generate-resi')
	// 		],
	// 		// 'status'   => apply_filters('sejoli/order/status', [])
	// 	];
	// 	return $js_vars;
	
	// }

	// /**
	//  * Get available order status
	//  * Hooked via filter sejoli/order/status, priority 1
	//  * @since  1.0.0
	//  * @param  array  $status
	//  * @return array
	//  */
	// public function get_status($status = []) {

	// 	return $this->status;
	
	// }

}