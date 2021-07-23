<?php
namespace Sejoli_Jne_Official\API;

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
class JNE extends \Sejoli_Jne_Official\API {

	/**
     * Set static data for sandbox api
     *
     * @since   1.0.0
     */
	public static function set_sandbox_data() {

		$username 	= 'TESTAPI';
		$api_key 	= '25c898a9faea1a100859ecd9ef674548';

		self::$body = array(
			'username' => $username,
			'api_key'  => $api_key
		);

	}

	/**
     * Set static data for live api
     *
     * @since   1.0.0
     */
	public function set_live_data() {


	}

	/**
     * Set static data based on api environment target
     *
     * @since   1.0.0
     *
     * @return 	(static) return an instance of static class
     */
	public static function set_params( $is_sandbox = true ) {
		
		self::$headers = [
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Accept' 	   => 'application/json'
		];

		if( $is_sandbox ):
			self::set_sandbox_data();
		else:
			self::set_live_data();
		endif;

		return new static;
	
	}

	/**
     * Check response from api to determine if request is successful
     *
     * @since   1.0.0
     *
     * @return 	(array|boolean) The response array or false on failure
     */
	public static function get_valid_body_object( $response ) {
	
		$response_body = $response['body'];

		if( isset( $response_body->status ) && $response_body->status == 'false' ) {
			return false;
		}
	 	
	 	return json_decode( $response_body );
	
	}

	/**
     * Get origin city data from JNE API
     *
     * @since   1.0.0
     *
     * @return 	(array|WP_Error) The response array or a WP_Error on failure
     */
	public static function get_origin() {
	
		try {
			self::$endpoint = 'http://apiv2.jne.co.id:10102/insert/getorigin';
			self::$method 	= 'POST';

			$get_response 	= self::do_request();

			if ( ! is_wp_error( $get_response ) ) :

				if ( self::verify_response_code( $get_response ) ) :

					if( $data = self::get_valid_body_object( $get_response ) ) :
						return $data->detail;
					else:
						return new \WP_Error( 'invalid_api_response', 'Invalid response body.' );
					endif;

				else :
					return new \WP_Error( 'invalid_api_response', 'Invalid response code.' );
				endif;

			else :
				return $get_response;
			endif;

		} catch ( Exception $e ) {
			return new \WP_Error( 'invalid_api_response', wp_sprintf( __( '<strong>Error from JNE API</strong>: %s', 'scod-shipping' ), $e->getMessage() ) );
		}
	
	}

	/**
     * Get destination data from JNE API
     *
     * @since   1.0.0
     *
     * @return 	(array|WP_Error) The response array or a WP_Error on failure
     */
	public function get_destination() {
	
		try {
			self::$endpoint = 'http://apiv2.jne.co.id:10102/insert/getdestination';
			self::$method 	= 'POST';

			$get_response 	= self::do_request();

			if ( ! is_wp_error( $get_response ) ) :

				if ( self::verify_response_code( $get_response ) ) :

					if( $data = self::get_valid_body_object( $get_response ) ) :
						return $data->detail;
					else:
						return new \WP_Error( 'invalid_api_response', 'Invalid response body.' );
					endif;

				else :
					return new \WP_Error( 'invalid_api_response', 'Invalid response code.' );
				endif;

			else :
				return $get_response;
			endif;

		} catch ( Exception $e ) {
			return new \WP_Error( 'invalid_api_response', wp_sprintf( __( '<strong>Error from JNE API</strong>: %s', 'scod-shipping' ), $e->getMessage() ) );
		}
	
	}

	/**
     * Get tariff data from JNE API
     *
     * @since   1.0.0
     *
     * @param 	$origin 		jne origin code
     * @param 	$destination 	jne destination code
     * @param 	$weight			weight of goods in Kg
     *
     * @return 	(array|WP_Error) The response array or a WP_Error on failure
     */
	public function get_tariff( string $origin, string $destination, int $weight ) {
	
		try {
			self::$endpoint = 'http://apiv2.jne.co.id:10102/tracing/api/pricedev';
			self::$method 	= 'POST';
			self::$body 	= array_merge( self::$body, [
				'from'		=> $origin,
				'thru'		=> $destination,
				'weight'	=> $weight
			]);

			$get_response 	= self::do_request();

			if ( ! is_wp_error( $get_response ) ) :

				if ( self::verify_response_code( $get_response ) ) :

					if( $data = self::get_valid_body_object( $get_response ) ) :

						if( isset( $data->price ) ) {

							return $data->price;
						}

						return new \WP_Error( 'invalid_api_response', 'Invalid tariff data.' );
						
					else:
						return new \WP_Error( 'invalid_api_response', 'Invalid response body.' );
					endif;

				else :
					return new \WP_Error( 'invalid_api_response', 'Invalid response code.' );
				endif;

			else :
				return new \WP_Error( 'invalid_api_response', 'Invalid response.' );
			endif;

		} catch ( Exception $e ) {
			return new \WP_Error( 'invalid_api_response', wp_sprintf( __( '<strong>Error from JNE API</strong>: %s', 'scod-shipping' ), $e->getMessage() ) );
		}
	
	}

	/**
     * Get airway bill or number resi data from JNE API
     *
     * @since   1.0.0
     *
     * @param 	$origin 		jne origin code
     * @param 	$destination 	jne destination code
     * @param 	$weight			weight of goods in Kg
     *
     * @return 	(array|WP_Error) The response array or a WP_Error on failure
     */
	public function get_airwaybill( int $order_id, string $shipper_name, string $shipper_addr1, string $shipper_addr2, string $shipper_city, string $shipper_region, int $shipper_zip, string $shipper_phone, string $receiver_name, string $receiver_addr1, string $receiver_addr2, string $receiver_city, string $receiver_region, int $receiver_zip, string $receiver_phone, int $qty, int $weight, string $goodsdesc, int $goodsvalue, int $goodstype, string $insurance, string $origin, string $destination, string $service, string $codflag, int $codamount ) {
	
		try {
			self::$endpoint 	= 'http://apiv2.jne.co.id:10102/tracing/api/generatecnote';
			self::$method 		= 'POST';
			self::$body 		= array_merge( self::$body, [
				'OLSHOP_BRANCH'			 => 'CGK000',
				'OLSHOP_CUST'			 => '10950700',
				'OLSHOP_ORDERID'		 => '893988', //$order_id,
				'OLSHOP_SHIPPER_NAME'	 => $shipper_name,
				'OLSHOP_SHIPPER_ADDR1'	 => $shipper_addr1,
				'OLSHOP_SHIPPER_ADDR2'	 => $shipper_addr2,
				'OLSHOP_SHIPPER_ADDR3'	 => null,
				'OLSHOP_SHIPPER_CITY'	 => $shipper_city,
				'OLSHOP_SHIPPER_REGION'	 => $shipper_region,
				'OLSHOP_SHIPPER_ZIP'	 => $shipper_zip,
				'OLSHOP_SHIPPER_PHONE'	 => $shipper_phone,
				'OLSHOP_RECEIVER_NAME'	 => $receiver_name,
				'OLSHOP_RECEIVER_ADDR1'	 => $receiver_addr1,
				'OLSHOP_RECEIVER_ADDR2'	 => $receiver_addr2,
				'OLSHOP_RECEIVER_ADDR3'	 => null,
				'OLSHOP_RECEIVER_CITY'	 => $receiver_city,
				'OLSHOP_RECEIVER_REGION' => $receiver_region,
				'OLSHOP_RECEIVER_ZIP'	 => $receiver_zip,
				'OLSHOP_RECEIVER_PHONE'	 => $receiver_phone,
				'OLSHOP_QTY'			 => $qty,
				'OLSHOP_WEIGHT'			 => $weight,
				'OLSHOP_GOODSDESC'		 => $goodsdesc,
				'OLSHOP_GOODSVALUE'		 => $goodsvalue,
				'OLSHOP_GOODSTYPE'		 => $goodstype,
				'OLSHOP_INST'			 => null,
				'OLSHOP_INS_FLAG'		 => $insurance,
				'OLSHOP_ORIG'			 => $origin,
				'OLSHOP_DEST'			 => $destination,
				'OLSHOP_SERVICE'		 => $service,
				'OLSHOP_COD_FLAG'		 => $codflag,
				'OLSHOP_COD_AMOUNT'		 => $codamount
			]);

			$get_response = self::do_request();

			if ( ! is_wp_error( $get_response ) ) :

				if ( self::verify_response_code( $get_response ) ) :

					if( $data = self::get_valid_body_object( $get_response ) ) :

						if( isset( $data->detail ) ) {

							return $data->detail;
						}

						return new \WP_Error( 'invalid_api_response', 'Invalid airwaybill data.' );
						
					else:
						return new \WP_Error( 'invalid_api_response', 'Invalid response body.' );
					endif;

				else :
					return new \WP_Error( 'invalid_api_response', 'Invalid response code.' );
				endif;

			else :
				return new \WP_Error( 'invalid_api_response', 'Invalid response.' );
			endif;

		} catch ( Exception $e ) {
			return new \WP_Error( 'invalid_api_response', wp_sprintf( __( '<strong>Error from JNE API</strong>: %s', 'scod-shipping' ), $e->getMessage() ) );
		}
	
	}

	/**
     * Get Tracking history of status based airwaybill number from JNE API
     *
     * @since   1.0.0
     *
     * @return 	(array|WP_Error) The response array or a WP_Error on failure
     */
	public function get_tracking(string $tracking_number) {
	
		try {
			// self::$endpoint = 'http://apiv2.jne.co.id:10102/tracing/api/list/cnoteretails/cnote/'.$tracking_number;
			self::$endpoint = 'http://apiv2.jne.co.id:10102/tracing/api/list/cnoteretails/cnote/4808012000000159';
			self::$method 	= 'POST';

			$get_response 	= self::do_request();

			if ( ! is_wp_error( $get_response ) ) :

				if ( self::verify_response_code( $get_response ) ) :

					if( $data = self::get_valid_body_object( $get_response ) ) :
						// return $data->detail;
						return $data;
					else:
						return new \WP_Error( 'invalid_api_response', 'Invalid response body.' );
					endif;

				else :
					return new \WP_Error( 'invalid_api_response', 'Invalid response code.' );
				endif;

			else :
				return $get_response;
			endif;

		} catch ( Exception $e ) {
			return new \WP_Error( 'invalid_api_response', wp_sprintf( __( '<strong>Error from JNE API</strong>: %s', 'scod-shipping' ), $e->getMessage() ) );
		}
	
	}

}