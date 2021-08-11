<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin;

use Oemm\System\Loader;
use Oemm\System\I18n;
use Oemm\System\Assets;
use Oemm\Library\Libraries;
use Oemm\System\Nag;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->loader = new Loader();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}


	/**
	 * Register all of the hooks related to the features of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_global_hooks() {
		$bootstrap = new Initializer();
		$assets    = new Assets();
		$updater   = new Updater();
		$libraries = new Libraries();
		$this->loader->add_filter( 'perfopsone_plugin_info', self::class, 'perfopsone_plugin_info' );
		$this->loader->add_action( 'init', $bootstrap, 'initialize' );
		$this->loader->add_action( 'init', $bootstrap, 'late_initialize', PHP_INT_MAX );
		$this->loader->add_action( 'wp_head', $assets, 'prefetch' );
		add_shortcode( 'oemm-changelog', [ $updater, 'sc_get_changelog' ] );
		add_shortcode( 'oemm-libraries', [ $libraries, 'sc_get_list' ] );
		add_shortcode( 'oemm-statistics', [ 'Oemm\System\Statistics', 'sc_get_raw' ] );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new oEmbed_Manager_Admin();
		$nag          = new Nag();
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'init_admin_menus' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'finalize_admin_menus', PHP_INT_MAX - 1 );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'normalize_admin_menus', PHP_INT_MAX );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'init_settings_sections' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( OEMM_PLUGIN_DIR . OEMM_SLUG . '.php' ), $plugin_admin, 'add_actions_links', 10, 4 );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'add_row_meta', 10, 2 );
		$this->loader->add_action( 'admin_notices', $nag, 'display' );
		$this->loader->add_action( 'wp_ajax_hide_oemm_nag', $nag, 'hide_callback' );
		//$this->loader->add_action( 'wp_ajax_oemm_get_stats', 'Oemm\Plugin\Feature\AnalyticsFactory', 'get_stats_callback' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new oEmbed_Manager_Public();
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Adds full plugin identification.
	 *
	 * @param array $plugin The already set identification information.
	 * @return array The extended identification information.
	 * @since 1.0.0
	 */
	public static function perfopsone_plugin_info( $plugin ) {
		$plugin[ OEMM_SLUG ] = [
			'name'    => OEMM_PRODUCT_NAME,
			'code'    => OEMM_CODENAME,
			'version' => OEMM_VERSION,
			'url'     => OEMM_PRODUCT_URL,
			'icon'    => self::get_base64_logo(),
		];
		return $plugin;
	}

	/**
	 * Returns a base64 svg resource for the plugin logo.
	 *
	 * @return string The svg resource as a base64.
	 * @since 1.0.0
	 */
	public static function get_base64_logo() {
		$source  = '<svg width="100%" height="100%" viewBox="0 0 1001 1001" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;">';
		$source .= '<g id="oEmbed-Manager" serif:id="oEmbed Manager" transform="matrix(10.0067,0,0,10.0067,0,0)">';
		$source .= '<rect x="0" y="0" width="100" height="100" style="fill:none;"/>';
		$source .= '<g id="Icons" transform="matrix(2.16775,0,0,2.15864,7.13472,6.83203)">';
		$source .= '<g transform="matrix(0,-58.8615,-58.8615,0,20,41.5608)"><path d="M0.689,0.294C0.689,0.31 0.676,0.323 0.661,0.323L0.191,0.323C0.175,0.323 0.162,0.31 0.162,0.294L0.162,-0.294C0.162,-0.31 0.175,-0.323 0.191,-0.323L0.661,-0.323C0.676,-0.323 0.689,-0.31 0.689,-0.294L0.689,0.294Z" style="fill:url(#_Linear1);fill-rule:nonzero;"/></g>';
		$source .= '<g opacity="0.5"><g transform="matrix(0,28.5011,28.5011,0,20,-9.5)"><path d="M0.579,0.667L0.579,-0.667L0.43,-0.667C0.396,-0.667 0.368,-0.639 0.368,-0.605L0.368,0.605C0.368,0.639 0.396,0.667 0.43,0.667L0.579,0.667Z" style="fill:url(#_Linear2);fill-rule:nonzero;"/></g></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,5,3)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,9,3)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0,-1,-1,0,13,3)"><path d="M-1,-1C-1.552,-1 -2,-0.552 -2,0C-2,0.552 -1.552,1 -1,1C-0.448,1 0,0.552 0,0C0,-0.552 -0.448,-1 -1,-1" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g opacity="0.5"><g transform="matrix(1,0,0,1,0,-11)"><rect x="3" y="20" width="34" height="21" style="fill:white;"/></g></g>';
		$source .= '<g transform="matrix(-4.23422e-18,-1.21069,-1.13646,-4.25209e-18,33.123,30.7133)"><path d="M-2,-2C-3.104,-2 -4,-1.104 -4,0C-4,1.104 -3.104,2 -2,2C-0.896,2 0,1.104 0,0C0,-1.104 -0.896,-2 -2,-2" style="fill:white;fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(-1.76848,13.123,13.0673,1.77401,33.4665,29.1423)"><path d="M-0.179,-0.132L-0.085,-0.126C-0.082,-0.128 -0.079,-0.13 -0.076,-0.132C-0.065,-0.162 -0.051,-0.19 -0.035,-0.216C-0.035,-0.22 -0.035,-0.223 -0.035,-0.228L-0.098,-0.298C-0.11,-0.312 -0.109,-0.334 -0.095,-0.347L-0.044,-0.392L0.007,-0.437C0.021,-0.449 0.043,-0.448 0.056,-0.434L0.118,-0.363C0.123,-0.362 0.128,-0.361 0.134,-0.36C0.158,-0.371 0.185,-0.38 0.212,-0.385C0.216,-0.39 0.22,-0.395 0.224,-0.4L0.23,-0.494C0.231,-0.513 0.248,-0.527 0.267,-0.526L0.335,-0.522L0.402,-0.518C0.421,-0.517 0.436,-0.5 0.435,-0.481L0.429,-0.387C0.433,-0.38 0.438,-0.373 0.442,-0.366C0.463,-0.358 0.483,-0.348 0.502,-0.336C0.512,-0.337 0.52,-0.337 0.53,-0.338L0.601,-0.4C0.615,-0.413 0.637,-0.412 0.649,-0.397L0.739,-0.296C0.752,-0.281 0.751,-0.26 0.736,-0.247L0.661,-0.18C0.657,-0.177 0.654,-0.174 0.651,-0.17C0.665,-0.142 0.675,-0.112 0.681,-0.081C0.686,-0.08 0.691,-0.079 0.696,-0.079L0.805,-0.072C0.808,-0.072 0.812,-0.071 0.815,-0.069C0.818,-0.068 0.821,-0.066 0.823,-0.063C0.827,-0.058 0.83,-0.052 0.829,-0.044L0.825,0.032L0.82,0.108C0.819,0.115 0.816,0.122 0.811,0.126C0.809,0.128 0.806,0.13 0.803,0.131C0.799,0.132 0.796,0.133 0.792,0.133L0.684,0.126L0.668,0.126C0.658,0.157 0.645,0.185 0.628,0.211C0.63,0.215 0.633,0.219 0.636,0.223L0.703,0.298C0.715,0.312 0.714,0.334 0.7,0.347L0.598,0.437C0.584,0.449 0.562,0.448 0.549,0.434L0.487,0.363C0.478,0.361 0.469,0.36 0.46,0.358C0.439,0.367 0.418,0.375 0.397,0.38C0.391,0.387 0.386,0.393 0.381,0.4L0.375,0.494C0.374,0.513 0.357,0.527 0.338,0.526L0.271,0.522L0.203,0.518C0.184,0.517 0.169,0.5 0.171,0.481L0.176,0.387C0.173,0.382 0.169,0.377 0.165,0.371C0.139,0.362 0.114,0.351 0.091,0.336C0.086,0.337 0.081,0.337 0.075,0.338L0.005,0.4C-0.01,0.413 -0.032,0.412 -0.044,0.397L-0.134,0.296C-0.147,0.281 -0.145,0.259 -0.131,0.247L-0.061,0.184C-0.06,0.18 -0.059,0.177 -0.058,0.173C-0.072,0.145 -0.082,0.116 -0.089,0.085C-0.092,0.083 -0.094,0.08 -0.097,0.078L-0.191,0.072C-0.21,0.071 -0.225,0.055 -0.223,0.036L-0.219,-0.032L-0.215,-0.1C-0.215,-0.101 -0.215,-0.102 -0.215,-0.102C-0.213,-0.12 -0.197,-0.133 -0.179,-0.132ZM0.443,0.009C0.448,-0.073 0.386,-0.143 0.305,-0.148C0.223,-0.153 0.153,-0.091 0.148,-0.01C0.143,0.072 0.205,0.142 0.286,0.147C0.364,0.152 0.432,0.096 0.442,0.019C0.443,0.016 0.443,0.012 0.443,0.009Z" style="fill:url(#_Linear3);fill-rule:nonzero;"/></g>';
		$source .= '<g transform="matrix(0.843544,0,0,0.877371,9.47461,9.06078)"><path d="M23.495,6.205C23.213,5.193 22.419,4.399 21.407,4.117C19.537,3.616 12.011,3.616 12.011,3.616C12.011,3.616 4.504,3.606 2.615,4.117C1.603,4.399 0.809,5.193 0.527,6.205C0.173,8.12 -0.002,10.063 0.005,12.01C-0.001,13.95 0.174,15.886 0.527,17.793C0.809,18.805 1.603,19.599 2.615,19.881C4.483,20.383 12.011,20.383 12.011,20.383C12.011,20.383 19.517,20.383 21.407,19.881C22.419,19.599 23.213,18.805 23.495,17.793C23.841,15.885 24.008,13.949 23.995,12.01C24.009,10.064 23.842,8.12 23.495,6.205ZM9.609,15.601L9.609,8.408L15.873,12.01L9.609,15.601Z" style="fill:url(#_Linear4);fill-rule:nonzero;"/></g>';
		$source .= '</g>';
		$source .= '</g>';
		$source .= '<defs>';
		$source .= '<linearGradient id="_Linear1" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,1.11022e-16)"><stop offset="0" style="stop-color:rgb(248,247,252);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear2" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,0,0,-1,0,0)"><stop offset="0" style="stop-color:rgb(25,39,131);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(65,172,255);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear3" x1="0" y1="0" x2="1" y2="-0.000139067" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1,-2.77556e-17,-2.77556e-17,-1,0,-5.95027e-05)"><stop offset="0" style="stop-color:rgb(255,216,111);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,147,8);stop-opacity:1"/></linearGradient>';
		$source .= '<linearGradient id="_Linear4" x1="0" y1="0" x2="1" y2="0" gradientUnits="userSpaceOnUse" gradientTransform="matrix(36.17,0,0,-47.3535,-2.11146,11.9995)"><stop offset="0" style="stop-color:rgb(255,216,111);stop-opacity:1"/><stop offset="1" style="stop-color:rgb(255,147,8);stop-opacity:1"/></linearGradient>';
		$source .= '</defs>';
		$source .= '</svg>';
		// phpcs:ignore
		return 'data:image/svg+xml;base64,' . base64_encode( $source );
	}

}
