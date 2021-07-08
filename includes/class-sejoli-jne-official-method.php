<?php
namespace Sejoli_Jne_Official;

use \WeDevs\ORM\Eloquent\Facades\DB;
use Sejoli_Jne_Official\Model\State as State;
use Sejoli_Jne_Official\Model\City as City;
use Sejoli_Jne_Official\Model\District as District;
use Sejoli_Jne_Official\Model\JNE\Origin as JNE_Origin;
use Sejoli_Jne_Official\Model\JNE\Destination as JNE_Destination;
use Sejoli_Jne_Official\Model\JNE\Tariff as JNE_Tariff;
use Sejoli_Jne_Official\API\JNE as API_JNE;
use Sejoli_Jne_Official\API\SCOD as API_SCOD;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://sejoli.co.id
 * @since      1.0.0
 *
 * @package    Sejoli_Jne_Official
 * @subpackage Sejoli_Jne_Official/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sejoli_Jne_Official
 * @subpackage Sejoli_Jne_Official/admin
 * @author     Sejoli Team <orangerdigiart@gmail.com>
 */
function scod_shipping_init() {

	class Shipping_Method extends \WC_Shipping_Method {
		/**
		 * Woongkir_API API Class Object
		 *
		 * @since 1.0.0
		 * @var API_SCOD
		 */
		private $api;

		/**
		 * Supported features.
		 *
		 */
		public $supports = array(
			'shipping-zones',
			'instance-settings',
		);

		/**
		 * Array of supported country code.
		 *
		 */
		public $available_countries = array( 'ID' );

		/**
	     * Constructor. The instance ID is passed to this.
	     *
	     * @param integer $instance_id default: 0
	     */
	    public function __construct( $instance_id = 0 ) {
			$this->api                = new API_SCOD();
	        $this->id 				  = 'scod-shipping';
	        $this->instance_id 		  = absint( $instance_id );
	        $this->title         	  = __( 'Sejoli COD Shipping', 'scod-shipping' );
	        $this->method_title       = __( 'Sejoli COD Shipping', 'scod-shipping' );
	        $this->method_description = __( 'Sejoli COD for WooCommerce shipping method', 'scod-shipping' );
			$this->init();
	    }

	    /**
		 * Initialize user set variables.
		 *
		 * @since 1.0.0
		 */
		public function init() {
			$this->init_form_fields();
			$this->init_settings();

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		}

	    /**
		 * Init form fields.
		 *
		 * @since 1.0.0
		 */
		public function init_form_fields() {
			if ( ! $this->validate_supported_country( WC()->countries->get_base_country() ) ) {
				$this->instance_form_fields = array(
					'title' => array(
						'title'       => __( 'Plugin Unavailable', 'scod-shipping' ),
						'type'        => 'title',
						'description' => __( 'This plugin only work for Store Address based in Indonesia.', 'scod-shipping' ),
					),
				);

				return;
			}

			$settings = array(
        		'scod_username' => array(
        			'title' 		=> __( 'SCOD Username', 'scod-shipping' ),
        			'type' 			=> 'text',
        			'description' 	=> __( 'Please enter your account username.', 'scod-shipping' ),
        		),
        		'scod_password' => array(
        			'title' 		=> __( 'SCOD Password', 'scod-shipping' ),
        			'type' 			=> 'password',
        			'description' 	=> __( 'Please enter your account password.', 'scod-shipping' ),
        		),
        		'store_id' => array(
        			'title' 		=> __( 'SCOD Store ID', 'scod-shipping' ),
        			'type' 			=> 'text',
        			'description' 	=> __( 'Please enter your store ID.', 'scod-shipping' ),
        			'default' 		=> '',
        		),
        		'store_secret_key' => array(
        			'title' 		=> __( 'SCOD Store Secret Key', 'scod-shipping' ),
        			'type' 			=> 'text',
        			'description' 	=> __( 'Please enter your store secret key.', 'scod-shipping' ),
        			'default' 		=> '',
        		),
        		'shipping_origin'  	=> array(
					'title'   		=> __( 'Shipping Origin', 'scod-shipping' ),
        			'description' 	=> __( 'Please select your shipping origin location.', 'scod-shipping' ),
					'type'    		=> 'select',
					'default' 		=> '',
					'options' 		=> $this->generate_origin_dropdown(),
				),
        		'base_weight' => array(
        			'title' 		=> __( 'Default Item Weight (Kg)', 'scod-shipping' ),
        			'type' 			=> 'text',
        			'description' 	=> __( 'Berat default yang digunakan ketika berat per barang tidak ada.', 'scod-shipping' ),
        			'default' 		=> '',
        		),
        		'jne_service_yes' => array(
					'title' 		=> __( 'JNE Services', 'scod-shipping' ),
        			'label'			=> __( 'YES', 'scod-shipping' ),
        			'type' 			=> 'checkbox',
					'default'		=> 'yes',
        		),
        		'jne_service_reg' => array(
        			'label'			=> __( 'Regular (COD Available)', 'scod-shipping' ),
        			'type' 			=> 'checkbox',
					'default'		=> 'yes',
        		),
        		'jne_service_oke' => array(
        			'label'			=> __( 'OKE (COD Available) ', 'scod-shipping' ),
        			'type' 			=> 'checkbox',
					'default'		=> 'yes',
        		),
        		'jne_service_jtr' => array(
        			'label'			=> __( 'JNE Trucking (COD Available) ', 'scod-shipping' ),
        			'type' 			=> 'checkbox',
					'default'		=> 'yes',
        		),
			);

			$this->instance_form_fields = $settings;
		}

		/**
		 * Generate options for origin dropdown
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		private function generate_origin_dropdown() {
			$option_default = array( '' => __( '--- Pilih Origin ---' ) );
			$option_cities  = JNE_Origin::pluck( 'name', 'id' )->toArray();
			return $option_default + $option_cities;
		}

		/**
		 * Validate if current value of country code is supported.
		 *
		 * @param $country_code (string) country code to check.
		 *
		 * @since 1.0.0
		 */
		public function validate_supported_country( string $country_code ) {
			$supported_countries = $this->available_countries;
			return \in_array( $country_code, $supported_countries );
		}

		/**
		 * Validate username & password settings field.
		 *
		 * @since 1.0.0
		 * @param string $key Input field key.
		 * @param string $value Input field current value.
		 * @throws Exception Error message.
		 */
		public function validate_scod_username_field( $key, $value ) {
			error_log( 'Validating scod account ..' );
			$error_msg 		  = wp_sprintf( __( '%s is not valid. Please use a valid account.', 'scod-shipping' ), 'Username or password' );
			$posted 		  = $this->get_post_data();
			$current_username = $this->get_option( 'scod_username' );
			$current_password = $this->get_option( 'scod_password' );
			$username 		  = $posted[ $this->get_field_key( 'scod_username' ) ];
			$password 		  = $posted[ $this->get_field_key( 'scod_password' ) ];

			if( $current_password != $password || $current_username != $username ) {

				if ( ! $username || ! $password ) {
					throw new \Exception( $error_msg );
				}

				$get_token = $this->api->get_new_token( $username, $password );

				if( is_wp_error( $get_token ) ) {
					throw new \Exception( $error_msg );
				}
			}

			return $value;
		}

		/**
		 * Validate store account fields.
		 *
		 * @since 1.0.0
		 * @param string $key Input field key.
		 * @param string $value Input field current value.
		 * @throws Exception Error message.
		 */
		public function validate_store_secret_key_field( $key, $value ) {
			error_log( 'Validating scod store account ..' );
			$error_msg 				  = wp_sprintf( __( '%s is not valid. Please use a valid account.', 'scod-shipping' ), 'Store ID or Store secret key' );
			$posted    				  = $this->get_post_data();
			$current_store_id 		  = $this->get_option( 'store_id' );
			$current_store_secret_key = $this->get_option( 'store_secret_key' );
			$store_id 				  = $posted[ $this->get_field_key( 'store_id' ) ];
			$store_secret_key 		  = $posted[ $this->get_field_key( 'store_secret_key' ) ];

			if( $current_store_id != $store_id || $current_store_secret_key != $store_secret_key ) {

				if ( ! $store_id || ! $store_secret_key ) {
					throw new \Exception( $error_msg );
				}

				$validate_store = $this->api->get_store_detail( $store_id, $store_secret_key );

				if( is_wp_error( $validate_store ) ) {
					throw new \Exception( $error_msg );
				}
			}

			return $value;
		}

		/**
		 * Generate options for origin dropdown
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		private function get_jne_services() {
			$services = array();

			if( $this->get_option('jne_service_yes') == 'yes' ) {
				$services[] = 'YES19';
			}

			if( $this->get_option('jne_service_oke') == 'yes' ) {
				$services[] = 'OKE19';
			}

			if( $this->get_option('jne_service_reg') == 'yes' ) {
				$services[] = 'REG19';
			}

			if( $this->get_option('jne_service_jtr') == 'yes' ) {
				$codes = array( 'JTR18', 'JTR250', 'JTR<150', 'JTR>250' );
				$services = array_merge( $services, $codes );
			}

			return $services;
		}

		/**
		 * Get origin object
		 *
		 * @since 	1.0.0
		 *
		 * @return 	(Object|false) returns an object on true, or false if fail
		 */
		public function get_origin_info() {
			$origin_option = $this->get_option( 'shipping_origin' );
			if( ! $origin_option ) {
				return false;
			}

			$origin = JNE_Origin::find( $origin_option );
			// echo 'ahayy';
			// print_r($origin_option);

			if( ! $origin ) {
				return false;
			}

			return $origin;
		}

		/**
		 * Get destination object
		 *
		 * @since 	1.0.0
		 *
		 * @param array $destination destination array with country, state, postcode, city, address, address_1, address_2
		 *
		 * @return 	(Object|false) returns an object on true, or false if fail
		 */
		public function get_destination_info( array $destination ) {
			if( ! $this->validate_supported_country( $destination['country'] ) ) {
				return false;
			}

			$location_data = array(
				'state'	   => NULL,
				'city'	   => NULL,
				'district' => NULL
			);

			if( $destination['state'] ) {
				$state = State::find( $destination['state'] );

				if( $state ) {
					$location_data[ 'state' ] = $state;
				}
			}

			if( $destination['city'] ) {
				$city = City::find( $destination['city'] );

				if( $city ) {
					$location_data[ 'city' ] = $city;
				}
			}

			if( $destination['address_2'] ) {
				$district = District::find( $destination['address_2'] );

				if( $district ) {
					$location_data[ 'district' ] = $district;
				}
			}

			$get_dest = DB::table( (new JNE_Destination)->getTableName() );

			// if( empty( $location_data['city'] ) ) {
			// 	$get_dest = $get_dest->whereNull( 'city_id' );
			// } else {
			// 	$get_dest = $get_dest->where( 'city_id', $location_data['city']->ID );
			// }

			// if( empty( $location_data['district'] ) ) {
			// 	$get_dest = $get_dest->whereNull( 'district_id' );
			// } else {
			// 	$get_dest = $get_dest->where( 'district_id', $location_data['district']->ID );
			// }

			// if( empty( $location_data['city'] ) ) {
			// 	$get_dest = $get_dest->whereNull( 'city_name' );
			// } else {
			// 	$get_dest = $get_dest->Where('city_name', 'like', '%' . $location_data['city']->name);
			// }

			if( empty( $location_data['district'] ) ) {
				$get_dest = $get_dest->whereNull( 'district_name' );
			} else {
				$get_dest = $get_dest->Where('district_name', 'like', '%' . strtoupper($location_data['district']->name));
			}
			
			// $file = SCOD_SHIPPING_DIR . 'database/jne/data/jne_destination.json';
			// $data = file_get_contents( $file ); 
	  //       $jsonData = json_decode($data);

	  //       $getDistrictName = strtoupper($location_data['district']->name);

	  //       $datades = null;
	  //       foreach( $jsonData as $id => $row ) {
	  //       	if ($getDistrictName == $row->district_name) {
		 //            $datades = array(
		 //                'ID'            => $row->ID,
		 //                'city_id'       => $row->city_id,
		 //                'district_id'   => $row->district_id,
		 //                'city_name'     => $row->city_name,
		 //                'district_name' => $row->district_name,
		 //                'code'          => $row->code
		 //            );
	  //       	}
	  //       }
			
			// if($datades == null){
			// 	$getDestination = $datades;	
			// } else {
			// 	$getDestination = (object)$datades;	
			// }

			// if( $destination = $getDestination ) {
			if( $destination = $get_dest->first() ) {
				return $destination;
			}
			
			return false;
		}

		/**
		 * Get tariff object
		 *
		 * @since 	1.0.0
		 *
	     * @param 	$origin 		origin object to find
	     * @param 	$destination 	destination object to find
	     *
		 * @return 	(Object|false) 	returns an object on true, or false if fail
		 */
		private function get_tariff_info( $origin, $destination ) {
			$get_tariff = JNE_Tariff::where( 'jne_origin_id', $origin->ID )
							->where( 'jne_destination_id', $destination->ID )
							->first();

			if( ! $get_tariff ) {
	        	$req_tariff_data = API_JNE::set_params()->get_tariff( $origin->code, $destination->code );

				if( is_wp_error( $req_tariff_data ) ) {
	        		return false;
	        	}

	        	$get_tariff 					= new JNE_Tariff();
	        	$get_tariff->jne_origin_id 		= $origin->ID;
	        	$get_tariff->jne_destination_id = $destination->ID;
	        	$get_tariff->tariff_data 		= $req_tariff_data;

	        	if( ! $get_tariff->save() ) {
	        		return false;
	        	}
	        }

			return $get_tariff;
		}

		/**
		 * Get cart package total weight
		 *
		 * @since 	1.0.0
	     *
		 * @return 	(Double|false) 	returns double type number, or false if fail
		 */
		private function get_cart_weight() {
			$scod_weight_unit = 'kg';
			$cart_weight 	  = WC()->cart->get_cart_contents_weight();
			$wc_weight_unit   = get_option( 'woocommerce_weight_unit' );

   			if( $wc_weight_unit != $scod_weight_unit && $cart_weight > 0 ) {
   				$cart_weight = wc_get_weight( $cart_weight, $scod_weight_unit, $wc_weight_unit );
   			}

       		if( $cart_weight == 0 ) {
       			$cart_weight = $this->get_option( 'base_weight' );
       		}

       		if( is_numeric( $cart_weight ) ) {
       			return ceil( $cart_weight );
       		}

			return false;
		}

	    /**
	     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
	     *
		 * @since 	1.0.0
		 *
	     * @param 	array $package default: array()
	     *
	     * @return 	boolean|rate returns false if fail, add rate to wc if available
	     */
	    public function calculate_shipping( $package = array() ) {
			$origin 	 = $this->get_origin_info();
			$destination = $this->get_destination_info( $package['destination'] );

			if( ! $origin ) {
	        	return false;
	        }

			if( ! $destination ) {
	        	return false;
	        }

			$tariff = $this->get_tariff_info( $origin, $destination );

			if( ! $tariff ) {
	        	return false;
	        }

	        if( is_array( $tariff->tariff_data ) && count( $tariff->tariff_data ) > 0 ) {

	       		$cart_weight = $this->get_cart_weight();

	       		if( ! $cart_weight ) {
	       			return false;
	       		}

	       		foreach ( $tariff->tariff_data as $rate ) {

					if( \in_array( $rate->service_code, $this->get_jne_services() ) ) {

				        $this->add_rate( array(
							'id'    => $tariff->getRateID( $this->id, $rate ),
							'label' => $tariff->getLabel( $rate ),
							'cost' 	=> $rate->price * $cart_weight
						));
					}
	        	}
	       	}

	    }

	}
}
