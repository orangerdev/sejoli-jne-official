<?php

namespace Sejoli_Jne_Official;

class Payment {

    public $id;
    public $name;
    public $title;
    public $logo;
    public $description;
    public $day = array();

    /**
     * Construction
     */
    public function __construct() {
        $this->day = array(
            1   => __("1 Hari", 'sejoli'),
            2   => __("2 Hari", 'sejoli'),
            3   => __("3 Hari", 'sejoli'),
            4   => __("4 Hari", 'sejoli'),
            5   => __("5 Hari", 'sejoli'),
            6   => __("6 Hari", 'sejoli'),
            7   => __("7 Hari", 'sejoli')
        );
    }

    /**
     * Get payment id
     * @since   1.0.0
     * @return  void
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get payment name
     * @since   1.0.0
     * @return  void
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get payment menu title
     * @since   1.0.0
     * @return  void
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Get payment menu logo
     * @since   1.0.0
     * @return  void
     */
    public function get_logo() {
        return $this->logo;
    }

    /**
     * Get payment description
     * @since   1.0.0
     * @return void
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get setup field
     * @since   1.0.0
     * @return array
     */
    public function get_setup_fields() {
        return [];
    }

    /**
     * Set order price
     * @since  1.0.0
     * @param float $price
     * @param array $order_data
     * @return float
     */
    public function set_price(float $price, array $order_data) {
        return $price;
    }

    /**
     * Set order meta data
     * @since 1.0.0
     * @param array $meta_data
     * @param array $order_data
     * @return array
     */
    public function set_meta_data(array $meta_data, array $order_data, $payment_subtype) {
        return $meta_data;
    }

    /**
     * Get unique code operational method
     * @since   1.1.7
     * @return  string
     */
    public function get_operational_method() {
        return '';
    }

    /**
     * Update order status based on product type ( digital or physic)
     * It's fired when payment module confirm the order payment
     *
     * @since   1.0.0
     * @param   int     $order_id
     * @return  void
     */
    protected function update_order_status($order_id) {

        $respond = sejolisa_get_order(['ID' => $order_id]);

        if(false !== $respond['valid']) :
            $order   = $respond['orders'];
            $product = sejolisa_get_product($order['product_id']);
            $status  = ('digital' === $product->type) ? 'completed' : 'in-progress';

            do_action('sejoli/order/update-status',[
                'ID'       => $order['ID'],
                'status'   => $status
            ]);
        endif;
    }
}
