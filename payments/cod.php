<?php

namespace Sejoli_Jne_Official\Payment;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Illuminate\Database\Capsule\Manager as Capsule;

final class Cod extends \Sejoli_Jne_Official\Payment{

    /**
     * Table name
     * @since 1.0.0
     * @var string
     */
    protected $table = 'sejoli_jne_cod_transaction';

    /**
     * Unique code
     * @since 1.0.0
     * @var float
     */
    protected $unique_code = 0.0;

    /**
     * Order price
     * @since 1.0.0
     * @var float
     */
    protected $order_price = 0.0;

    /**
     * Construction
     */
    public function __construct() {

        global $wpdb;

        $this->id          = 'cod';
        $this->name        = __('Transaksi COD', 'sejoli-jne-official');
        $this->title       = __('Transaksi COD', 'sejoli-jne-official');
        $this->description = __('Transaksi COD tidak akan divalidasi secara otomatis.', 'sejoli-jne-official');
        $this->table       = $wpdb->prefix . $this->table;

        add_action('admin_init',        [$this, 'register_transaction_table'], 1);
        add_action('sejoli/order/new',  [$this, 'save_unique_code'], 999);
        add_filter('sejoli/payment/payment-options', [$this, 'add_payment_options'] );

    }

    /**
     * Register transaction table
     * @return void
     */
    public function register_transaction_table() {

        if(!Capsule::schema()->hasTable( $this->table )):
            Capsule::schema()->create( $this->table, function($table){
                $table->increments('ID');
                $table->datetime('created_at');
                $table->datetime('updated_at')->default('0000-00-00 00:00:00');
                $table->integer('order_id');
                $table->integer('user_id')->nullable();
                $table->float('total', 12, 2);
                $table->integer('unique_code');
                $table->text('meta_data');
            });
        endif;
    }

    /**
     * Return setup field
     * @return array
     */
    public function get_setup_fields() {
        return [
            Field::make('separator', 'sep_cod_tranaction_setting',	__('Pengaturan Transaksi COD', 'sejoli-jne-official')),

            Field::make('checkbox', 'cod_transaction_active', __('Aktifkan metode transaksi ini', 'sejoli-jne-official'))
                ->set_option_value('yes')
                ->set_default_value(true),

            Field::make('text',     'cod_transaction_unique_code', __('Maksimal kode unik', 'sejoli-jne-official'))
                ->set_attribute('type', 'number')
                ->set_attribute('min', 1)
                ->set_attribute('max', 999)
                ->set_default_value(999)
                ->set_required(true)
                ->set_width(50),

            Field::make('select',   'cod_transaction_operation', __('Pengoperasian kode unik', 'sejoli-jne-official'))
                ->set_width(50)
                ->set_options([
                    ''        => __('Tidak ada pengoperasian', 'sejoli-jne-official'),
                    'added'   => __('Total nilai belanja ditambahkan kode unik', 'sejoli-jne-official'),
                    'reduced' => __('Total nilai belanja dikurangi kode unik', 'sejoli-jne-official')
                ])
                ->set_default_value('added'),
        ];
    }

    /**
     * Add payment options if cod transfer active
     * Hooked via filter sejoli/payment/payment-options
     * @since   1.0.0
     * @param   array $options
     * @return  array
     */
    public function add_payment_options($options = array()) {

        $active = boolval( carbon_get_theme_option('cod_transaction_active') );

        if(true === $active) :

            $cod_name = __('Cash on Delivery', 'sejoli-jne-official');
            $cod_image = SEJOLI_JNE_OFFICIAL_URL . 'public/img/cod.png';

            $key = 'cod:::CashOnDelivery';
            $cod_name = 
            $options[$key] = [
                'label' => $cod_name,
                'image' => $cod_image
            ];

        endif;

        return $options;
    }

    /**
     * Check unique code
     */
    protected function check_unique_code() {

        $operation = carbon_get_theme_option('cod_transaction_operation');

        if('' !== $operation) :
            $latest_id = Capsule::table($this->table)
                            ->select('ID')
                            ->latest()
                            ->first();

            $the_latest_id = (is_null($latest_id)) ? 0 : $latest_id->ID;

            $max_unique_code   = floatval(carbon_get_theme_option('cod_transaction_unique_code'));
            $this->unique_code = 1;

            if(false !== $latest_id) :

                $this->unique_code = (NULL === $latest_id) ? 1 : $latest_id->ID + 1;

                // if latest_id + 1 over max unique code, then back to 1
                while($max_unique_code < $this->unique_code) :
                    $this->unique_code = $this->unique_code - $max_unique_code;
                endwhile;

                if('added' == $operation) :
                    $this->order_price += $this->unique_code;
                else :
                    $this->order_price -= $this->unique_code;
                endif;

            endif;

        else :

        endif;
    }

    /**
     * Set order price
     * @param float $price
     * @param array $order_data
     * @return float
     */
    public function set_price(float $price, array $order_data) {

        if(0.0 !== $price ) :

            $this->order_price = $price;
            $this->check_unique_code();

            return floatval($this->order_price);
        endif;

        return $price;
    }

    /**
     * Set transaction fee
     * @since 1.0.0
     * @param array $order_data
     * @return string
     */
    public function add_transaction_fee(array $order_data) {

        $operation = carbon_get_theme_option('cod_transaction_operation');

        if('' === $operation)
            return;


        return ('added' === $operation ) ? $this->unique_code : '-'.$this->unique_code;
    }

    /**
     * Save unique code
     * Hooked via action sejoli/order/new, priority 999
     * @param  array  $order_data
     */
    public function save_unique_code(array $order_data) {

        if('cod' == $order_data['payment_gateway'] && !empty($this->unique_code)) :

            Capsule::table($this->table)
                ->insert([
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => '0000-00-00 00:00:00',
                    'order_id'    => $order_data['ID'],
                    'user_id'     => $order_data['user_id'],
                    'total'       => $order_data['grand_total'],
                    'unique_code' => $this->unique_code,
                    'meta_data'   => serialize(array())
                ]);
        endif;

    }

    /**
     * Set order meta data
     * @param array $meta_data
     * @param array $order_data
     * @param array $payment_subtype
     * @return array
     */
    public function set_meta_data(array $meta_data, array $order_data, $payment_subtype) {

        $meta_data['cod'] = [
            'unique_code' => $this->unique_code,
            'cod-chosen' => $payment_subtype
        ];

        return $meta_data;
    }

    /**
     * Display payment instruction in notification
     * @param  array    $invoice_data
     * @param  string   $recipient_type
     * @param  string   $media
     * @return string
     */
    public function display_payment_instruction($invoice_data, $media = 'email') {

        if('on-hold' !== $invoice_data['order_data']['status']) :
            return;
        endif;

        $content = sejoli_get_notification_content(
                        'cod',
                        $media,
                        array(
                            'order' => $invoice_data['order_data']
                        )
                    );

        return $content;
    }

    /**
     * Display simple payment instruction in notification
     * @param  array    $invoice_data
     * @param  string   $recipient_type
     * @param  string   $media
     * @return string
     */
    public function display_simple_payment_instruction($invoice_data, $media = 'email') {

        if('on-hold' !== $invoice_data['order_data']['status']) :
            return;
        endif;

        $content = __('via Cash on Delivery', 'sejoli-jne-official');

        return $content;
    }


    /**
     * Set payment info to order datas
     * @since 1.0.0
     * @param array $order_data
     * @return array
     */
    public function set_payment_info(array $order_data) {

        $trans_data = [
            'bank'  => 'COD - Cash on Delivery'
        ];

        return $trans_data;
    }
    

    /**
     * Get unique code operational method
     * @since   1.1.6
     * @return  string
     */
    public function get_operational_method() {
        $operation = carbon_get_theme_option('cod_transaction_operation');

        if(in_array($operation, array('added', ''))) :
            return '';
        endif;

        return '-';
    }
}
