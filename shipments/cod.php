<?php

namespace Sejoli_Jne_Official\ShipmentJNE;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use \WeDevs\ORM\Eloquent\Facades\DB;
use Sejoli_Jne_Official\Model\JNE\Destination as JNE_Destination;
use Sejoli_Jne_Official\Model\JNE\Tariff as JNE_Tariff;
use Sejoli_Jne_Official\API\JNE as API_JNE;

class CODJNE {

    /**
     * Construction
     * @since   1.2.0
     */
    public function __construct() {

        add_filter( 'sejoli/product/fields',    array($this, 'set_product_shipping_fields'), 35);
        add_filter( 'sejoli/shipment/options',  array($this, 'set_shipping_options'),        10, 2);
        add_action( 'sejoli/product/meta-data', array($this, 'setup_product_cod_meta'),      10, 2);

    }

    /**
     * Check if district in cities
     * @since   1.2.0
     * @param   int     $district_id    District ID
     * @param   array   $cities         All City IDs
     * @return  boolean
     */
    protected function check_if_subdistrict_in_cities(int $subdistrict_id, array $cities) {

        $is_in_cities = false;

        ob_start();
		
        require SEJOLI_JNE_OFFICIAL_DIR . 'json/subdistrict.json';
		$json_data = ob_get_contents();

		ob_end_clean();

		$subdistricts        = json_decode($json_data, true);
        $key                 = array_search($subdistrict_id, array_column($subdistricts, 'subdistrict_id'));
        $current_subdistrict = $subdistricts[$key];

        if( in_array( $current_subdistrict['city_id'], $cities) ) :
            return true;
        endif;

        return $is_in_cities;
    }

    /**
	 * Get city options
	 * @since 	1.2.0
	 * @param  	array  $options 	City options
	 * @return 	array
	 */
	public function get_city_options($options = array()) {

		$options = [];

		ob_start();

		require SEJOLI_JNE_OFFICIAL_DIR . 'json/city.json';
		$json_data = ob_get_contents();
		
        ob_end_clean();

		$subdistricts = json_decode($json_data, true);

		foreach($subdistricts as $data):
			$options[$data['city_id']] = $data['province'] . ' - ' . $data['type'].' '.$data['city_name'] ;
		endforeach;

		asort($options);

		return $options;

	}

    /**
     * Add JS Vars for localization
     * Hooked via sejoli/admin/js-localize-data, priority 10
     * @since   1.0.0
     * @param   array   $js_vars    Array of js vars
     * @return  array
     */
    public function set_localize_js_var(array $js_vars) {

        $js_vars['order']['check_physical'] = add_query_arg([
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
    public function get_json_subdistrict_options() {

        $response = sejoli_jne_get_district_options( $_REQUEST['term'] );
        wp_send_json( $response );

    }

    /**
     * Get subdistriction options
     * Hooked via filter sejoli/shipment/subdistricts, priority 1
     * @since  1.0.0
     * @return array
     */
    public function get_subdistrict_options($options = array()) {

        $options = [];

        ob_start();
        
        require SEJOLI_JNE_OFFICIAL_DIR . 'json/subdistrict.json';
        $json_data = ob_get_contents();

        ob_end_clean();

        $subdistricts = json_decode($json_data, true);

        foreach($subdistricts as $data):
            $options[$data['subdistrict_id']] = $data['province'] . ' - ' . $data['type'].' '.$data['city'] . ' - ' . $data['subdistrict_name'];
        endforeach;

        asort($options);

        return $options;

    }

    /**
     * Get subdistrict detail
     * @since   1.2.0
     * @since   1.5.0       Add conditional to check if subdistrict_id is 0
     * @param   integer     $subdistrict_id     District ID
     * @return  array|null  District detail
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

        return  NULL;

    }

    /**
     * Add COD shipping product fields
     * @since   1.2.0
     * @param   array   $product_fields     Current product fields
     * @return  array
     */
    public function set_product_shipping_fields($product_fields) {

        $fields = array(

            Field::make('separator', 'sep_sejoli_shipment_code_jne', __('Cash on Delivery (COD JNE)', 'sejoli-jne-official'))
                ->set_classes('sejoli-with-help')
                ->set_help_text('<a href="' . sejolisa_get_admin_help('affiliate-link') . '" class="thickbox sejoli-help">Tutorial <span class="dashicons dashicons-video-alt2"></span></a>')
                ->set_conditional_logic(array(
                    array(
                        'field' => 'product_type',
                        'value' => 'physical'
                    ), array(
                        'field' => 'shipment_active',
                        'value' => true
                    )
                )),

            Field::make( 'checkbox', 'shipment_cod_jne_active', __('Aktifkan COD JNE', 'sejoli-jne-official')),
            
            Field::make( "multiselect", "shipment_cod_jne_services", __('Layanan JNE', 'sejoli-jne-official') )
                ->add_options( array(
                    'cod_jne_service_reg' => 'REG',
                    'cod_jne_service_oke' => 'OKE',
                    'cod_jne_service_jtr' => 'JTR',
                ))
                ->set_conditional_logic(array(
                    array(
                        'field' => 'shipment_cod_jne_active',
                        'value' => true
                    ),
                    array(
                        'field' => 'product_type',
                        'value' => 'physical'
                    )
                )),

            Field::make('text', 'shipment_cod_jne_weight', __('Berat barang (dalam gram)', 'sejoli-jne-official'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', 100)
                ->set_required(true)
                ->set_conditional_logic(array(
                    array(
                        'field' => 'shipment_cod_jne_active',
                        'value' => true
                    ),
                    array(
                        'field' => 'product_type',
                        'value' => 'physical'
                    )
                )),

            Field::make('select', 'shipment_cod_jne_origin', __('Awal pengiriman', 'sejoli-jne-official'))
                ->set_options(array($this, 'get_subdistrict_options'))
                ->set_required(true)
                ->set_conditional_logic(array(
                    array(
                        'field' => 'shipment_cod_jne_active',
                        'value' => true
                    ),
                    array(
                        'field' => 'product_type',
                        'value' => 'physical'
                    )))
                ->set_help_text(__('Ketik nama kecamatan untuk pengiriman', 'sejoli-jne-official')),

        );

        $product_fields['shipping']['fields'] = array_merge( $product_fields['shipping']['fields'], $fields );

        return $product_fields;

    }

    /**
     * Generate options for origin dropdown
     *
     * @since 1.0.0
     *
     * @return array
     */
    private function get_jne_services(int $product_id) {

        $services = array();

        $jne_services = carbon_get_post_meta($product_id, 'shipment_cod_jne_services');

        foreach ( $jne_services as $jne_service ) {

            if( $jne_service == 'cod_jne_service_reg' ) {
                $services[] = 'REG19';
            }

            if( $jne_service == 'cod_jne_service_oke' ) {
                $services[] = 'OKE19';
            }

            if( $jne_service == 'cod_jne_service_jtr' ) {
                $codes    = array( 'JTR18', 'JTR250', 'JTR<150', 'JTR>250' );
                $services = array_merge( $services, $codes );
            }

        }

        return $services;

    }

    /**
     * Add shipping data to product meta
     * Hooked via filter sejoli/product/meta-data, priority 100
     * @since   1.0.0
     * @param   WP_Post     $product
     * @param   int         $product_id
     * @return  WP_Post
     */
    public function setup_product_cod_meta(\WP_Post $product, int $product_id) {

        $product->cod = [
            'cod-active' => boolval(carbon_get_post_meta($product_id, 'shipment_cod_jne_active')),
            'cod-weight' => intval(carbon_get_post_meta($product_id, 'shipment_cod_jne_weight')),
            'cod-origin' => carbon_get_post_meta($product_id, 'shipment_cod_jne_origin'),
        ];

        return $product;

    }

    /**
     * Get tariff object
     *
     * @since   1.0.0
     *
     * @param   $origin         origin object to find
     * @param   $destination    destination object to find
     *
     * @return  (Object|false)  returns an object on true, or false if fail
     */
    private function get_tariff_info( $origin, $destination, $weight ) {

        $get_tariff = JNE_Tariff::where( 'jne_origin_id', $origin->ID )
                        ->where( 'jne_destination_id', $destination->ID )
                        ->first();

        if( ! $get_tariff ) {
            // $req_tariff_data = API_JNE::set_params()->get_tariff( $origin->code, $destination->code, $weight );
            $req_tariff_data = API_JNE::set_params()->get_tariff( 'CGK10000', 'BDO10000', $weight );

            if( is_wp_error( $req_tariff_data ) ) {
                return false;
            }

            $get_tariff                     = new JNE_Tariff();
            $get_tariff->jne_origin_id      = $origin->ID;
            $get_tariff->jne_destination_id = $destination->ID;
            $get_tariff->tariff_data        = $req_tariff_data;

            if( ! $get_tariff->save() ) {
                return false;
            }
        }

        return $get_tariff;

    }

    /**
     * Set COD shipping options
     * @since   1.2.0
     * @param   array $shipping_options     Current shipping options
     * @param   array $post_data            Post data options
     * @return  array
     */
    public function set_shipping_options($shipping_options, array $post_data) {

        $product_id    = intval($post_data['product_id']);
        $is_cod_active = boolval(carbon_get_post_meta( $product_id, 'shipment_cod_jne_active' ));

        if(false !== $is_cod_active) :

            $cod_origin           = carbon_get_post_meta( $product_id, 'shipment_cod_jne_origin');
            $cod_origin_city      = $this->get_subdistrict_detail($cod_origin);
            $cod_destination_city = $this->get_subdistrict_detail($post_data['district_id']);
            $is_cod_locally       = boolval(carbon_get_post_meta($product_id, 'shipment_cod_jne_cover'));
            $add_options          = true;
            $fee_title            = '';
            $get_origin           = JNE_Destination::where( 'city_name', $cod_origin_city['city'] )->first();
            $get_destination      = JNE_Destination::where( 'district_name', $cod_destination_city['subdistrict_name'] )->first(); 
            $product              = sejolisa_get_product($post_data['product_id']);
            $product_weight       = intval($product->shipping['weight']);
            $weight_cost          = (int) round((intval($post_data['quantity']) * $product_weight) / 1000);
            $weight_cost          = (0 === $weight_cost) ? 1 : $weight_cost;
            $tariff               = $this->get_tariff_info( $get_origin, $get_destination, $weight_cost );

            if(true === $is_cod_locally) :
                
                $city_cover  = carbon_get_post_meta( $product_id, 'shipment_cod_jne_city');
                $district_id = intval($post_data['district_id']);
                $add_options = $this->check_if_subdistrict_in_cities($district_id, $city_cover);
            
            endif;

            if(true === $add_options) :

                if( ! $tariff ) {
                    return false;
                }

                if( is_array( $tariff->tariff_data ) && count( $tariff->tariff_data ) > 0 ) {

                    foreach ( $tariff->tariff_data as $rate ) {

                        if( \in_array( $rate->service_code, $this->get_jne_services($product_id) ) ) {
                            
                            $price = $rate->price * $weight_cost;

                            if($rate->service_display == 'OKE'){
                                $cod_title = 'JNE '.$rate->service_display. __(' (Ongkos Kirim Ekonomis)', 'sejoli-jne-official');
                                $key_title = 'JNE '.$rate->service_display;
                                $fee_title = ' - ' . sejolisa_price_format($price). ', (COD - estimasi 2-3 Hari)';
                            }
                            elseif($rate->service_display == 'REG'){
                                $cod_title = 'JNE '.$rate->service_display. __(' (Layanan Reguler)', 'sejoli-jne-official');
                                $key_title = 'JNE '.$rate->service_display;
                                $fee_title = ' - ' . sejolisa_price_format($price). ', (COD - estimasi 1-2 Hari)';
                            }
                            else{
                                $cod_title = 'JNE '.$rate->service_display. __(' (Layanan Pengiriman Truk)', 'sejoli-jne-official');
                                $key_title = 'JNE '.$rate->service_display;
                                $fee_title = ' - ' . sejolisa_price_format($price). ', (COD - estimasi 3-4 Hari)';
                            }
                            
                            $key_options                    = 'COD:::'.$key_title.':::' . sanitize_title($price);
                            $shipping_options[$key_options] = $cod_title . $fee_title;

                        }
                    }
                }

            endif;

        endif;

        return $shipping_options;

    }

}
