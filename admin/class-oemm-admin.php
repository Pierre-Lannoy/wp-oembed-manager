<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin;

use Oemm\Plugin\Feature\Analytics;
use Oemm\Plugin\Feature\AnalyticsFactory;
use Oemm\System\Assets;
use Oemm\System\Logger;
use Oemm\System\Role;
use Oemm\System\Option;
use Oemm\System\Form;
use Oemm\System\Blog;
use Oemm\System\Date;
use Oemm\System\Timezone;
use Oemm\System\GeoIP;
use Oemm\System\Environment;
use PerfOpsOne\AdminMenus;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Oemm_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Assets    $assets    The plugin assets manager.
	 */
	protected $assets;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->assets = new Assets();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		$this->assets->register_style( OEMM_ASSETS_ID, OEMM_ADMIN_URL, 'css/oembed-manager.min.css' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$this->assets->register_script( OEMM_ASSETS_ID, OEMM_ADMIN_URL, 'js/oembed-manager.min.js', [ 'jquery' ] );
	}

	/**
	 * Init PerfOps admin menus.
	 *
	 * @param array $perfops    The already declared menus.
	 * @return array    The completed menus array.
	 * @since 1.0.0
	 */
	public function init_perfops_admin_menus( $perfops ) {
		if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$perfops['settings'][] = [
				'name'          => OEMM_PRODUCT_NAME,
				'description'   => '',
				'icon_callback' => [ \Oemm\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'oemm-settings',
				/* translators: as in the sentence "oEmbed Manager Settings" or "WordPress Settings" */
				'page_title'    => sprintf( esc_html__( '%s Settings', 'oembed-manager' ), OEMM_PRODUCT_NAME ),
				'menu_title'    => OEMM_PRODUCT_NAME,
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_settings_page' ],
				'position'      => 50,
				'plugin'        => OEMM_SLUG,
				'version'       => OEMM_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Oemm\System\Statistics', 'sc_get_raw' ],
			];
		}
		/*if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
			$perfops['analytics'][] = [
				'name'          => esc_html__( 'API oEmbed Manager', 'oembed-manager' ),
				/* translators: as in the sentence "Find out inbound and outbound API calls made to/from your network." or "Find out inbound and outbound API calls made to/from your website." *
				'description'   => sprintf( esc_html__( 'Find out inbound and outbound API calls made to/from your %s.', 'oembed-manager' ), Environment::is_wordpress_multisite() ? esc_html__( 'network', 'oembed-manager' ) : esc_html__( 'website', 'oembed-manager' ) ),
				'icon_callback' => [ \Oemm\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'oemm-viewer',
				/* translators: as in the sentence "DecaLog Viewer" *
				'page_title'    => sprintf( esc_html__( 'API oEmbed Manager', 'oembed-manager' ), OEMM_PRODUCT_NAME ),
				'menu_title'    => esc_html__( 'API oEmbed Manager', 'oembed-manager' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_viewer_page' ],
				'position'      => 50,
				'plugin'        => OEMM_SLUG,
				'activated'     => true,
				'remedy'        => '',
			];
		}*/
		return $perfops;
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfops_admin_menus', [ $this, 'init_perfops_admin_menus' ] );
		AdminMenus::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'oemm_plugin_options_section', esc_html__( 'Plugin options', 'oembed-manager' ), [ $this, 'plugin_options_section_callback' ], 'oemm_plugin_options_section' );
	}

	/**
	 * Add links in the "Actions" column on the plugins view page.
	 *
	 * @param string[] $actions     An array of plugin action links. By default this can include 'activate',
	 *                              'deactivate', and 'delete'.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array    $plugin_data An array of plugin data. See `get_plugin_data()`.
	 * @param string   $context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                              'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 * @return array Extended list of links to print in the "Actions" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_actions_links( $actions, $plugin_file, $plugin_data, $context ) {
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=oemm-settings' ) ), esc_html__( 'Settings', 'oembed-manager' ) );
		//$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=oemm-viewer' ) ), esc_html__( 'Statistics', 'oembed-manager' ) );
		return $actions;
	}

	/**
	 * Add links in the "Description" column on the plugins view page.
	 *
	 * @param array  $links List of links to print in the "Description" column on the Plugins page.
	 * @param string $file Path to the plugin file relative to the plugins directory.
	 * @return array Extended list of links to print in the "Description" column on the Plugins page.
	 * @since 1.0.0
	 */
	public function add_row_meta( $links, $file ) {
		if ( 0 === strpos( $file, OEMM_SLUG . '/' ) ) {
			$links[] = '<a href="https://wordpress.org/support/plugin/' . OEMM_SLUG . '/">' . __( 'Support', 'oembed-manager' ) . '</a>';
			$links[] = '<a href="https://github.com/Pierre-Lannoy/wp-oembed-manager">' . __( 'GitHub repository', 'oembed-manager' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the settings page.
	 *
	 * @since 1.0.0
	 */
	public function get_settings_page() {
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( $action && $tab ) {
			switch ( $tab ) {
				case 'misc':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_options();
								} elseif ( ! empty( $_POST ) && array_key_exists( 'reset-to-defaults', $_POST ) ) {
									$this->reset_options();
								}
							}
							break;
					}
					break;
			}
		}
		include OEMM_ADMIN_DIR . 'partials/oembed-manager-admin-settings-main.php';
	}

	/**
	 * Save the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function save_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'oemm-plugin-options' ) ) {
				Option::network_set( 'use_cdn', array_key_exists( 'oemm_plugin_options_usecdn', $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_plugin_options_usecdn' ) : false );
				Option::network_set( 'display_nag', array_key_exists( 'oemm_plugin_options_nag', $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_plugin_options_nag' ) : false );
				flush_rewrite_rules();
				$message = esc_html__( 'Plugin settings have been saved.', 'oembed-manager' );
				$code    = 0;
				add_settings_error( 'oemm_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings updated.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'oembed-manager' );
				$code    = 2;
				add_settings_error( 'oemm_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not updated.', $code );
			}
		}
	}

	/**
	 * Reset the plugin options.
	 *
	 * @since 1.0.0
	 */
	private function reset_options() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'oemm-plugin-options' ) ) {
				Option::reset_to_defaults();
				$message = esc_html__( 'Plugin settings have been reset to defaults.', 'oembed-manager' );
				$code    = 0;
				add_settings_error( 'oemm_no_error', $code, $message, 'updated' );
				Logger::info( 'Plugin settings reset to defaults.', $code );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'oembed-manager' );
				$code    = 2;
				add_settings_error( 'oemm_nonce_error', $code, $message, 'error' );
				Logger::warning( 'Plugin settings not reset to defaults.', $code );
			}
		}
	}

	/**
	 * Callback for plugin options section.
	 *
	 * @since 1.0.0
	 */
	public function plugin_options_section_callback() {
		$form = new Form();
		if ( defined( 'DECALOG_VERSION' ) ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site is currently using %s.', 'oembed-manager' ), '<em>DecaLog v' . DECALOG_VERSION .'</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__('Your site does not use any logging plugin. To log all events triggered in oEmbed Manager, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'oembed-manager' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
		}
		add_settings_field(
			'oemm_plugin_options_logger',
			__( 'Logging', 'oembed-manager' ),
			[ $form, 'echo_field_simple_text' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text' => $help
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_logger' );
		add_settings_field(
			'oemm_plugin_options_usecdn',
			__( 'Resources', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'oembed-manager' ),
				'id'          => 'oemm_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, oEmbed Manager will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'oembed-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_usecdn' );
		add_settings_field(
			'oemm_plugin_options_nag',
			__( 'Admin notices', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'oembed-manager' ),
				'id'          => 'oemm_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows oEmbed Manager to display admin notices throughout the admin dashboard.', 'oembed-manager' ) . '<br/>' . esc_html__( 'Note: oEmbed Manager respects DISABLE_NAG_NOTICES flag.', 'oembed-manager' ),
				'full_width'  => true,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_nag' );
	}

}
