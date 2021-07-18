<?php
namespace Sejoli_Jne_Official\Model;

Class ShipmentJNE extends \Sejoli_Jne_Official\Model
{
    /**
     * @since   1.5.3.
     */
    static protected $api_key     = array(
        0 => 'c1bc5e2b11ab236bf4b4988ead182b59'
    );

    static protected $origin      = null;
    static protected $destination = null;
    static protected $weight      = null; // weight per single product
    static protected $quantity    = 1;
    static protected $courier     = null;

    /**
     * Reset all property data
     * @since   1.0.0
     * @access  public
     */
    static public function reset() {
        self::$origin      = null;
        self::$destination = null;
        self::$weight      = null; // weight per single product
        self::$quantity    = 1;
        self::$courier     = null;

        return new static;
    }

    /**
     * Set district origin id
     * @since   1.0.0
     * @access  public
     */
    static public function set_origin($origin) {
        self::$origin = $origin;
        return new static;
    }

    /**
     * Set district destination id
     * @since   1.0.0
     * @access  public
     */
    static public function set_destination($destination) {
        self::$destination = $destination;
        return new static;
    }

    /**
     * Set weight per single product
     * @since   1.0.0
     * @access  public
     */
    static public function set_weight($weight) {
        self::$weight = intval($weight);
        return new static;
    }

    /**
     * Set product quantity
     * @since   1.0.0
     * @access  public
     */
    static public function set_quantity($quantity) {
        self::$quantity = intval($quantity);
        return new static;
    }

    /**
     * Set courier
     * @since   1.0.0
     * @access  public
     */
    static public function set_courier($courier) {
        self::$courier = $courier;
        return new static;
    }

    /**
     * Validate all data
     * @since   1.0.0
     * @access  protected
     */
    static protected function validate() {

        if(empty(self::$origin)) :
            self::set_valid(false);
            self::set_message(__('Asal pengiriman belum diisi', 'sejoli-jne-official'));
        endif;

        if(empty(self::$destination)) :
            self::set_valid(false);
            self::set_message(__('Tujuan pengiriman belum diisi', 'sejoli-jne-official'));
        endif;

        if(empty(self::$courier)) :
            self::set_valid(false);
            self::set_message(__('Kurir pengiriman belum dipilih', 'sejoli-jne-official'));
        endif;

        if(0 === self::$weight) :
            self::set_valid(false);
            self::set_message(__('Berat barang tidak benar', 'sejoli-jne-official'));
        endif;

        if(0 === self::$quantity) :
            self::set_valid(false);
            self::set_message(__('Jumlah barang tidak benar', 'sejoli-jne-official'));
        endif;
    }

    /**
     * Get temporary shipment data
     * @since   1.0.0
     * @access  protected
     * @return  false|array
     */
    static protected function get_temporary_data() {

        $shipment_data = get_transient('sejolisa-shipment');

        if(false !== $shipment_data) :
            if(isset($shipment_data[self::$origin]) && isset($shipment_data[self::$origin][self::$destination])) :
                return $shipment_data[self::$origin][self::$destination];
            endif;
        endif;

        return false;
    }

    /**
     * Set temporary shipment data
     * @since   1.0.0
     * @access  protected
     * @return  false|array
     */
    static protected function set_temporary_data($shipment_data) {

        $all_shipment_data = get_transient('sejolisa-shipment');

        if(false === $all_shipment_data) :
            $all_shipment_data = [];
        endif;

        if(!isset($all_shipment_data[self::$origin])) :
            $all_shipment_data[self::$origin] = [];
        endif;

        if(!isset($all_shipment_data[self::$origin][self::$destination])) :
            $all_shipment_data[self::$origin][self::$destination] = [];
        endif;

        $all_shipment_data[self::$origin][self::$destination] = $shipment_data;

        set_transient('sejolisa-shipment', $all_shipment_data, 1 * DAY_IN_SECONDS);
    }

    /**
     * Set shipping data as dropdown optios
     * @since   1.0.0
     * @access  protected
     * @return  array
     */
    static protected function set_shipping_as_options($shipping_data) {

        $options     = [];
        $weight_cost = (int) round((self::$quantity * self::$weight) / 1000);
        $weight_cost = (0 === $weight_cost) ? 1 : $weight_cost;

        foreach($shipping_data as $key => $data) :
            list($courier, $service, $cost) = explode(':::', $key);
            $total_cost    = $data['cost'] * $weight_cost;
            $_key = $courier . ':::' . $service . ':::' . $total_cost;
            error_log(print_r($data['courier'], true));
            if($data['courier'] == "JNE"):
                $options[$_key] = sprintf(
                                        __('%s %s (%s) - %s, estimasi %s Hari (COD)', 'sejoli-jne-official'),
                                        $data['courier'],
                                        $data['service'],
                                        $data['description'],
                                        sejolisa_price_format($total_cost),
                                        $data['etd']
                                  );
            else:
                $options[$_key] = sprintf(
                                        __('%s %s (%s) - %s, estimasi %s Hari', 'sejoli-jne-official'),
                                        $data['courier'],
                                        $data['service'],
                                        $data['description'],
                                        sejolisa_price_format($total_cost),
                                        $data['etd']
                                  );
            endif;
        endforeach;

        return $options;
    }

    /**
     * @since   1.5.3.3
     * @param   string  $api_key
     */
    static protected function contact_service( $api_key ) {

        $params = array(
            'key'             => $api_key,
            'originType'      => 'subdistrict',
            'origin'          => self::$origin,
            'destinationType' => 'subdistrict',
            'destination'     => self::$destination,
            'weight'          => 1000,
            'courier'         => self::$courier
        );

        $response = wp_remote_post(
            'https://<p>`</p>ro.rajaongkir.com/api/cost',
            [
                'timeout' => 180,
                'body'    => $params
            ]);

        $weight        = (self::$quantity * self::$weight) / 1000;
        $code          = wp_remote_retrieve_response_code($response);

        $body_response = json_decode(wp_remote_retrieve_body($response), true);

        if(200 === intval($code)) :

            $shipment_data = [];
            $services      = apply_filters('sejoli/shipment/available-courier-services', []);

            foreach( (array) $body_response['rajaongkir']['results'] as $_courier_data) :

                $courier_key  = strtoupper($_courier_data['code']);
                $courier_name = $_courier_data['name'];

                foreach( (array) $_courier_data['costs'] as $_courier_services) :

                    foreach( (array) $_courier_services['cost'] as $_courier_cost) :

                        if(in_array($_courier_services['service'], $services)) :
                            $key = strtoupper($courier_key.':::'.$_courier_services['service'].':::'.intval($_courier_cost['value']));

                            $shipment_data[$key] = [
                                'courier'     => $courier_key,
                                'service'     => $_courier_services['service'],
                                'description' => $_courier_services['description'],
                                'cost'        => $_courier_cost['value'],
                                'etd'         => $_courier_cost['etd']
                            ];
                        endif;
                    endforeach;

                endforeach;

            endforeach;

            self::set_temporary_data($shipment_data);
            self::set_valid(true);
            self::set_respond('shipment', self::set_shipping_as_options($shipment_data));
        else :

            self::set_valid(false);
            self::set_message($body_response['rajaongkir']['status']['description']);

            do_action('sejoli/log/write', 'courier service log', $body_response['rajaongkir']['status']['description']);

        endif;

    }

    /**
     * Get shipment cos
     * @since   1.0.0
     * @access  public
     */
    static public function get_cost() {

        self::validate();

        if(false !== self::$valid) :

            $shipment_data = self::get_temporary_data();

            if(false !== $shipment_data && (is_array($shipment_data) && 0 < count($shipment_data))) :

                self::set_valid(true);
                self::set_respond('shipment', self::set_shipping_as_options($shipment_data));
                return new static;

            endif;

            self::contact_service( self::$api_key[0]);

        endif;

        return new static;
    }
}
