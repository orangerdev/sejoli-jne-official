<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://sejoli.co.id
 * @since      1.0.0
 *
 * @package    Sejoli_Jne_Official
 * @subpackage Sejoli_Jne_Official/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Sejoli_Jne_Official
 * @subpackage Sejoli_Jne_Official/includes
 * @author     Sejoli Team <engineer@sejoli.co.id>
 */
class Sejoli_Jne_Official {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Sejoli_Jne_Official_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		if ( defined( 'SEJOLI_JNE_OFFICIAL_VERSION' ) ) {
			$this->version = SEJOLI_JNE_OFFICIAL_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'sejoli-jne-official';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_json_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Sejoli_Jne_Official_Loader. Orchestrates the hooks of the plugin.
	 * - Sejoli_Jne_Official_i18n. Defines internationalization functionality.
	 * - Sejoli_Jne_Official_Admin. Defines all hooks for the admin area.
	 * - Sejoli_Jne_Official_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'includes/class-sejoli-jne-official-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'includes/class-sejoli-jne-official-i18n.php';

		/**
		 * The class responsible for integrating with database
		 * @var [type]
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'includes/class-sejoli-jne-official-database.php';

		/**
		 * The class responsible for creating database tables.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/main.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/indonesia/state.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/indonesia/city.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/indonesia/district.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/jne/origin.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/jne/destination.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/jne/tariff.php';

		/**
		 * The class responsible for database seed.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'database/indonesia/seed.php';

		/**
		 * The class responsible for database models.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/main.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/state.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/city.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/district.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/jne/origin.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/jne/destination.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'model/jne/tariff.php';

		/**
		 * The class responsible for defining API related functions.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'includes/class-sejoli-jne-official-api.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'api/class-sejoli-jne-official-jne.php';

		/**
		 * The class responsible for defining all actions that work for json functions
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . '/json/main.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . '/json/order.php';

		/**
		 * The class responsible for defining CLI command and function
		 * side of the site.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'cli/jne.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'admin/class-sejoli-jne-official-admin.php';
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'admin/shipment.php';
		// require_once SEJOLI_JNE_OFFICIAL_DIR . 'admin/order.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once SEJOLI_JNE_OFFICIAL_DIR . 'public/class-sejoli-jne-official-public.php';

		$this->loader = new Sejoli_Jne_Official_Loader();

		Sejoli_Jne_Official\DBIntegration::connection();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Sejoli_Jne_Official_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Sejoli_Jne_Official_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$admin = new Sejoli_Jne_Official\Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );

		$shipment = new Sejoli_Jne_Official\Admin\ShipmentJNE( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'plugins_loaded',							  $shipment, 'register_libraries',  10);
		$this->loader->add_filter( 'sejoli/admin/js-localize-data',		 	      $shipment, 'set_localize_js_var',	10);
		$this->loader->add_action( 'carbon_fields_theme_options_container_saved', $shipment, 'delete_cache_data',	10);

		// $order = new Sejoli_Jne_Official\Admin\Order( $this->get_plugin_name(), $this->get_version() );

		// $this->loader->add_action( 'admin_init',					$order, 'register_cron_jobs',	 100);
		// $this->loader->add_filter( 'sejoli/admin/js-localize-data',	$order, 'set_localize_js_cod_var',	 1);
		// $this->loader->add_filter( 'sejoli/order/status',			$order, 'get_status', 			 9999);
		// $this->loader->add_action( 'sejoli/order/update-status',	$order, 'process_pickup',		 999, 2);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-update',	$order, 'process_pickup_by_ajax', 999);

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$public = new Sejoli_Jne_Official\Front( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to json request
	 *
	 * @since 	1.0.0
	 * @access 	private
	 */
	private function define_json_hooks() {

		// $affiliate = new Sejoli_Jne_Official\JSON\Affiliate();

		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-get-bonus-content', 		$affiliate, 'get_bonus_content', 1);
		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-update-bonus-content', 	$affiliate, 'update_bonus_content', 1);
		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-get-facebook-pixel', 		$affiliate, 'get_facebook_pixel', 1);
		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-update-facebook-pixel',	$affiliate, 'update_facebook_pixel', 1);
		// $this->loader->add_action( 'wp_ajax_sejoli-confirm-commission-transfer',		$affiliate, 'confirm_commission_transfer');
		// $this->loader->add_action( 'wp_ajax_sejoli-pay-single-affiliate-commission',	$affiliate, 'confirm_single_commission_transfer');

		// $commission = new Sejoli_Jne_Official\JSON\Commission();

		// $this->loader->add_action( 'wp_ajax_sejoli-commission-table',				$commission, 'set_for_table',	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-commission-table',		$commission, 'set_for_affiliate_table',	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-commission-detail',	$commission, 'set_for_affiliate_commission_confirmation', 1);
		// $this->loader->add_action( 'wp_ajax_sejoli-commission-chart',				$commission, 'set_for_chart',	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-commission-confirm',				$commission, 'set_for_paid_confirmation',	1);

		// $coupon = new Sejoli_Jne_Official\JSON\Coupon();

		// $this->loader->add_action( 'wp_ajax_sejoli-create-coupon',	$coupon, 'create_affiliate_coupon', 	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-list-coupons',	$coupon, 'list_parent_coupons',			1);
		// $this->loader->add_action( 'wp_ajax_sejoli-coupon-table',	$coupon, 'set_for_table',				1);
		// $this->loader->add_action( 'wp_ajax_sejoli-coupon-check', 	$coupon, 'check_coupon_availability', 	999);
		// $this->loader->add_action( 'wp_ajax_sejoli-coupon-update',	$coupon, 'update_coupons',				1);
		// $this->loader->add_action( 'wp_ajax_sejoli-coupon-delete',	$coupon, 'delete_coupons',				1);

		$order = new Sejoli_Jne_Official\JSON\Order();

		// $this->loader->add_action( 'wp_ajax_sejoli-affiliate-order-table',		$order, 'set_for_affiliate_table', 	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-export-prepare',		$order, 'prepare_for_exporting', 	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-shipping',				$order, 'check_for_shipping', 		1);
		$this->loader->add_action( 'wp_ajax_sejoli-order-pickup-generate-resi',	$order, 'generate_pickup_resi', 1);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-pickup',				$order, 'process_pickup', 		1);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-table',				$order, 'set_for_table',			1);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-chart',				$order, 'set_for_chart',			1);
		// $this->loader->add_action( 'wp_ajax_sejoli-order-detail',				$order, 'get_detail',	    		1);
		// $this->loader->add_action( 'wp_ajax_sejoli-bulk-notification-order',	$order, 'check_order_for_bulk_notification', 1);
		// $this->loader->add_action( 'sejoli_ajax_check-order-for-confirmation',	$order, 'get_order_confirmation',		1);

		// $product  = new Sejoli_Jne_Official\JSON\Product();

		// $this->loader->add_action( 'wp_ajax_sejoli-product-options',				$product, 'set_for_options',		1);
		// $this->loader->add_action( 'wp_ajax_sejoli-product-affiliate-link-list',	$product, 'list_affiliate_links', 	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-product-affiliate-help-list',	$product, 'list_affiliate_help', 	1);
		// $this->loader->add_action( 'wp_ajax_sejoli-product-table',					$product, 'set_for_table', 			1);
		// $this->loader->add_action( 'wp_ajax_sejoli-check-autoresponder',			$product, 'check_autoresponder',	1);

		// $statistic = new Sejoli_Jne_Official\JSON\Statistic();

		// $this->loader->add_action( 'wp_ajax_sejoli-statistic-commission',		$statistic, 'get_commission_data',				1);
		// $this->loader->add_action( 'wp_ajax_sejoli-statistic-product',			$statistic, 'get_product_data',					1);
		// $this->loader->add_action( 'sejoli_ajax_get-member-statistic-today', 	$statistic, 'get_member_today_statistic', 		1);
		// $this->loader->add_action( 'sejoli_ajax_get-member-statistic-yesterday',$statistic, 'get_member_yesterday_statistic', 	1);
		// $this->loader->add_action( 'sejoli_ajax_get-member-statistic-monthly', 	$statistic, 'get_member_monthly_statistic', 1);
		// $this->loader->add_action( 'sejoli_ajax_get-member-statistic-all', 		$statistic, 'get_member_all_statistic', 	1);
		// $this->loader->add_action( 'sejoli_ajax_get-chart-statistic-monthly',	$statistic, 'get_chart_monthly_statistic', 	1);
		// $this->loader->add_action( 'sejoli_ajax_get-chart-statistic-yearly',	$statistic, 'get_chart_yearly_statistic', 	1);
		// $this->loader->add_action( 'sejoli_ajax_get-top-ten',					$statistic, 'get_top_ten_data', 			1);
		// $this->loader->add_action( 'sejoli_ajax_get-acquisition-data',			$statistic, 'get_acquisition_data',			1);
		// $this->loader->add_action( 'sejoli_ajax_get-acquisition-member-data',	$statistic, 'get_acquisition_member_data',	1);

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		$this->loader->run();

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {

		return $this->plugin_name;

	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Sejoli_Jne_Official_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {

		return $this->loader;

	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {

		return $this->version;

	}

	/**
	 * Register shipping method to WooCommerce.
	 *
	 * @since 1.0.0
	 *
	 * @param array $methods Registered shipping methods.
	 */
	public function register_sejoli_jne_method( $methods ) {

	    $methods[ 'sejoli-jne-shipping' ] = new \Sejoli_Jne_Official\Shipping_Method();
	    return $methods;

	}

}
