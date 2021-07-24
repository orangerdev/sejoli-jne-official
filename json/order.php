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

    /**
     * Set user options
     * @since   1.0.0
     * @return  json
     */
    public function set_for_options() {

    }

    /**
     * Set table data
     * Hooked via action wp_ajax_sejoli-order-table, priority 1
     * @since   1.0.0
     * @return  json
     */
    public function set_for_table() {

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

            $data            = $response['orders'];
            $product_id      = $data['product_id'];
            $user_id         = $data['user_id'];
            $payment_gateway = $data['payment_gateway'];
            $qty             = $data['quantity'];
            $weight          = $data['product']->cod['cod-weight'];
            $weight_cost     = (int) round((intval($qty) * $weight) / 1000);
            $weight_cost     = (0 === $weight_cost) ? 1 : $weight_cost;
            $type_product    = $data['type'];
            $shipping_name   = $data['meta_data']['shipping_data']['service'];
            if($shipping_name == "JNE REG") {
                $shipping_service = "REG";
            } elseif($shipping_name == "JNE OKE") {
                $shipping_service = "OKE";
            } else {
                $shipping_service = "JTR";
            }
            $receiver_destination_id   = $data['meta_data']['shipping_data']['district_id'];
            $receiver_destination_city = $this->get_subdistrict_detail($receiver_destination_id);
            $receiver_destination      = JNE_Destination::where( 'city_name', $receiver_destination_city['city'] )->first();
            $receiver_name             = $data['meta_data']['shipping_data']['receiver'];
            $receiver_address          = $data['meta_data']['shipping_data']['address'];
            $receiver_zip              = '0000';
            $receiver_phone            = $data['meta_data']['shipping_data']['phone'];
            $shipping_cost             = $data['meta_data']['shipping_data']['cost'];
            $product_name              = $data['product']->post_title;
            $product_price             = $data['product']->price;
            $product_type              = $data['product']->type;
            $shipper_origin_id         = $data['product']->cod['cod-origin'];
            $shipper_origin_city       = $this->get_subdistrict_detail($shipper_origin_id);
            $shipper_origin            = JNE_Destination::where( 'district_name', $shipper_origin_city['subdistrict_name'] )->first(); 
            $shipper_name              = carbon_get_theme_option( 'sejoli_store_name' );
            $shipper_address           = $data['meta_data']['shipping_data']['address'];
            $shipper_zip               = '0000';
            $shipper_phone             = carbon_get_theme_option( 'sejoli_store_phone' );
            $params['shipperName']     = $shipper_name;
            $params['shipperAddr1']    = $shipper_origin_city['subdistrict_name'];
            $params['shipperAddr2']    = $shipper_origin_city['subdistrict_name'];
            $params['shipperCity']     = $shipper_origin_city['type'].' '.$shipper_origin_city['city'];
            $params['shipperRegion']   = $shipper_origin_city['province'];
            $params['shipperZip']      = $shipper_zip;
            $params['shipperPhone']    = $shipper_phone;
            $params['receiverName']    = $receiver_name;
            $params['receiverAddr1']   = $receiver_address;
            $params['receiverAddr2']   = $receiver_address;
            $params['receiverCity']    = $receiver_destination_city['type'].' '.$receiver_destination_city['city'];
            $params['receiverRegion']  = $receiver_destination_city['province'];
            $params['receiverZip']     = $receiver_zip;
            $params['receiverPhone']   = $receiver_phone;
            $params['qty']             = $qty;
            $params['weight']          = $weight_cost;
            $params['goodsDesc']       = $product_name;
            // $params['origin']          = $shipper_origin->code;
            // $params['destination']     = $receiver_destination->code;
            $params['service']         = $shipping_service;
            $params['codAmount']       = $shipping_cost;
 
        endif;

        error_log(print_r($params, true));

        $respond = [
            'valid'   => false,
            'message' => NULL
        ];

        if( wp_verify_nonce( $params['nonce'], 'sejoli-order-pickup-generate-resi') ) :

            unset( $params['nonce'] );

            $do_update = API_JNE::set_params()->get_airwaybill( $params['invoice_number'], $params['shipperName'], $params['shipperAddr1'], $params['shipperAddr2'], $params['shipperCity'], $params['shipperRegion'], $params['shipperZip'], $params['shipperPhone'], $params['receiverName'], $params['receiverAddr1'], $params['receiverAddr2'], $params['receiverCity'], $params['receiverRegion'], $params['receiverZip'], $params['receiverPhone'], $params['qty'], $params['weight'], $params['goodsDesc'], $params['goodsValue'], $params['goodsType'], $params['insurance'], $params['origin'], $params['destination'], $params['service'], $params['codflag'], $params['codAmount'] );

            if ( ! is_wp_error( $do_update ) ) {

                $respond['valid']  = true;
                $number_resi = $do_update[0]->cnote_no;

            } else {

                $respond['message'] = $do_update->get_error_message();
            }

        endif;
        
        echo wp_send_json( $number_resi );

    }

    /**
     * Create Updating Status Order to Completed Based on Shipment Status is Delivered Cron Job
     *
     * @since 1.0.0
     */
    public function sejoli_update_status_cron_schedules($schedules) {

        $schedules['order_status_to_completed'] = array(
            'interval' => 300, 
            'display'  => 'Update Status Order to Completed Once every 5 minutes'
        );
        return $schedules;

    }

    /**
     * Set Schedule Event for Updating Status Order to Complete Based on Shipping Status is Delivered Cron Job
     *
     * @since 1.0.0
     */
    public function schedule_update_order_to_complete_based_on_shipment_status() {

        // Schedule an action if it's not already scheduled
        if ( ! wp_next_scheduled( 'update_status_order_to_completed' ) ) {
            wp_schedule_event( time(), 'order_status_to_completed', 'update_status_order_to_completed' );
        }

    }

    /**
     * Create Updating Status Order to Complete Based on Shipping Status is Delivered Functiona
     *
     * @since    1.0.0
     */
    public function update_status_order_to_completed_based_on_shipment_status() {

        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sejolisa_orders WHERE status = 'shipping'");
        
        // Loop through each order post object
        foreach( $results as $result ){

            $order_id          = $result->ID; // The Order ID
            $meta_data         = unserialize($result->meta_data);
            $shipping_number   = $meta_data['shipping_data']['resi_number'];
            $shipment_tracking = API_JNE::set_params()->get_tracking( $shipping_number );

            if($shipment_tracking->cnote->pod_status == "DELIVERED"){

                // Process updating order status
                $status = "completed";
                do_action('sejoli/order/update-status', [
                    'ID'     => 37, // $order_id,
                    'status' => $status
                ]);

            }

        }

    }

}