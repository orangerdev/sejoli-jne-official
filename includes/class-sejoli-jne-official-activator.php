<?php

/**
 * Fired during plugin activation
 *
 * @link       https://sejoli.co.id
 * @since      1.0.0
 *
 * @package    Sejoli_Jne_Official
 * @subpackage Sejoli_Jne_Official/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Sejoli_Jne_Official
 * @subpackage Sejoli_Jne_Official/includes
 * @author     Sejoli Team <engineer@sejoli.co.id>
 */
class Sejoli_Jne_Official_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		Sejoli_Jne_Official\Database\State::create_table();
		Sejoli_Jne_Official\Database\City::create_table();
		Sejoli_Jne_Official\Database\District::create_table();
		
		Sejoli_Jne_Official\Database\JNE\Origin::create_table();
		Sejoli_Jne_Official\Database\JNE\Destination::create_table();
		Sejoli_Jne_Official\Database\JNE\Tariff::create_table();

		$seed = new Sejoli_Jne_Official\Database\Seed();
		
	}

}
