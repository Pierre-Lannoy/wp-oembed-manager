<?php
/**
 * Options handling
 *
 * Handles all options operations for the plugin.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\System;

use Oemm\System\Environment;

/**
 * Define the options functionality.
 *
 * Handles all options operations for the plugin.
 *
 * @package System
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Option {

	/**
	 * The list of defaults options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $defaults    The defaults list.
	 */
	private static $defaults = [];

	/**
	 * The list of network-wide options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $network    The network-wide list.
	 */
	private static $network = [];

	/**
	 * The list of site options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $site    The site list.
	 */
	private static $site = [];

	/**
	 * The list of private options.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array    $private    The private options list.
	 */
	private static $private = [];

	/**
	 * Set the defaults options.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$defaults['use_cdn']                 = false;
		self::$defaults['script_in_footer']        = false;
		self::$defaults['display_nag']             = false;
		self::$defaults['nags']                    = [];
		self::$defaults['version']                 = '0.0.0';
		self::$defaults['last_check']              = [];
		self::$defaults['history']                 = 21;
		self::$defaults['analytics']               = true;
		self::$network                             = [ 'version', 'use_cdn', 'script_in_footer', 'display_nag', 'analytics', 'history' ];
		self::$defaults['disable_producer']        = false;
		self::$defaults['disable_consumer']        = false;
		self::$defaults['consumer_mode']           = 0;
		self::$defaults['advanced_clickable']      = false;
		self::$defaults['advanced_ttl']            = 24;   //hours
		self::$defaults['advanced_timeout']        = 5;    //seconds
		self::$defaults['advanced_size']           = 150;  //Kb
		self::$defaults['exception_consent_block'] = false;
		self::$defaults['exception_cookie_block']  = false;
		self::$defaults['exception_dnt_block']     = false;
		self::$defaults['exception_consent_param'] = '';
		self::$defaults['exception_cookie_param']  = '';
		self::$defaults['exception_dnt_param']     = '';
		self::$defaults['exception_consent_id']    = '';
		self::$defaults['exception_cookie_id']     = '';
		self::$defaults['exception_dnt_id']        = '';
		self::$defaults['exception_consent_text']  = '<p>' . __( 'In order to respect your opposition to our privacy policy, this embedded content have been disabled.', 'oembed-manager' ) . '</p>';
		self::$defaults['exception_cookie_text']   = '<p>' . __( 'In order to respect your choice about cookies, this embedded content have been disabled.', 'oembed-manager' ) . '</p>';
		self::$defaults['exception_dnt_text']      = '<p>' . __( 'In order to honor the <em>Do Not Track</em> request sent by your browser, this embedded content have been disabled to prevent third-party tracking cookies.', 'oembed-manager' ) . '</p>';
		self::$site                                = [ 'disable_producer', 'disable_consumer', 'consumer_mode', 'advanced_clickable', 'advanced_ttl', 'advanced_timeout', 'advanced_size', 'exception_consent_block', 'exception_cookie_block', 'exception_dnt_block', 'exception_consent_param', 'exception_cookie_param', 'exception_dnt_param', 'exception_consent_id', 'exception_cookie_id', 'exception_dnt_id', 'exception_consent_text', 'exception_cookie_text', 'exception_dnt_text' ];
	}

	/**
	 * Get the options infos for Site Health "info" tab.
	 *
	 * @since 1.0.0
	 */
	public static function debug_info() {
		$result = [];
		$si     = '[Site Option] ';
		$nt     = $si;
		if ( Environment::is_wordpress_multisite() ) {
			$nt = '[Network Option] ';
		}
		foreach ( self::$network as $opt ) {
			$val            = self::network_get( $opt );
			$result[ $opt ] = [
				'label'   => $nt . $opt,
				'value'   => is_bool( $val ) ? $val ? 1 : 0 : $val,
				'private' => in_array( $opt, self::$private, true ),
			];
		}
		foreach ( self::$site as $opt ) {
			$val            = self::site_get( $opt );
			$result[ $opt ] = [
				'label'   => $si . $opt,
				'value'   => is_bool( $val ) ? $val ? 1 : 0 : $val,
				'private' => in_array( $opt, self::$private, true ),
			];
		}
		return $result;
	}

	/**
	 * Get an option value for a site.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function site_get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) && ! isset( $default ) ) {
			$default = self::$defaults[ $option ];
		}
		$val = get_option( OEMM_PRODUCT_ABBREVIATION . '_' . $option, $default );
		if ( is_bool( $default ) ) {
			return (bool) $val;
		}
		return $val;
	}

	/**
	 * Get an option value for a network.
	 *
	 * @param   string  $option     Option name. Expected to not be SQL-escaped.
	 * @param   boolean $default    Optional. The default value if option doesn't exists.
	 * @return  mixed   The value of the option.
	 * @since 1.0.0
	 */
	public static function network_get( $option, $default = null ) {
		if ( array_key_exists( $option, self::$defaults ) && ! isset( $default ) ) {
			$default = self::$defaults[ $option ];
		}
		$val = get_site_option( OEMM_PRODUCT_ABBREVIATION . '_' . $option, $default );
		if ( is_bool( $default ) ) {
			return (bool) $val;
		}
		return $val;
	}

	/**
	 * Verify if an option exists.
	 *
	 * @param   string $option Option name. Expected to not be SQL-escaped.
	 * @return  boolean   True if the option exists, false otherwise.
	 * @since 1.0.0
	 */
	public static function site_exists( $option ) {
		return 'non_existent_option' !== get_option( OEMM_PRODUCT_ABBREVIATION . '_' . $option, 'non_existent_option' );
	}

	/**
	 * Verify if an option exists.
	 *
	 * @param   string $option Option name. Expected to not be SQL-escaped.
	 * @return  boolean   True if the option exists, false otherwise.
	 * @since 1.0.0
	 */
	public static function network_exists( $option ) {
		return 'non_existent_option' !== get_site_option( OEMM_PRODUCT_ABBREVIATION . '_' . $option, 'non_existent_option' );
	}

	/**
	 * Set an option value for a site.
	 *
	 * @param string      $option   Option name. Expected to not be SQL-escaped.
	 * @param mixed       $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @param string|bool $autoload Optional. Whether to load the option when WordPress starts up. For existing options,
	 *                              `$autoload` can only be updated using `update_option()` if `$value` is also changed.
	 *                              Accepts 'yes'|true to enable or 'no'|false to disable. For non-existent options,
	 *                              the default value is 'yes'. Default null.
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function site_set( $option, $value, $autoload = null ) {
		if ( false === $value ) {
			$value = 0;
		}
		return update_option( OEMM_PRODUCT_ABBREVIATION . '_' . $option, $value, $autoload );
	}

	/**
	 * Set an option value for a network.
	 *
	 * @param string $option   Option name. Expected to not be SQL-escaped.
	 * @param mixed  $value    Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
	 * @return boolean  False if value was not updated and true if value was updated.
	 * @since 1.0.0
	 */
	public static function network_set( $option, $value ) {
		if ( false === $value ) {
			$value = 0;
		}
		return update_site_option( OEMM_PRODUCT_ABBREVIATION . '_' . $option, $value );
	}

	/**
	 * Delete all options for a site.
	 *
	 * @return integer Number of deleted items.
	 * @since 1.0.0
	 */
	public static function site_delete_all() {
		global $wpdb;
		$result = 0;
		// phpcs:ignore
		$delete = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '" . OEMM_PRODUCT_ABBREVIATION . '_%' . "';" );
		foreach ( $delete as $option ) {
			if ( delete_option( $option ) ) {
				++$result;
			}
		}
		return $result;
	}

	/**
	 * Reset some options to their defaults.
	 *
	 * @since 1.0.0
	 */
	public static function reset_to_defaults() {
		self::network_set( 'use_cdn', self::$defaults['use_cdn'] );
		self::network_set( 'script_in_footer', self::$defaults['script_in_footer'] );
		self::network_set( 'display_nag', self::$defaults['display_nag'] );
		self::network_set( 'analytics', self::$defaults['analytics'] );
		self::network_set( 'history', self::$defaults['history'] );
		self::network_set( 'status', self::$defaults['status'] );
		self::network_set( 'info', self::$defaults['info'] );
	}

	/**
	 * Initializes the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	}
}

Option::init();
