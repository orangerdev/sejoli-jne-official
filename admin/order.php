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

    /**
     * Update status order
     * Hooked via action sejoli/order/update-status, priorirty
     * @since  1.0.0
     * @param  array  $args
     * @return void
     */
    public function process_pickup(array $args) {

		$args = wp_parse_args($args, [
	        'ID'     => NULL,
	        'status' => NULL
	    ]);

	    $respond = sejolisa_get_order([
	        'ID' => $args['ID']
	    ]);

		if(false !== $respond['valid'] && isset($respond['orders']) && isset($respond['orders']['ID'])) :

			$order       = $respond['orders'];
			$prev_status = $order['status'];
			$new_status  = $args['status'];

			if($prev_status === $new_status) :
				sejolisa_set_respond([
						'valid' => false,
						'order' => $order,
						'messages' => [
							'error' => [
								sprintf(__('Can\'t update since current order status and given status are same. The status is %s', 'sejoli'), $new_status)
							]
						]
					],
					'order'
				);
				return;
			endif;

			// We need this hook later to validate if we can allow moving status to another
			// For example, we prevent moving order with status completed to on-hold
			$allow_update_status = apply_filters('sejoli/order/allow-update-status',
				true,
				[
					'prev_status' => $prev_status,
					'new_status'  => $new_status
				],
				$order);

			// is allowed
			if(true === $allow_update_status) :

				do_action('sejoli/order/update-status-from/'. sanitize_title($order['status']), $new_status, $order);

				$respond = sejolisa_update_order_status($args);

				if(false !== $respond['valid']) :

					$order['status'] = $new_status;

					do_action('sejoli/order/status-updated', 	$order);
					do_action('sejoli/order/set-status/'.sanitize_title($new_status), $order);

				endif;

				$respond['messages']['success'][0] = sprintf(__('Order ID #%s updated from %s to %s', 'sejoli'), $order['ID'], $prev_status, $new_status);

				sejolisa_set_respond($respond, 'order');

			else :
				sejolisa_set_respond([
						'valid' => false,
						'order' => $order,
						'messages' => [
							'error' => [
								sprintf(__('Updating order status from %s to %s is not allowed', 'sejoli'), $prev_status, $new_status)
							]
						]
					],
					'order'
				);
			endif;
		else :
			sejolisa_set_respond($respond, 'order');
		endif;
    }

	/**
	 * Update order status by ajax
	 * Hooked via action wp_ajax_sejoli-order-update, priority 1
	 * @return json
	 */
	public function process_pickup_by_ajax() {

		$response = [];

		if(wp_verify_nonce($_POST['nonce'], 'sejoli-order-update')) :

			$post = wp_parse_args($_POST,[
				'orders' => NULL,
				'status' => 'on-hold'
			]);

			if(is_array($post['orders']) && 0 < count($post['orders'])) :

				if(!in_array($post['status'], ['delete', 'resend'])) :

					foreach($post['orders'] as $order_id) :
						do_action('sejoli/order/update-status', [
							'ID'     => $order_id,
							'status' => $post['status']
						]);

						$response[] = sprintf( __('Order %s updated to %s', 'sejoli'), $order_id, $post['status']);
					endforeach;

				elseif('resend' === $post['status'] ) :

					foreach($post['orders'] as $order_id) :

						$get_response = sejolisa_get_order([ 'ID' => $order_id]);

						if(false !== $get_response['valid']) :

							$order = $get_response['orders'];

							do_action('sejoli/notification/order/' . $order['status'], $order);

							$response[] = sprintf( __('Order %s resent notification %s', 'sejoli'), $order_id, $order['status']);

						endif;

					endforeach;

				else :
					// delete
				endif;
			endif;
		endif;

		wp_send_json($response);
		exit;
	}

}