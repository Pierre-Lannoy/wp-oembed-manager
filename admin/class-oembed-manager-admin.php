<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin;

use Oemm\System\Assets;

use Oemm\System\Role;
use Oemm\System\Option;
use Oemm\System\Form;
use Oemm\System\Blog;
use Oemm\System\Date;
use Oemm\System\Timezone;
use Oemm\System\GeoIP;
use Oemm\System\Environment;
use PerfOpsOne\Menus;
use PerfOpsOne\AdminBar;
use Oemm\Plugin\Feature\oEmbed;

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class oEmbed_Manager_Admin {

	/**
	 * The assets manager that's responsible for handling all assets of the plugin.
	 *
	 * @since  1.0.0
	 */
	protected $assets;

	/**
	 * The allowed tags in replacement text blocks.
	 *
	 * @since  2.0.0
	 */
	private static $allowedtags = [
		'a'          => [
			'href'   => [],
			'title'  => [],
			'target' => [],
		],
		'abbr'       => [ 'title' => [] ],
		'acronym'    => [ 'title' => [] ],
		'code'       => [],
		'pre'        => [],
		'em'         => [],
		'strong'     => [],
		'div'        => [
			'class' => [],
			'style' => [],
		],
		'span'       => [
			'class' => [],
			'style' => [],
		],
		'i'          => [
			'class' => [],
			'style' => [],
		],
		'button'     => [
			'class'    => [],
			'style'    => [],
			'data-tab' => [],
		],
		'p'          => [
			'class' => [],
			'style' => [],
		],
		'br'         => [],
		'ul'         => [],
		'ol'         => [],
		'li'         => [],
		'h1'         => [],
		'h2'         => [],
		'h3'         => [],
		'h4'         => [],
		'h5'         => [],
		'h6'         => [],
		'img'        => [
			'src'   => [],
			'class' => [],
			'alt'   => [],
			'style' => [],
		],
		'blockquote' => [ 'cite' => true ],
	];

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
	public function init_perfopsone_admin_menus( $perfops ) {
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
				'plugin'        => OEMM_SLUG,
				'version'       => OEMM_VERSION,
				'activated'     => true,
				'remedy'        => '',
				'statistics'    => [ '\Oemm\System\Statistics', 'sc_get_raw' ],
			];
			$perfops['tools'][] = [
				'name'          => esc_html__( 'oEmbed', 'oembed-manager' ),
				'description'   => esc_html__( 'View, clear and update/create oEmbed cached items used by your site.', 'oembed-manager' ),
				'icon_callback' => [ \Oemm\Plugin\Core::class, 'get_base64_logo' ],
				'slug'          => 'oemm-tools',
				'page_title'    => esc_html__( 'oEmbed Cache Management', 'oembed-manager' ),
				'menu_title'    => esc_html__( 'oEmbed', 'oembed-manager' ),
				'capability'    => 'manage_options',
				'callback'      => [ $this, 'get_tools_page' ],
				'plugin'        => OEMM_SLUG,
				'activated'     => true,
				'remedy'        => '',
			];
		}
		return $perfops;
	}

	/**
	 * Init PerfOps admin bar.
	 *
	 * @param array $perfops    The already declared items.
	 * @return array    The completed items array.
	 * @since 3.2.0
	 */
	public function init_perfopsone_admin_bar( $perfops ) {
		if ( ! ( $action = filter_input( INPUT_GET, 'action' ) ) ) {
			$action = filter_input( INPUT_POST, 'action' );
		}
		if ( ! ( $tab = filter_input( INPUT_GET, 'tab' ) ) ) {
			$tab = filter_input( INPUT_POST, 'tab' );
		}
		$early_signal  = ( 'misc' === $tab && 'do-save' === $action ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() );
		$early_signal &= ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) );
		$early_signal &= ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'oemm-plugin-options' ) );
		if ( $early_signal ) {
			Option::network_set( 'adminbar', array_key_exists( 'oemm_plugin_options_adminbar', $_POST ) );
		}
		if ( Option::network_get( 'adminbar' ) && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) ) {
			$perfops[] = [
				'id'    => 'oemm-tools-reset',
				'title' => '<strong>oEmbed</strong>&nbsp;&nbsp;➜&nbsp;&nbsp;' . __( 'Clear All Caches', 'oembed-manager' ),
				'href'  => add_query_arg( '_wpnonce', wp_create_nonce( 'quick-action-oemm-tools' ), admin_url( 'admin.php?page=oemm-tools&quick-action=reset' ) ),
				'meta'  => false,
			];
			$perfops[] = [
				'id'    => 'oemm-tools-warmup',
				'title' => '<strong>oEmbed</strong>&nbsp;&nbsp;➜&nbsp;&nbsp;' . __( 'Update All Caches', 'oembed-manager' ),
				'href'  => add_query_arg( '_wpnonce', wp_create_nonce( 'quick-action-oemm-tools' ), admin_url( 'admin.php?page=oemm-tools&quick-action=warmup' ) ),
				'meta'  => false,
			];
		}
		return $perfops;
	}

	/**
	 * Dispatch the items in the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function finalize_admin_menus() {
		Menus::finalize();
	}

	/**
	 * Removes unneeded items from the settings menu.
	 *
	 * @since 2.0.0
	 */
	public function normalize_admin_menus() {
		Menus::normalize();
	}

	/**
	 * Set the items in the settings menu.
	 *
	 * @since 1.0.0
	 */
	public function init_admin_menus() {
		add_filter( 'init_perfopsone_admin_menus', [ $this, 'init_perfopsone_admin_menus' ] );
		add_filter( 'init_perfopsone_admin_bar', [ $this, 'init_perfopsone_admin_bar' ] );
		Menus::initialize();
		AdminBar::initialize();
	}

	/**
	 * Initializes settings sections.
	 *
	 * @since 1.0.0
	 */
	public function init_settings_sections() {
		add_settings_section( 'oemm_consumer_misc_section', '', [ $this, 'consumer_misc_section_callback' ], 'oemm_consumer_misc_section' );
		add_settings_section( 'oemm_consumer_advanced_section', esc_html__( 'Advanced options', 'oembed-manager' ), [ $this, 'consumer_advanced_section_callback' ], 'oemm_consumer_advanced_section' );
		add_settings_section( 'oemm_producer_section', '', [ $this, 'producer_section_callback' ], 'oemm_producer_section' );
		add_settings_section( 'oemm_plugin_options_section', esc_html__( 'Plugin options', 'oembed-manager' ), [ $this, 'plugin_options_section_callback' ], 'oemm_plugin_options_section' );
		foreach ( oEmbed::get_descriptions() as $integrations ) {
			if ( $integrations['enabled'] ) {
				foreach ( $integrations['items'] as $item ) {
					if ( $item['detected'] ) {
						add_settings_section( 'oemm_consumer_rules_section', esc_html__( 'Exceptions rules', 'oembed-manager' ), [ $this, 'consumer_rules_section_callback' ], 'oemm_consumer_rules_section' );
						break 2;
					}
				}
			}
		}
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
		$actions[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=oemm-tools' ) ), esc_html__( 'Tools', 'oembed-manager' ) );
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
			$links[] = '<a href="https://wordpress.org/support/plugin/' . OEMM_SLUG . '/">' . esc_html__( 'Support', 'oembed-manager' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Get the content of the tools page.
	 *
	 * @since 1.0.0
	 */
	public function get_tools_page() {
		include OEMM_ADMIN_DIR . 'partials/oembed-manager-admin-tools.php';
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
						case 'install-decalog':
							if ( class_exists( 'PerfOpsOne\Installer' ) ) {
								$result = \PerfOpsOne\Installer::do( 'decalog', true );
								if ( '' === $result ) {
									add_settings_error( 'oemm_no_error', '', esc_html__( 'Plugin successfully installed and activated with default settings.', 'oembed-manager' ), 'info' );
								} else {
									add_settings_error( 'oemm_install_error', '', sprintf( esc_html__( 'Unable to install or activate the plugin. Error message: %s.', 'oembed-manager' ), $result ), 'error' );
								}
							}
							break;
					}
					break;
				case 'producer':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_producer();
									oEmbed::set_producer();
									flush_rewrite_rules(true);
								}
							}
							break;
					}
					break;
				case 'consumer':
					switch ( $action ) {
						case 'do-save':
							if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() || Role::LOCAL_ADMIN === Role::admin_type() ) {
								if ( ! empty( $_POST ) && array_key_exists( 'submit', $_POST ) ) {
									$this->save_consumer();
									oEmbed::set_consumer();
									oEmbed::purge_cache();
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
				Option::network_set( 'adminbar', array_key_exists( 'oemm_plugin_options_adminbar', $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_plugin_options_adminbar' ) : false );
				$message = esc_html__( 'Plugin settings have been saved.', 'oembed-manager' );
				$code    = 0;
				add_settings_error( 'oemm_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( 'Plugin settings updated.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'oembed-manager' );
				$code    = 2;
				add_settings_error( 'oemm_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->warning( 'Plugin settings not updated.', [ 'code' => $code ] );
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
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( 'Plugin settings reset to defaults.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been reset to defaults. Please try again.', 'oembed-manager' );
				$code    = 2;
				add_settings_error( 'oemm_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->warning( 'Plugin settings not reset to defaults.', [ 'code' => $code ] );
			}
		}
	}

	/**
	 * Save the producer options.
	 *
	 * @since 1.0.0
	 */
	private function save_producer() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'oemm-plugin-options' ) ) {
				Option::site_set( 'disable_producer', array_key_exists( 'oemm_producer_disable_producer', $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_producer_disable_producer' ) : false );
				$message = esc_html__( 'Plugin settings have been saved.', 'oembed-manager' );
				$code    = 0;
				add_settings_error( 'oemm_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( 'Plugin settings updated.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'oembed-manager' );
				$code    = 2;
				add_settings_error( 'oemm_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->warning( 'Plugin settings not updated.', [ 'code' => $code ] );
			}
		}
	}

	/**
	 * Save the consumer options.
	 *
	 * @since 1.0.0
	 */
	private function save_consumer() {
		if ( ! empty( $_POST ) ) {
			if ( array_key_exists( '_wpnonce', $_POST ) && wp_verify_nonce( $_POST['_wpnonce'], 'oemm-plugin-options' ) ) {
				Option::site_set( 'disable_consumer', array_key_exists( 'oemm_consumer_misc_disable_consumer', $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_consumer_misc_disable_consumer' ) : false );
				Option::site_set( 'consumer_mode', array_key_exists( 'oemm_consumer_misc_mode', $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_misc_mode', FILTER_SANITIZE_NUMBER_INT ) : Option::site_get( 'consumer_mode' ) );
				Option::site_set( 'advanced_clickable', array_key_exists( 'oemm_consumer_advanced_clickable', $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_consumer_advanced_clickable' ) : false );
				Option::site_set( 'advanced_ttl', array_key_exists( 'oemm_consumer_advanced_ttl', $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_advanced_ttl', FILTER_SANITIZE_NUMBER_INT ) : Option::site_get( 'advanced_ttl' ) );
				Option::site_set( 'advanced_timeout', array_key_exists( 'oemm_consumer_advanced_timeout', $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_advanced_timeout', FILTER_SANITIZE_NUMBER_INT ) : Option::site_get( 'advanced_timeout' ) );
				Option::site_set( 'advanced_size', array_key_exists( 'oemm_consumer_advanced_size', $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_advanced_size', FILTER_SANITIZE_NUMBER_INT ) : Option::site_get( 'advanced_size' ) );
				foreach ( oEmbed::get_descriptions() as $integrations ) {
					if ( $integrations['enabled'] ) {
						Option::site_set( 'exception_' . $integrations['prefix'] . '_block', array_key_exists( 'oemm_consumer_rules_block_' . $integrations['prefix'], $_POST ) ? (bool) filter_input( INPUT_POST, 'oemm_consumer_rules_block_' . $integrations['prefix'] ) : false );
						Option::site_set( 'exception_' . $integrations['prefix'] . '_param', array_key_exists( 'oemm_consumer_rules_param_' . $integrations['prefix'], $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_rules_param_' . $integrations['prefix'], FILTER_SANITIZE_STRING ) : Option::site_get( 'exception_' . $integrations['prefix'] . '_param' ) );
						Option::site_set( 'exception_' . $integrations['prefix'] . '_id', array_key_exists( 'oemm_consumer_rules_id_' . $integrations['prefix'], $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_rules_id_' . $integrations['prefix'], FILTER_SANITIZE_STRING ) : Option::site_get( 'exception_' . $integrations['prefix'] . '_id' ) );
						Option::site_set( 'exception_' . $integrations['prefix'] . '_text', array_key_exists( 'oemm_consumer_rules_text_' . $integrations['prefix'], $_POST ) ? (string) filter_input( INPUT_POST, 'oemm_consumer_rules_text_' . $integrations['prefix'], FILTER_UNSAFE_RAW ) : Option::site_get( 'exception_' . $integrations['prefix'] . '_text' ) );
					}
				}
				$message = esc_html__( 'Plugin settings have been saved.', 'oembed-manager' );
				$code    = 0;
				add_settings_error( 'oemm_no_error', $code, $message, 'updated' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( 'Plugin settings updated.', [ 'code' => $code ] );
			} else {
				$message = esc_html__( 'Plugin settings have not been saved. Please try again.', 'oembed-manager' );
				$code    = 2;
				add_settings_error( 'oemm_nonce_error', $code, $message, 'error' );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->warning( 'Plugin settings not updated.', [ 'code' => $code ] );
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
		if ( \DecaLog\Engine::isDecalogActivated() ) {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'thumbs-up', 'none', '#00C800' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site is currently using %s.', 'oembed-manager' ), '<em>' . \DecaLog\Engine::getVersionString() . '</em>' );
		} else {
			$help  = '<img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'alert-triangle', 'none', '#FF8C00' ) . '" />&nbsp;';
			$help .= sprintf( esc_html__( 'Your site does not use any logging plugin. To log all events triggered in oEmbed Manager, I recommend you to install the excellent (and free) %s. But it is not mandatory.', 'oembed-manager' ), '<a href="https://wordpress.org/plugins/decalog/">DecaLog</a>' );
			if ( class_exists( 'PerfOpsOne\Installer' ) && ! Environment::is_wordpress_multisite() ) {
				$help .= '<br/><a href="' . esc_url( admin_url( 'admin.php?page=oemm-settings&tab=misc&action=install-decalog' ) ) . '" class="poo-button-install"><img style="width:16px;vertical-align:text-bottom;" src="' . \Feather\Icons::get_base64( 'download-cloud', 'none', '#FFFFFF', 3 ) . '" />&nbsp;&nbsp;' . esc_html__('Install It Now', 'oembed-manager' ) . '</a>';
			}
		}
		add_settings_field(
			'oemm_plugin_options_logger',
			esc_html__( 'Logging', 'oembed-manager' ),
			[ $form, 'echo_field_simple_text' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text' => $help,
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_logger' );
		add_settings_field(
			'oemm_plugin_options_adminbar',
			__( 'Quick actions', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text'        => esc_html__( 'Display in admin bar', 'oembed-manager' ),
				'id'          => 'oemm_plugin_options_adminbar',
				'checked'     => Option::network_get( 'adminbar' ),
				'description' => esc_html__( 'If checked, oEmbed Manager will display in admin bar the most important actions, if any.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_adminbar' );
		add_settings_field(
			'oemm_plugin_options_usecdn',
			esc_html__( 'Resources', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text'        => esc_html__( 'Use public CDN', 'oembed-manager' ),
				'id'          => 'oemm_plugin_options_usecdn',
				'checked'     => Option::network_get( 'use_cdn' ),
				'description' => esc_html__( 'If checked, oEmbed Manager will use a public CDN (jsDelivr) to serve scripts and stylesheets.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_usecdn' );
		add_settings_field(
			'oemm_plugin_options_nag',
			esc_html__( 'Admin notices', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_plugin_options_section',
			'oemm_plugin_options_section',
			[
				'text'        => esc_html__( 'Display', 'oembed-manager' ),
				'id'          => 'oemm_plugin_options_nag',
				'checked'     => Option::network_get( 'display_nag' ),
				'description' => esc_html__( 'Allows oEmbed Manager to display admin notices throughout the admin dashboard.', 'oembed-manager' ) . '<br/>' . esc_html__( 'Note: oEmbed Manager respects DISABLE_NAG_NOTICES flag.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_plugin_options_section', 'oemm_plugin_options_nag' );
	}

	/**
	 * Get consumer modes.
	 *
	 * @since 2.0.0
	 */
	public function get_consumer_modes() {
		$choices   = [];
		$choices[] = [ 0, esc_html__( 'Styled and formated content', 'oembed-manager' ) ];
		$choices[] = [ 1, esc_html__( 'Just the URL', 'oembed-manager' ) ];
		$choices[] = [ 2, esc_html__( 'Nothing', 'oembed-manager' ) ];
		return $choices;
	}

	/**
	 * Callback for consumer misc options section.
	 *
	 * @since 2.0.0
	 */
	public function consumer_misc_section_callback() {
		$form = new Form();
		add_settings_field(
			'oemm_consumer_misc_disable_consumer',
			esc_html__( 'oEmbed consumer', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_consumer_misc_section',
			'oemm_consumer_misc_section',
			[
				'text'        => esc_html__( 'Disabled', 'oembed-manager' ),
				'id'          => 'oemm_consumer_misc_disable_consumer',
				'checked'     => Option::site_get( 'disable_consumer' ),
				'description' => sprintf( esc_html__( 'Prevents you and your contributors to embed external content in %s.', 'oembed-manager' ), get_bloginfo( 'name' ) ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_consumer_misc_section', 'oemm_consumer_misc_disable_consumer' );
		add_settings_field(
			'oemm_consumer_misc_mode',
			esc_html__( 'Display', 'apcu-manager' ),
			[ $form, 'echo_field_select' ],
			'oemm_consumer_misc_section',
			'oemm_consumer_misc_section',
			[
				'list'        => $this->get_consumer_modes(),
				'id'          => 'oemm_consumer_misc_mode',
				'value'       => Option::site_get( 'consumer_mode' ),
				'description' => esc_html__( 'How the embedded content must be displayed.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_consumer_misc_section', 'oemm_consumer_misc_mode' );
	}

	/**
	 * Callback for consumer rules options section.
	 *
	 * @since 2.0.0
	 */
	public function consumer_rules_section_callback() {
		$form = new Form();
		foreach ( oEmbed::get_descriptions() as $integrations ) {
			if ( $integrations['enabled'] ) {
				foreach ( $integrations['items'] as $item ) {
					if ( $item['detected'] ) {
						switch ( $integrations['prefix'] ) {
							case 'consent':
								$action      = esc_html__( 'Don\'t display if consent is not given', 'oembed-manager' );
								$description = sprintf( esc_html__( 'If checked, no embedded content will be outputted as long as the plugin %s does not have collected the consent.', 'oembed-manager' ), '<strong>' . $item['name'] . '</strong>' );
								break;
							case 'cookie':
								$action      = esc_html__( 'Don\'t display if cookie consent is not given', 'oembed-manager' );
								$description = sprintf( esc_html__( 'If checked, no embedded content will be outputted as long as the plugin %s does not have collected the cookie consent.', 'oembed-manager' ), '<strong>' . $item['name'] . '</strong>' );
								break;
							case 'dnt':
								$action      = __( 'Honor <em>Do Not Track</em> requests', 'oembed-manager' );
								$description = sprintf( esc_html__( 'If checked, no embedded content will be outputted if the plugin %s detects a Do Not Track header.', 'oembed-manager' ), '<strong>' . $item['name'] . '</strong>' );
								break;
						}
						add_settings_field(
							'oemm_consumer_rules_block_' . $integrations['prefix'],
							$integrations['title'],
							[ $form, 'echo_field_checkbox' ],
							'oemm_consumer_rules_section',
							'oemm_consumer_rules_section',
							[
								'text'        => $action,
								'id'          => 'oemm_consumer_rules_block_' . $integrations['prefix'],
								'checked'     => Option::site_get( 'exception_' . $integrations['prefix'] . '_block' ),
								'description' => $description,
								'full_width'  => false,
								'enabled'     => true,
							]
						);
						register_setting( 'oemm_consumer_rules_section', 'oemm_consumer_rules_block_' . $integrations['prefix'] );
						if ( $item['execution']['use_param'] ) {
							add_settings_field(
								'oemm_consumer_rules_param_' . $integrations['prefix'],
								'',
								[ $form, 'echo_field_input_text' ],
								'oemm_consumer_rules_section',
								'oemm_consumer_rules_section',
								[
									'id'          => 'oemm_consumer_rules_param_' . $integrations['prefix'],
									'value'       => Option::site_get( 'exception_' . $integrations['prefix'] . '_param' ),
									'description' => sprintf( esc_html__( 'Item to verify - in doubt, see %s settings.', 'oembed-manager' ), '<strong>' . $item['name'] . '</strong>' ),
									'full_width'  => false,
									'enabled'     => true,
									'placeholder' => $item['execution']['help'],
								]
							);
							register_setting( 'oemm_consumer_rules_section', 'oemm_consumer_rules_param_' . $integrations['prefix'] );
						}
						add_settings_field(
							'oemm_consumer_rules_text_' . $integrations['prefix'],
							'',
							[ $form, 'echo_field_input_textarea' ],
							'oemm_consumer_rules_section',
							'oemm_consumer_rules_section',
							[
								'id'          => 'oemm_consumer_rules_text_' . $integrations['prefix'],
								'value'       => Option::site_get( 'exception_' . $integrations['prefix'] . '_text' ),
								'description' => __( 'Replacement text displayed while consent is not given.', 'oembed-manager' ) . ' ' . __( 'Could be plain HTML. Let blank to fully hide the placeholder.', 'oembed-manager' ),
								'columns'     => 100,
								'lines'       => 5,
								'enabled'     => true,
							]
						);
						register_setting( 'oemm_consumer_rules_section', 'oemm_consumer_rules_text_' . $integrations['prefix'] );
						break;
					}
				}
			}
		}
	}

	/**
	 * Get consumer modes.
	 *
	 * @since 2.0.0
	 */
	public function get_ttls() {
		$choices   = [];
		$choices[] = [ 1, esc_html__( '1 hour', 'oembed-manager' ) ];
		$choices[] = [ 24, esc_html__( '1 day', 'oembed-manager' ) ];
		$choices[] = [ 168, esc_html__( '1 week', 'oembed-manager' ) ];
		$choices[] = [ 720, esc_html__( '1 month', 'oembed-manager' ) ];
		$choices[] = [ 8736, esc_html__( '1 year', 'oembed-manager' ) ];
		return $choices;
	}

	/**
	 * Get consumer timeouts.
	 *
	 * @since 2.0.0
	 */
	public function get_timeouts() {
		$choices = [];
		foreach ( [ 5, 10, 20, 40, 60 ] as $time ) {
			$choices[] = [ $time, sprintf( esc_html__( '%s seconds', 'oembed-manager' ), $time ) ];
		}
		return $choices;
	}

	/**
	 * Get consumer sizes.
	 *
	 * @since 2.0.0
	 */
	public function get_sizes() {
		$choices = [];
		foreach ( [ 75, 150, 300, 600 ] as $time ) {
			$choices[] = [ $time, sprintf( esc_html__( '%s kilobytes', 'oembed-manager' ), $time ) ];
		}
		return $choices;
	}

	/**
	 * Callback for consumer advanced options section.
	 *
	 * @since 2.0.0
	 */
	public function consumer_advanced_section_callback() {
		$form = new Form();
		add_settings_field(
			'oemm_consumer_advanced_clickable',
			esc_html__( 'Links', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_consumer_advanced_section',
			'oemm_consumer_advanced_section',
			[
				'text'        => esc_html__( 'Clickable URL', 'oembed-manager' ),
				'id'          => 'oemm_consumer_advanced_clickable',
				'checked'     => Option::site_get( 'advanced_clickable' ),
				'description' => esc_html__( 'If WordPress outputs oEmbed URLs, transform them into clickable links.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_consumer_advanced_section', 'oemm_consumer_advanced_clickable' );

		add_settings_field(
			'oemm_consumer_advanced_ttl',
			esc_html__( 'Cache', 'apcu-manager' ),
			[ $form, 'echo_field_select' ],
			'oemm_consumer_advanced_section',
			'oemm_consumer_advanced_section',
			[
				'list'        => $this->get_ttls(),
				'id'          => 'oemm_consumer_advanced_ttl',
				'value'       => Option::site_get( 'advanced_ttl' ),
				'description' => esc_html__( 'How long the embedded content is cached by WordPress.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_consumer_advanced_section', 'oemm_consumer_advanced_ttl' );
		add_settings_field(
			'oemm_consumer_advanced_timeout',
			esc_html__( 'Connection timeout', 'apcu-manager' ),
			[ $form, 'echo_field_select' ],
			'oemm_consumer_advanced_section',
			'oemm_consumer_advanced_section',
			[
				'list'        => $this->get_timeouts(),
				'id'          => 'oemm_consumer_advanced_timeout',
				'value'       => Option::site_get( 'advanced_timeout' ),
				'description' => esc_html__( 'How long WordPress can wait the external website when fetching content.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_consumer_advanced_section', 'oemm_consumer_advanced_timeout' );
		add_settings_field(
			'oemm_consumer_advanced_size',
			esc_html__( 'Maximum size', 'apcu-manager' ),
			[ $form, 'echo_field_select' ],
			'oemm_consumer_advanced_section',
			'oemm_consumer_advanced_section',
			[
				'list'        => $this->get_sizes(),
				'id'          => 'oemm_consumer_advanced_size',
				'value'       => Option::site_get( 'advanced_size' ),
				'description' => esc_html__( 'How much WordPress can retrieve of the original content.', 'oembed-manager' ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_consumer_advanced_section', 'oemm_consumer_advanced_size' );
	}

	/**
	 * Callback for producer options section.
	 *
	 * @since 2.0.0
	 */
	public function producer_section_callback() {
		$form = new Form();
		add_settings_field(
			'oemm_producer_disable_producer',
			__( 'oEmbed producer', 'oembed-manager' ),
			[ $form, 'echo_field_checkbox' ],
			'oemm_producer_section',
			'oemm_producer_section',
			[
				'text'        => esc_html__( 'Disabled', 'oembed-manager' ),
				'id'          => 'oemm_producer_disable_producer',
				'checked'     => Option::site_get( 'disable_producer' ),
				'description' => sprintf( esc_html__( 'Prevents other websites to embed content from %s.', 'oembed-manager' ), get_bloginfo( 'name' ) ),
				'full_width'  => false,
				'enabled'     => true,
			]
		);
		register_setting( 'oemm_producer_section', 'oemm_producer_disable_producer' );
	}


}
