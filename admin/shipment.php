<?php

namespace Sejoli_Jne_Official\Admin;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class ShipmentJNE {

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
	 * Current product commission
	 * @since 	1.0.0
	 * @access 	protected
	 * @var 	null|array
	 */
	protected $current_commission = NULL;

	/**
	 * Shipping data
	 * @since 	1.0.0
	 * @access 	protected
	 * @var 	null|array
	 */
	protected $shipping_data = NULL;

	/**
	 * Shipping libraries data
	 * @since	1.2.0
	 * @access 	protected
	 * @var 	array
	 */
	protected $libraries = array();

	/**
	 * Does order need shipment?
	 * @since 	1.0.0
	 * @var 	boolean
	 */
	protected $order_needs_shipment = false;

	/**
	 * List of used delivery couriers and services.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	// private $couriers = array(
	// 	'domestic'      => array(
	// 		'jne'     => array(
	// 			'label'    => 'JNE',
	// 			'website'  => 'http://www.jne.co.id',
	// 			'active'   => true,
	// 			'services' => array(
	// 				'CTC'    => array(
	// 					'title'	 => 'City Courier',
	// 					'active' => true
	// 				),
	// 				'OKE'    => array(
	// 					'title'	 => 'Ongkos Kirim Ekonomis',
	// 					'active' => true
	// 				),
	// 				'REG'    => array(
	// 					'title'	 => 'Layanan Reguler',
	// 					'active' => true
	// 				),
	// 			)
	// 		)
	// 	)
	// );

	/**
	 * Get active courier and services;
	 * @since 	1.0.0
	 * @access 	private
	 * @var 	false|array
	 */
	// private $available_couriers = false;

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
	 * Register shipment libraries
	 * Hooked via action plugins_loaded, priority 100
	 * @since 	1.2.0
	 * @return 	void
	 */
	public function register_libraries() {

		require_once( SEJOLI_JNE_OFFICIAL_DIR . 'shipments/cod.php');

		$this->libraries['cod-jne']	= new \Sejoli_Jne_Official\ShipmentJNE\CODJNE;
	}

	/**
	 * Add JS Vars for localization
	 * Hooked via sejoli/admin/js-localize-data, priority 10
	 * @since 	1.0.0
	 * @param 	array 	$js_vars 	Array of js vars
	 * @return 	array
	 */
	public function set_localize_js_var(array $js_vars) {

		$js_vars['order']['check_physical']	= add_query_arg([
													'ajaxurl' => add_query_arg([
														'action' => 'sejoli-order-check-if-physical'
													], admin_url('admin-ajax.php')),
													'nonce' => wp_create_nonce('sejoli-order-check-if-physical')
												]);

		$js_vars['get_subdistricts'] = [
			'ajaxurl' => add_query_arg([
					'action' => 'get-subdistricts'
				], admin_url('admin-ajax.php')
			),
			'placeholder' => __('Ketik minimal 3 karakter', 'sejoli-jne-official')
		];

		return $js_vars;
	}

	/**
	 * Get subdistriction options for json
	 * Hooked via action wp_ajax_get-subdistricts
	 * @since  1.0.0
	 * @return json
	 */
	// public function get_json_subdistrict_options() {

	// 	$response = sejoli_jne_get_district_options( $_REQUEST['term'] );

	// 	wp_send_json( $response );
	// }

	/**
	 * Get subdistriction options
	 * Hooked via filter sejoli/shipment/subdistricts, priority 1
	 * @since  1.0.0
	 * @return array
	 */
	// public function get_subdistrict_options($options = array()) {
	// 	$options = [];

	// 	ob_start();
	// 	require SEJOLI_JNE_OFFICIAL_DIR . 'json/subdistrict.json';
	// 	$json_data = ob_get_contents();
	// 	ob_end_clean();

	// 	$subdistricts = json_decode($json_data, true);

	// 	foreach($subdistricts as $data):
	// 		$options[$data['subdistrict_id']] = $data['province'] . ' - ' . $data['type'].' '.$data['city'] . ' - ' . $data['subdistrict_name'];
	// 	endforeach;

	// 	asort($options);

	// 	return $options;
	// }

	/**
	 * Get subdistrict detail
	 * @since 	1.2.0
	 * @since 	1.5.0 		Add conditional to check if subdistrict_id is 0
	 * @param  	integer 	$subdistrict_id 	District ID
	 * @return 	array|null 	District detail
	 */
	public function get_subdistrict_detail($subdistrict_id) {

		if( 0 !== intval($subdistrict_id) ) :

			ob_start();
			require SEJOLI_JNE_OFFICIAL_DIR . 'json/subdistrict.json';
			$json_data = ob_get_contents();
			ob_end_clean();

			$subdistricts        = json_decode($json_data, true);
	        $key                 = array_search($subdistrict_id, array_column($subdistricts, 'subdistrict_id'));
	        $current_subdistrict = $subdistricts[$key];

			return $current_subdistrict;

		endif;

		return 	NULL;
	}

	/**
	 * Delete shipping transient data everytime carbon fields - theme options saved
	 * Hooked via action carbon_fields_theme_options_container_saved, priority 10
	 * @since 	1.4.0
	 * @return 	void
	 */
	public function delete_cache_data() {
		delete_transient('sejolisa-shipment');
	}

	/**
	 * Setup shipment fields for general options
	 * Hooked via fi;ter sejoli/general/fields, priority 40
	 * @since 	1.0.0
	 * @param  	array  $fields 	Plugin option shipment fields in array
	 * @return 	array
	 */
	// public function setup_shipping_fields(array $fields) {

	// 	$shipping_fields = [];

	// 	foreach($this->couriers['domestic'] as $key => $_courier) :

	// 		$main_key = 'shipment_jne_'. $key . '_active';

	// 		$shipping_fields[] = Field::make('checkbox', $main_key, $_courier['label'])
	// 								->set_default_value($_courier['active'])
	// 								->set_help_text($_courier['website'])
	// 								->set_classes('main-title');

	// 		foreach($_courier['services'] as $service => $setting):

	// 			$service_key = sanitize_title($service);
	// 			$shipping_fields[] = Field::make('checkbox', 'shipment_jne_' . $key . '_' . $service_key . '_active', $setting['title'])
	// 									->set_default_value($setting['active'])
	// 									->set_conditional_logic([
	// 										[
	// 											'field'	=> $main_key,
	// 											'value'	=> true
	// 										]
	// 									]);
	// 		endforeach;

	// 	endforeach;

	// 	$shipping_fields = apply_filters('sejoli/shipment/fields', $shipping_fields);

	// 	$fields[] = [
	// 		'title'  => __('Pengiriman COD JNE', 'sejoli-jne-official'),
	// 		'fields' => $shipping_fields
	// 	];

	// 	return $fields;
	// }

    /**
	 * Setup shipment fields for product
	 * Hooked via filter sejoli/product/fields, priority 30
	 * @since  1.0.0	Initialization
	 * @since  1.2.0 	Add ability to modify product shipment fields
	 * @param  array  	$fields
	 * @return array
	 */
	// public function setup_setting_fields(array $fields) {

	// 	$currency = 'Rp. '; // later will be using hook filter;

 //        $conditionals = [
 //            'physical'  => [
 //                [
 //                    'field' => 'product_type',
 //                    'value' => 'physical'
 //                ],[
 //                    'field' => 'shipment_active',
 //                    'value' => true
 //                ]
 //            ]
 //        ];

	// 	$fields['shipping'] = [
	// 		'title'	=> __('Pengiriman', 'sejoli-jne-official'),
	// 		'fields' =>  [
	// 			Field::make( 'separator', 'sep_shipment' , __('Pengaturan Pengiriman', 'sejoli-jne-official'))
	// 				->set_classes('sejoli-with-help')
	// 				->set_help_text('<a href="' . sejolisa_get_admin_help('shipping') . '" class="thickbox sejoli-help">Tutorial <span class="dashicons dashicons-video-alt2"></span></a>'),

 //                Field::make('html',     'html_info_shipment')
 //                    ->set_html('<div class="sejoli-html-message info"><p>'. __('Pengaturan ini hanya <strong>BERLAKU</strong> jika tipe produk adalah produk fisik', 'sejoli-jne-official') . '</p></div>'),

 //                Field::make('checkbox', 'shipment_active', __('Aktifkan pengaturan pengiriman'))
 //                    ->set_option_value('yes')
 //                    ->set_default_value(false)
 //                    ->set_conditional_logic([
 //                        [
 //                            'field' => 'product_type',
 //                            'value' => 'physical'
 //                        ]
 //                    ]),

	// 			Field::make('checkbox', 'shipment_input_own_value', __('Customer tentukan sendiri biaya pengiriman', 'sejoli-jne-official'))
	// 				->set_default_value(false)
	// 				->set_conditional_logic([
	// 					[
	// 						'field' => 'product_type',
	// 						'value' => 'physical'
	// 					]
	// 				]),

 //                Field::make('text', 'shipment_weight', __('Berat barang (dalam gram)', 'sejoli-jne-official'))
 //                    ->set_attribute('type', 'number')
 //                    ->set_attribute('min', 100)
 //                    ->set_required(true)
 //                    ->set_conditional_logic($conditionals['physical']),

	// 			// Field::make('html', 'html_info_shipment_select')
	// 			// 	->set_html("<select name=carbon_fields_compact_input[_shipment_origin]></select>")
	// 			// 	->set_conditional_logic($conditionals['physical'])
	// 			//
	// 			Field::make('select', 'shipment_origin', __('Awal pengiriman', 'sejoli-jne-official'))
	//                 ->set_options(array($this, 'get_subdistrict_options'))
	//                 ->set_required(true)
	//                 ->set_help_text(__('Ketik nama kecamatan untuk pengiriman', 'sejoli-jne-official'))
 //            ]
 //        ];

 //        return $fields;
 //    }

	/**
	 * Add shipping data to product meta
	 * Hooked via filter sejoli/product/meta-data, priority 100
	 * @since 	1.0.0
	 * @param  	WP_Post 	$product
	 * @param  	int     	$product_id
	 * @return 	WP_Post
	 */
	// public function setup_product_meta(\WP_Post $product, int $product_id) {

	// 	$product->shipping = [
	// 		'active'    => boolval(carbon_get_post_meta($product_id, 'shipment_active')),
	// 		'weight'    => intval(carbon_get_post_meta($product_id, 'shipment_weight')),
	// 		'origin'    => intval(carbon_get_post_meta($product_id, 'shipment_origin')),
	// 		'own_value' => boolval(carbon_get_post_meta($product_id, 'shipment_input_own_value')),
	// 	];

	// 	return $product;
	// }

	/**
	 * Set current order needs shipment
	 * Hooked via action sejoli/order/need-shipment, priority 1
	 * @since 1.1.1
	 * @param boolean $need_shipment [description]
	 */
	public function set_order_needs_shipment($need_shipment = false) {
		$this->order_needs_shipment = $need_shipment;
	}

	/**
	 * Validate shipping
	 * Hooked via filter sejoli/checkout/is-shipping-valid, priority 1
	 * @since  1.0.0
	 * @param  bool    	$valid
	 * @param  WP_Post 	$product
	 * @param  array   	$post_data
	 * @param  bool  	$is_calculate	Check if current request is to calculate only or to checkout
	 * @return bool
	 */
	// public function validate_shipping_when_checkout(bool $valid, \WP_Post $product, array $post_data, $is_calculate = false) {

	// 	if('digital' === $product->type && !$this->order_needs_shipment) :
	// 		return $valid;
	// 	endif;

	// 	/**
	// 	 * Check courier data
	// 	 */
	// 	if(isset($post_data['shipment']) && !empty($post_data['shipment']) && 'undefined' !== $post_data['shipment']) :

	// 		list($courier,$service,$cost)	= explode(':::', $post_data['shipment']);

	// 		$this->shipping_data = [
	// 			'courier'     => $courier,
	// 			'service'     => $service,
	// 			'cost'        => floatval($cost),
	// 			'district_id' => intval($post_data['district_id'])
	// 		];

	// 	elseif(isset($post_data['shipping_own_value']) && 'undefined' !== $post_data['shipping_own_value']) :

	// 		$this->shipping_data = [
	// 			'courier'     => 'MANUAL',
	// 			'service'     => 'MANUAL',
	// 			'cost'        => floatval($post_data['shipping_own_value']),
	// 			'district_id' => 0
	// 		];

	// 	else :
	// 		$valid = false;
	// 		sejolisa_set_message( __('Detil pengiriman belum lengkap', 'sejoli-jne-official') );
	// 	endif;

	// 	if(false === $is_calculate) :

	// 		if(!empty($post_data['user_name'])) :
	// 			if(is_array($this->shipping_data)) :
	// 				$this->shipping_data['receiver'] = sanitize_text_field($post_data['user_name']);
	// 			endif;
	// 		else :
	// 			$valid = false;
	// 			sejolisa_set_message( __('Nama penerima belum diisi', 'sejoli-jne-official'));
	// 		endif;

	// 		if(!empty($post_data['user_phone'])) :
	// 			if(is_array($this->shipping_data)) :
	// 				$this->shipping_data['phone'] = sanitize_text_field($post_data['user_phone']);
	// 			endif;
	// 		else :
	// 			$valid = false;
	// 			sejolisa_set_message( __('Nomor telpon penerima belum diisi', 'sejoli-jne-official'));
	// 		endif;

	// 		/**
	// 		 * Check address
	// 		 */
	// 		if(isset($post_data['address']) && !empty($post_data['address'])) :
	// 			if(is_array($this->shipping_data)) :
	// 				$this->shipping_data['address'] = sanitize_textarea_field($post_data['address']);
	// 			endif;
	// 		endif;

	// 	endif;

	// 	return $valid;
	// }

	/**
	 * Get available couriers
	 * Hooked via filter sejoli/shipment/available-couriers, priority 100
	 * @since 	1.0.0
	 * @param  	array 	$available_couriers
	 * @return 	array
	 */
	// public function get_available_couriers($available_couriers = []) {

	// 	if(false === $this->available_couriers) :

	// 		foreach($this->couriers['domestic'] as $key => $_courier) :

	// 			$main_key = 'shipment_jne_'. $key . '_active';
	// 			$active = boolval(carbon_get_theme_option($main_key));

	// 			if(false !== $active) :

	// 				foreach($_courier['services'] as $service => $active) :

	// 					$service_key = sanitize_title($service);
	// 					$service_key = 'shipment_jne_' . $key . '_' . $service_key . '_active';
	// 					$active      = boolval(carbon_get_theme_option($service_key));

	// 					if(false !== $active) :

	// 						if(!isset($available_couriers[$key])) :
	// 							$available_couriers[$key] = array();
	// 						endif;

	// 						$available_couriers[$key][] = $service;

	// 					endif;

	// 				endforeach;
	// 			endif;

	// 		endforeach;

	// 		$this->available_couriers = $available_couriers;

	// 	endif;

	// 	return $this->available_couriers;
	// }

	/**
	 * Get available courier services
	 * Hooked via filter sejoli/shipment/available-courier-services, priority 100
	 * @since 	1.0.0
	 * @param  	array  $services
	 * @return 	array
	 */
	// public function get_available_courier_services($services = array()) {
	// 	$available_couriers = $this->get_available_couriers();

	// 	if(
	// 		false !== $available_couriers &&
	// 		is_array($available_couriers) &&
	// 		0 < count($available_couriers)
	// 	) :

	// 		foreach($available_couriers as $courier => $_services) :
	// 			foreach($_services as $_service) :
	// 				$services[] = $_service;
	// 			endforeach;
	// 		endforeach;

	// 	endif;

	// 	return $services;
	// }

	/**
	 * Calculate shipment cost
	 * Hooked via action sejoli/shipment/calculation, priority 100
	 * @param  	array  $post_data
	 * @return 	void
	 */
	// public function calculate_shipment_cost(array $post_data) {

	// 	$available_couriers = apply_filters('sejoli/shipment/available-couriers', []);
	// 	$getShipmentStatus = carbon_get_post_meta( $post_data['product_id'], 'shipment_cod_jne_active' );
	// 	if($getShipmentStatus == 1) :
	// 		if(
	// 			false !== $available_couriers &&
	// 			is_array($available_couriers) &&
	// 			0 < count($available_couriers)
	// 		) :
	// 			$couriers       = implode(':', array_keys($available_couriers));
	// 			$product        = sejolisa_get_product($post_data['product_id']);
	// 			$product_weight = intval($product->shipping['weight']);
			

	// 			$response       = sejoli_jne_get_shipment_cost([
	// 		        'destination_id' => $post_data['district_id'],
	// 		        'origin_id'      => $product->shipping['origin'],
	// 		        'weight'         => apply_filters('sejoli/product/weight', $product_weight, $post_data ),
	// 		        'courier'        => $couriers,
	// 		        'quantity'       => $post_data['quantity']
	// 		    ]);

	// 			// $response['shipment'] = apply_filters('sejoli/shipment/options', $response['shipment'], $post_data);
	// 			// error_log(print_r($getShipmentStatus, true));

	// 			sejolisa_set_respond($response, 'shipment');
	// 		else :
	// 			sejolisa_set_respond($response, 'shipment');
	// 		endif;
	// 	endif;


	// }

	/**
	 * Set shipment data to order meta,
	 * Hooked via filter sejoli/order/meta-data, priority 100
	 * @since 	1.0.0
	 * @param 	array 	$meta_data
	 * @param 	array  	$order_data
	 * @return  array
	 */
	// public function set_order_meta($meta_data = [], array $order_data) {

	// 	if(false !== $this->shipping_data && is_array($this->shipping_data)) :
	// 		$meta_data['need_shipment'] = true;
	// 		$meta_data['shipping_data'] = $this->shipping_data;
	// 		$meta_data['free_shipping']	= apply_filters('sejoli/order/is-free-shipping', false);
	// 	endif;

	// 	return $meta_data;
	// }

	/**
	 * Add shipping cost to grand total
	 * Hooked via filter sejoli/order/grand-total, priority 300
	 * @since  	1.0.0
	 * @param 	float 	$grand_total
	 * @param 	array 	$post_data
	 * @return 	float
	 */
	// public function add_shipping_cost(float $grand_total, array $post_data) {

	// 	if(false !== $this->shipping_data && is_array($this->shipping_data) && isset($this->shipping_data['cost'])) :
	// 		$grand_total += $this->shipping_data['cost'];
	// 	endif;

	// 	return $grand_total;
	// }

	/**
     * Set shipment value to cart
     * Hooked via filter sejoli/order/cart-detail, 10
     * @since 1.0.0
     * @param array $cart_detail
     * @param array $order_data
     * @return array $cart_detail
     */
  //   public function set_cart_detail(array $cart_detail, array $order_data) {

		// if(false !== $this->shipping_data && is_array($this->shipping_data)) :
		// 	$cart_detail['shipment_fee'] = $this->shipping_data['cost'];

		// endif;

  //       return $cart_detail;
  //   }

	/**
	 * Reduce grand total with shipment if there is any shipping data in order meta
	 * Hooked via filter sejoli/commission/order-grand-total, priority 1
	 * @param  float  $grand_total
	 * @param  array  $order_data
	 * @return float
	 */
	// public function reduce_with_shipping_cost(float $grand_total, array $order_data) {

	// 	if(isset($order_data['meta_data']['shipping_data'])) :
	// 		$grand_total -= floatval($order_data['meta_data']['shipping_data']['cost']);
	// 	endif;

	// 	return $grand_total;
	// }

	/**
	 * Translate order meta shipping data for order detail
	 * Hooked via sejoli/order/detail priority 100
	 * @since 	1.0.0
	 * @param 	array $order_data
	 * @return 	array
	 */
	// public function add_shipping_info_in_order_data(array $order_data) {

	// 	if(isset($order_data['meta_data']['need_shipment']) && true === boolval($order_data['meta_data']['need_shipment'])) :

	// 		$buyer = sejolisa_get_user($order_data['user_id']);

	// 		$order_data['meta_data']['shipping_data'] = wp_parse_args($order_data['meta_data']['shipping_data'],[
	// 			'courier'	=> NULL,
	// 			'address' 	=> NULL,
	// 			'receiver'	=> $buyer->display_name,
	// 			'phone'		=> $buyer->meta->phone
	// 		]);

	// 		$shipping = $order_data['meta_data']['shipping_data'];

	// 		ob_start();
	// 		printf( __('%s %s, ongkos %s', 'sejoli-jne-official'), $shipping['courier'], $shipping['service'], sejolisa_price_format($shipping['cost']) );
	// 		$content = ob_get_contents();
	// 		ob_end_clean();

	// 		$order_data['courier'] = $content;
	// 		$order_data['address'] = $shipping['address'];
	// 		$district              = (isset($shipping['district_id'])) ? $this->get_subdistrict_detail($shipping['district_id']) : NULL;

	// 		$content = '';
	// 		ob_start();

	// 		if(!empty($district)) :
	//             echo "<br />"; printf( __('Kecamatan %s. ', 'sejoli-jne-official'), $district['subdistrict_name'] );
	// 			printf( '%s %s. ', $district['type'], $district['city'] );
	//             printf( __('Provinsi %s', 'sejoli-jne-official'), $district['province'] );
	// 		endif;

	// 		$content = ob_get_contents();
	// 		ob_end_clean();

	// 		$order_data['address'] .= $content;




	// 	endif;

	// 	return $order_data;
	// }

	/**
	 * Display shipping info
	 * Hooked via sejoli/notification/content/order-meta
	 * @param  string $content      	[description]
	 * @param  string $media        	[description]
	 * @param  string $recipient_type   [description]
	 * @param  array  $invoice_data 	[description]
	 * @return string
	 */
	// public function add_shipping_info_in_notification(string $content, string $media, $recipient_type, array $invoice_data) {

	// 	if(
	// 		in_array($recipient_type, ['buyer', 'admin']) &&
	// 		isset($invoice_data['order_data']['meta_data']['need_shipment']) &&
	// 		true === boolval($invoice_data['order_data']['meta_data']['need_shipment'])
	// 	) :
	// 		$shipping  = $invoice_data['order_data']['meta_data']['shipping_data'];
	// 		$meta_data = $invoice_data['order_data']['meta_data'];
	// 		$district  = (isset($shipping['district_id'])) ? $this->get_subdistrict_detail($shipping['district_id']) : NULL;

	// 		$content .= sejoli_get_notification_content(
	// 						'shipment',
	// 						$media,
	// 						array(
	// 							'shipping'  => $shipping,
	// 							'district'	=> $district,
	// 							'meta_data' => $meta_data
	// 						)
	// 					);
	// 	endif;

	// 	return $content;
	// }
}
