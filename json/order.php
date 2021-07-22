<?php
namespace Sejoli_Jne_Official\JSON;

use \WeDevs\ORM\Eloquent\Facades\DB;
use Sejoli_Jne_Official\API\JNE as API_JNE;
use Sejoli_Jne_Official\Model\JNE\Destination as JNE_Destination;

Class Order extends \Sejoli_Jne_Official\JSON
{
    /**
     * Construction
     */
    public function __construct() {

    }

    // /**
    //  * Set user options
    //  * @since   1.0.0
    //  * @return  json
    //  */
    // public function set_for_options() {

    // }

  //   /**
  //    * Set table data
  //    * Hooked via action wp_ajax_sejoli-order-table, priority 1
  //    * @since   1.0.0
  //    * @return  json
  //    */
  //   public function set_for_table() {

		// $table = $this->set_table_args($_POST);

		// $data  = [];

  //       if(isset($_POST['backend']) && current_user_can('manage_sejoli_orders')) :

  //       else :
  //           $table['filter']['user_id'] = get_current_user_id();
  //       endif;

		// $respond = sejolisa_get_orders($table['filter'], $table);

		// if(false !== $respond['valid']) :
		// 	$data = $respond['orders'];
		// endif;

  //       if(class_exists('WP_CLI')) :
  //           __debug([
  //   			'table'           => $table,
  //   			'draw'            => $table['draw'],
  //   			'data'            => $data,
  //   			'recordsTotal'    => $respond['recordsTotal'],
  //   			'recordsFiltered' => $respond['recordsTotal'],
  //   		]);
  //       else :
  //   		echo wp_send_json([
  //   			'table'           => $table,
  //   			'draw'            => $table['draw'],
  //   			'data'            => $data,
  //   			'recordsTotal'    => $respond['recordsTotal'],
  //   			'recordsFiltered' => $respond['recordsTotal'],
  //   		]);
  //       endif;
		// exit;
  //   }

    // /**
    //  * Get single order data
    //  * Hooked via wp_ajax_sejoli-order_detail, priority 1
    //  * @since   1.0.0
    //  * @return  json
    //  */
    // public function get_detail() {

    //     $data = false;

    //     if(wp_verify_nonce($_GET['nonce'], 'sejoli-order-detail')) :
    //         $response = sejolisa_get_order(['ID' => $_GET['order_id'] ]);
    //         if(false !== $response['valid']) :
    //             $data = $response['orders'];
    //         endif;
    //     endif;

    //     echo wp_send_json($data);
    //     exit;
    // }

    // /**
    //  * Check if given order product is physical or not
    //  * Hooked via wp_ajax_sejoli-order-shipping, priority 1
    //  * @since   1.0.0
    //  * @return  json
    //  */
    // public function check_for_shipping() {

    //     $data = false;

    //     if(wp_verify_nonce($_POST['nonce'], 'sejoli-order-shipping')) :

    //         $response = sejolisa_get_orders_with_physical_product($_POST['orders']);

    //         if(false !== $response['valid']) :

    //             $orders = $response['orders'];
    //             $temp = [];

    //             foreach($orders as $i => $order) :
    //                 $temp[$i]                = $order;
    //                 $temp[$i]->meta_data     = $meta_data = maybe_unserialize($order->meta_data);
    //                 $temp[$i]->need_shipment = (isset($meta_data['need_shipment'])) ? boolval($meta_data['need_shipment']) : false;
    //                 $temp[$i]->shipping_data = isset($meta_data['shipping_data']) ? $meta_data['shipping_data'] : false;
    //             endforeach;

    //             $response['orders'] = $temp;

    //         endif;

    //         $data = $response;
    //     endif;

    //     echo wp_send_json($data);
    //     exit;
    // }
     
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
     * Process Pickup Generate Resi COD
     * Hooked via wp_ajax_sejoli-order-pickup-generate-resi, priority 1
     * @since   1.0.0
     * @return  json
     */
    public function generate_pickup_resi() {
        $params = wp_parse_args( $_POST, array(
            'invoice_number' => NULL,
            'shipperName'    => NULL,
            'shipperAddr1'   => NULL,
            'shipperAddr2'   => NULL,
            'shipperCity'    => NULL,
            'shipperRegion'  => NULL,
            'shipperZip'     => NULL,
            'shipperPhone'   => NULL,
            'receiverName'   => NULL,
            'receiverAddr1'  => NULL,
            'receiverAddr2'  => NULL,
            'receiverCity'   => NULL,
            'receiverRegion' => NULL,
            'receiverZip'    => NULL,
            'receiverPhone'  => NULL,
            'qty'            => NULL,
            'weight'         => NULL,
            'goodsDesc'      => NULL,
            'goodsValue'     => 1000,
            'goodsType'      => 1,
            'insurance'      => "N",
            'origin'         => "CGK10000",
            'destination'    => "BDO10000",
            'service'        => NULL,
            'codflag'        => "YES",
            'codAmount'      => NULL,
            'nonce'          => NULL
        ));

        $response = sejolisa_get_order(['ID' => $params['invoice_number'] ]);
        if(false !== $response['valid']) :

            $data = $response['orders'];

            $product_id = $data['product_id'];
            $user_id = $data['user_id'];
            $payment_gateway = $data['payment_gateway'];
            $qty = $data['quantity'];
            $weight = $data['product']->cod['cod-weight'];
            $weight_cost = (int) round((intval($qty) * $weight) / 1000);
            $weight_cost = (0 === $weight_cost) ? 1 : $weight_cost;
            $type_product = $data['type'];
            $shipping_name = $data['meta_data']['shipping_data']['service'];
            if($shipping_name == "JNE REG") {
                $shipping_service = "REG";
            } elseif($shipping_name == "JNE OKE") {
                $shipping_service = "OKE";
            } else {
                $shipping_service = "JTR";
            }
            $receiver_destination_id = $data['meta_data']['shipping_data']['district_id'];
            $receiver_destination_city = $this->get_subdistrict_detail($receiver_destination_id);
            $receiver_destination = JNE_Destination::where( 'city_name', $receiver_destination_city['city'] )->first();
            $receiver_name = $data['meta_data']['shipping_data']['receiver'];
            $receiver_address = $data['meta_data']['shipping_data']['address'];
            $receiver_zip = '0000';
            $receiver_phone = $data['meta_data']['shipping_data']['phone'];
            $shipping_cost = $data['meta_data']['shipping_data']['cost'];
            $product_name = $data['product']->post_title;
            $product_price = $data['product']->price;
            $product_type = $data['product']->type;
            $shipper_origin_id = $data['product']->cod['cod-origin'];
            $shipper_origin_city = $this->get_subdistrict_detail($shipper_origin_id);
            $shipper_origin = JNE_Destination::where( 'district_name', $shipper_origin_city['subdistrict_name'] )->first(); 
            $shipper_name = get_bloginfo('name');
            $shipper_address = $data['meta_data']['shipping_data']['address'];
            $shipper_zip = '0000';
            $shipper_phone = '000000000000';
            $params['shipperName'] = $shipper_name;
            $params['shipperAddr1'] = $shipper_origin_city['subdistrict_name'];
            $params['shipperAddr2'] = $shipper_origin_city['subdistrict_name'];
            $params['shipperCity'] = $shipper_origin_city['type'].' '.$shipper_origin_city['city'];
            $params['shipperRegion'] = $shipper_origin_city['province'];
            $params['shipperZip'] = $shipper_zip;
            $params['shipperPhone'] = $shipper_phone;
            $params['receiverName'] = $receiver_name;
            $params['receiverAddr1'] = $receiver_address;
            $params['receiverAddr2'] = $receiver_address;
            $params['receiverCity'] = $receiver_destination_city['type'].' '.$receiver_destination_city['city'];
            $params['receiverRegion'] = $receiver_destination_city['province'];
            $params['receiverZip'] = $receiver_zip;
            $params['receiverPhone'] = $receiver_phone;
            $params['qty'] = $qty;
            $params['weight'] = $weight_cost;
            $params['goodsDesc'] = $product_name;
            // $params['origin'] = $shipper_origin->code;
            // $params['destination'] = $receiver_destination->code;
            $params['service'] = $shipping_service;
            $params['codAmount'] = $shipping_cost;
 
        endif;

        $respond  = [
            'valid'   => false,
            'message' => NULL
        ];

        if( wp_verify_nonce( $params['nonce'], 'sejoli-order-pickup-generate-resi') ) :

            unset( $params['nonce'] );

            $do_update = API_JNE::set_params()->get_airwaybill( $params['invoice_number'], $params['shipperName'], $params['shipperAddr1'], $params['shipperAddr2'], $params['shipperCity'], $params['shipperRegion'], $params['shipperZip'], $params['shipperPhone'], $params['receiverName'], $params['receiverAddr1'], $params['receiverAddr2'], $params['receiverCity'], $params['receiverRegion'], $params['receiverZip'], $params['receiverPhone'], $params['qty'], $params['weight'], $params['goodsDesc'], $params['goodsValue'], $params['goodsType'], $params['insurance'], $params['origin'], $params['destination'], $params['service'], $params['codflag'], $params['codAmount'] );

            if ( ! is_wp_error( $do_update ) ) {

                $respond['valid']  = true;

            } else {

                $respond['message'] = $do_update->get_error_message();
            }

        endif;
        
        $number_resi = $do_update[0]->cnote_no;
        error_log(print_r($do_update, true));

        echo wp_send_json( $number_resi );
    }

    /**
     * Process Pickup COD
     * Hooked via wp_ajax_sejoli-order-pickup, priority 1
     * @since   1.0.0
     * @return  json
     */
    public function process_pickup() {
        $data = false;

        $post_data = wp_parse_args($_POST,[
            'nonce' => NULL,
            'data'  => []
        ]);

        if(wp_verify_nonce($post_data['nonce'], 'sejoli-order-input-resi')) :

            if(isset($post_data['data']['order_resi']) && 0 < count($post_data['data']['order_resi'])) :

                foreach($post_data['data']['order_resi'] as $order_id => $resi_number) :

                    $resi_number = sanitize_text_field(trim($resi_number));

                    if(!empty($resi_number)) :

                        $response = sejolisa_update_order_meta_data(
                            $order_id,
                            [
                                'shipping_data' => [
                                    'resi_number' => $resi_number
                                ]
                            ]);

                        do_action('sejoli/order/update-status', [
                            'ID'          => $order_id,
                            'status'      => 'shipping'
                        ]);

                        if(false !== $response['valid']) :
                            $data[] = sprintf( __('Order %s updated to shipping with resi number %s', 'sejoli'), $order_id, $resi_number);
                        endif;
                    endif;

                endforeach;

            endif;

        endif;

        echo wp_send_json($data);
        exit;
    }

    // /**
    //  * Prepare for exporting order data
    //  * Hooked via wp_ajax_sejoli-order-export-prepare, priority 1
    //  * @since   1.0.2
    //  * @return  void
    //  */
    // public function prepare_for_exporting() {

    //     $response = [
    //         'url'  => admin_url('/'),
    //         'data' => [],
    //     ];

    //     $post_data = wp_parse_args($_POST,[
    //         'data'    => array(),
    //         'nonce'   => NULL,
    //         'backend' => false
    //     ]);

    //     if(wp_verify_nonce($post_data['nonce'], 'sejoli-order-export-prepare')) :

    //         $request = array();

    //         foreach($post_data['data'] as $_data) :
    //             if(!empty($_data['val'])) :
    //                 $request[$_data['name']]    = $_data['val'];
    //             endif;
    //         endforeach;

    //         if(false !== $post_data['backend']) :
    //             $request['backend'] = true;
    //         endif;

    //         $response['data'] = $request;
    //         $response['url']  = wp_nonce_url(
    //                                 add_query_arg(
    //                                     $request,
    //                                     site_url('/sejoli-ajax/sejoli-order-export')
    //                                 ),
    //                                 'sejoli-order-export',
    //                                 'sejoli-nonce'
    //                             );
    //     endif;

    //     echo wp_send_json($response);
    //     exit;
    // }

   // /*
   //  * Check order for bulk notification
   //  * Hooked via action wp_ajax_sejoli-bulk-notification-order, priority 1
   //  * @return [type] [description]
   //  */
   // public function check_order_for_bulk_notification() {

   //     $data      = false;
   //     $post_data = wp_parse_args($_GET,[
   //         'nonce'      => false,
   //         'product'    => NULL,
   //         'date-range' => date('Y-m-d',strtotime('-30day')) . ' - ' . date('Y-m-d'),
   //         'status'     => 'on-hold'
   //     ]);

   //     if(
   //         wp_verify_nonce($post_data['nonce'], 'sejoli-bulk-notification-order') &&
   //         !empty($post_data['product'])
   //     ) :

   //         $data = sejolisa_get_orders_for_bulks([
   //             'date-range' => $post_data['date-range'],
   //             'product_id' => $post_data['product'],
   //             'status'     => $post_data['status']
   //         ]);
   //     endif;

   //     echo wp_send_json($data);

   //     exit;
   // }

   // /**
   //  * Get order data for confirmation process
   //  * Hooked via action sejoli_ajax_check-order-for-confirmation, priority 1
   //  * @since    1.1.6
   //  * @since    1.5.0   Enchance the confirmation process
   //  * @return   void
   //  */
   // public function get_order_confirmation() {

   //     $response = array(
   //         'valid'   => false,
   //         'order'   => null,
   //         'message' => __('Order berdasarkan invoice yang anda masukkan tidak ditemukan', 'sejoli')
   //     );

   //     $post_data = wp_parse_args($_GET, array(
   //         'order_id'          => 0,
   //         'sejoli_ajax_nonce' => NULL
   //     ));

   //     if(sejoli_ajax_verify_nonce('sejoli-check-order-for-confirmation') && !empty($post_data['order_id'])) :

   //         $order_id = trim(preg_replace('/[^0-9]/', '', $post_data['order_id']));
   //         $order_id = str_replace('INV','', $order_id);
   //         $order_response = sejolisa_get_order(['ID' => $order_id]);

   //         // Order not found by invoice ID, then we will check by the amount
   //         if(false === $order_response['valid']) :
   //             $order_response = sejolisa_get_order_by_amount($order_id);
   //         endif;

   //         if(false !== $order_response['valid']) :

   //              switch ($order_response['orders']['status']) :

   //                  case 'in-progress' :
   //                  case 'shipping' :
   //                  case 'completed' :
   //                      $response['message'] = __('Order berdasarkan invoice yang anda masukkan sudah diproses', 'sejoli');
   //                      break;

   //                  case 'refunded' :
   //                  case 'cancelled' :
   //                      $response['message'] = __('Order berdasarkan invoice yang anda masukkan sudah dibatalkan', 'sejoli');
   //                      break;

   //                  case 'on-hold' :
   //                  case 'payment-confirm' :
   //                      $product_id = intval($order_response['orders']['product_id']);
   //                      $product    = get_post($product_id);

   //                      $response['valid']  = true;
   //                      $response['order']  = array(
   //                          'invoice_id' => $order_response['orders']['ID'],
   //                          'product_id' => $product_id,
   //                          'product'    => $product->post_title,
   //                          'total'      => $order_response['orders']['grand_total']
   //                      );
   //                      $response['message']= __('Order ditemukan', 'sejoli');
   //                      break;

   //                  endswitch;

   //              endif;
   //       endif;

   //       echo wp_send_json($response);
   //     exit;
   // }
   
}
