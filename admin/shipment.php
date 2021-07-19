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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version 	   = $version;

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
	 * Set current order needs shipment
	 * Hooked via action sejoli/order/need-shipment, priority 1
	 * @since 1.1.1
	 * @param boolean $need_shipment [description]
	 */
	public function set_order_needs_shipment($need_shipment = false) {

		$this->order_needs_shipment = $need_shipment;
	
	}

}
