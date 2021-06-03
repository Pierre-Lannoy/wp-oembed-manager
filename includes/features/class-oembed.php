<?php
/**
 * oEmbed
 *
 * Handles oEmbed process.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin\Feature;


use Oemm\System\Option;
use Oemm\Plugin\Feature\Integration;
use Oemm\Plugin\Feature\Cookie;
use Oemm\Plugin\Feature\DNT;
use Oemm\Plugin\Feature\Consent;

/**
 * This class is responsible of the oEmbed operations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class oEmbed {

	/**
	 * Construct the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

	}

	/**
	 * Init the class.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		global $pagenow;
		// phpcs:ignore
		if ( ( 'admin.php' === $pagenow ) && ( 'oemm-tools' === ( array_key_exists( 'page', $_GET ) ? (string) filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING ) : '') ) ) {
			self::set_consumer( true );
		} else {
			self::set_consumer();
		}
		self::set_producer();
	}

	/**
	 * Get all the integrations.
	 *
	 * @return array An array containing all integrations.
	 * @since 1.0.0
	 */
	public static function get_descriptions() {
		$integrations   = [];
		$integrations[] = [
			'title'       => __( 'Consent management', 'oembed-manager' ),
			'description' => __( 'These plugins help <strong>oEmbed Manager</strong> to build exception rules, based on the optional consent of your visitors:', 'oembed-manager' ),
			'prefix'      => 'consent',
			'items'       => Consent::init()->detect()->get_items(),
			'enabled'     => Consent::init()->detect()->count_activated() !== 0,
		];
		$integrations[] = [
			'title'       => __( 'Cookies management', 'oembed-manager' ),
			'description' => __( 'These plugins help <strong>oEmbed Manager</strong> to build exception rules, based on the consent of your visitors about cookies:', 'oembed-manager' ),
			'prefix'      => 'cookie',
			'items'       => Cookie::init()->detect()->get_items(),
			'enabled'     => Cookie::init()->detect()->count_activated() !== 0,
		];
		$integrations[] = [
			'title'       => __( '<em>Do Not Track</em> policy', 'oembed-manager' ),
			'description' => __( 'These plugins help <strong>oEmbed Manager</strong> to build exception rules, based on the <em>Do Not Track</em> header sent by your visitors\' browser:', 'oembed-manager' ),
			'prefix'      => 'dnt',
			'items'       => DNT::init()->detect()->get_items(),
			'enabled'     => DNT::init()->detect()->count_activated() !== 0,
		];
		return $integrations;
	}

	/**
	 * Disable oEmbed rewrite rules.
	 *
	 * @param array $rules The WP rewrite rules.
	 * @return array The modified rules.
	 *
	 * @since 1.0.0
	 */
	public static function disable_rewrite_rules( $rules ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( strpos( $rewrite, 'embed=true' ) !== false ) {
				unset( $rules[ $rule ] );
			}
		}
		return $rules;
	}

	/**
	 * Remove oEmbed query vars.
	 *
	 * @since 1.0.0
	 */
	public static function remove_query_vars() {
		global $wp;
		$wp->public_query_vars = array_diff( $wp->public_query_vars, [ 'embed' ] );
	}

	/**
	 * Remove oEmbed autoembed.
	 *
	 * @since 1.0.0
	 */
	public static function remove_autoembed() {
		global $wp_embed;
		remove_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );
	}

	/**
	 * Remove oEmbed related plugins from TinyMCE.
	 *
	 * @param array $plugins List of TinyMCE plugins.
	 * @return array The modified list.
	 *
	 * @since 1.0.0
	 */
	public static function remove_tiny_mce_plugin( $plugins ) {
		return array_diff( $plugins, [ 'wpembed', 'wpview' ] );
	}

	/**
	 * Modify video oEmbed html output.
	 *
	 * @param string $html The (cached) HTML result, stored in post meta.
	 * @return string The modified HTML, ready to print.
	 *
	 * @since 1.1.0
	 */
	public static function modify_video_oembed_html( $html ) {
		return self::modify_oembed_html( $html );
	}

	/**
	 * Modify oEmbed html output.
	 *
	 * @param string $html The (cached) HTML result, stored in post meta.
	 * @param string $url The initial URL.
	 * @param array $attr An array of shortcode attributes.
	 * @param integer $post_id The post ID.
	 * @return string The modified HTML, ready to print.
	 *
	 * @since 1.0.0
	 */
	public static function modify_oembed_html( $html, $url = '', $attr = array(), $post_id = 0 ) {
		if ( (int) Option::site_get( 'consumer_mode' ) === 2 ) {
			return '';
		}
		if ( (int) Option::site_get( 'consumer_mode' ) === 1 ) {
			$output = $url;
			if ( Option::site_get( 'advanced_clickable' ) ) {
				$output = '<a href="' . $url . '">' . $url . '</a>';
			}
			return $output;
		}

		// Verify consent exclusions
		if ( Option::site_get( 'exception_consent_block' ) ) {
			if ( ! Consent::init()->evaluate( Option::site_get( 'exception_consent_id' ), Option::site_get( 'exception_consent_param', null ) ) ) {
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed content blocked. Reason: consent not collected. Url: ' . $url );
				return Option::site_get( 'exception_consent_text' );
			}
		}

		// Verify cookie exclusions
		if ( Option::site_get( 'exception_cookie_block' ) ) {
			if ( ! Cookie::init()->evaluate( Option::site_get( 'exception_cookie_id' ), Option::site_get( 'exception_cookie_param', null ) ) ) {
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed content blocked. Reason: cookie consent not collected. Url: ' . $url );
				return Option::site_get( 'exception_cookie_text' );
			}
		}

		// Verify DNT exclusions
		if ( Option::site_get( 'exception_dnt_block' ) ) {
			if ( ! DNT::init()->evaluate( Option::site_get( 'exception_dnt_id' ), Option::site_get( 'exception_dnt_param', null ) ) ) {
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed content blocked. Reason: Do Not Track headers detected. Url: ' . $url );
				return Option::site_get( 'exception_dnt_text' );
			}
		}
		\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed content allowed. Url: ' . $url );
		return $html;
	}

	/**
	 * Get the cache ttl for oEmbed (consumer).
	 *
	 * @return integer The cache ttl in seconds.
	 *
	 * @since 1.0.0
	 */
	public static function get_cache_ttl() {
		return (int) round( Option::site_get( 'advanced_ttl' ) * 3600 );
	}

	/**
	 * Add/modify some args for fetching external websites.
	 *
	 * @param array $args The current args.
	 * @return array The modified args.
	 *
	 * @since 1.0.0
	 */
	public static function modify_fetch_args( $args ) {
		if ( ! isset( $args ) ) {
			$args = array();
		}
		$args['timeout']             = Option::site_get( 'advanced_timeout' );
		$args['limit_response_size'] = (int) round( Option::site_get( 'advanced_size' ) * 1024 );
		return $args;
	}

	/**
	 * Reset the array of post types to cache oEmbed results for.
	 *
	 * @param array $args The current args.
	 * @return array The modified args.
	 * @since 2.0.0
	 */
	public static function embed_cache_oembed_types( $args ) {
		return [ 'public' => true ];
	}

	/**
	 * Set consumer mode.
	 *
	 * @param boolean   $bypass     Optional. Bypass the settings.
	 * @since 1.0.0
	 */
	public static function set_consumer( $bypass = false ) {
		if ( $bypass ) {
			//add_filter( 'embed_cache_oembed_types', [ self::class, 'embed_cache_oembed_types' ], PHP_INT_MAX );
			\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'Consumer settings bypassed.' );
		} else {
			if ( Option::site_get( 'disable_consumer' ) ) {
				self::remove_autoembed();
				remove_filter( 'the_content_feed', '_oembed_filter_feed_content' );
				remove_action( 'plugins_loaded', 'wp_maybe_load_embeds', 0 );
				add_filter( 'pre_option_embed_autourls', '__return_false' );
				add_filter( 'embed_oembed_discover', '__return_false' );
				remove_action( 'wp_head', 'wp_oembed_add_host_js' );
				remove_filter( 'excerpt_more', 'wp_embed_excerpt_more', 20 );
				remove_filter( 'the_excerpt_embed', 'wptexturize' );
				remove_filter( 'the_excerpt_embed', 'convert_chars' );
				remove_filter( 'the_excerpt_embed', 'wpautop' );
				remove_filter( 'the_excerpt_embed', 'shortcode_unautop' );
				remove_filter( 'the_excerpt_embed', 'wp_embed_excerpt_attachment' );
				remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result' );
				remove_filter( 'oembed_response_data', 'get_oembed_response_data_rich' );
				remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result' );
				add_filter( 'tiny_mce_plugins', [ self::class, 'remove_tiny_mce_plugin' ] );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed consumer disabled.' );
			} else {
				add_filter( 'embed_oembed_html', [ self::class, 'modify_oembed_html' ], PHP_INT_MAX, 4 );
				add_filter( 'video_embed_html', [ self::class, 'modify_video_oembed_html' ], PHP_INT_MAX, 1 );
				add_filter( 'oembed_ttl', [ self::class, 'get_cache_ttl' ], PHP_INT_MAX );
				add_filter( 'oembed_remote_get_args', [ self::class, 'modify_fetch_args' ], PHP_INT_MAX );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed consumer enabled.' );
			}
		}
	}

	/**
	 * Set producer mode.
	 *
	 * @since 1.0.0
	 */
	public static function set_producer() {
		if ( Option::site_get( 'disable_producer' ) ) {
			self::remove_query_vars();
			remove_action( 'rest_api_init', 'wp_oembed_register_route' );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_filter( 'rest_pre_serve_request', '_oembed_rest_pre_serve_request' );
			remove_action( 'embed_head', 'enqueue_embed_scripts', 1 );
			remove_action( 'embed_head', 'print_embed_styles' );
			remove_action( 'embed_head', 'wp_print_head_scripts', 20 );
			remove_action( 'embed_head', 'wp_print_styles', 20 );
			remove_action( 'embed_head', 'wp_no_robots' );
			remove_action( 'embed_head', 'rel_canonical' );
			remove_action( 'embed_head', 'locale_stylesheet', 30 );
			remove_action( 'embed_content_meta', 'print_embed_comments_button' );
			remove_action( 'embed_content_meta', 'print_embed_sharing_button' );
			remove_action( 'embed_footer', 'print_embed_sharing_dialog' );
			remove_action( 'embed_footer', 'print_embed_scripts' );
			remove_action( 'embed_footer', 'wp_print_footer_scripts', 20 );
			add_filter( 'rewrite_rules_array', [ self::class, 'disable_rewrite_rules' ] );
			\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed producer disabled.' );
		} else {
			remove_filter( 'rewrite_rules_array', [ self::class, 'disable_rewrite_rules' ] );
			\DecaLog\Engine::eventsLogger( OEMM_SLUG )->debug( 'oEmbed producer enabled.' );
		}
	}

	/**
	 * Purge oEmbed caches.
	 *
	 * @since 1.0.0
	 */
	private static function purge_caches() {
		global $wpdb;
		$count = $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%_oembed_%'" );
		if ( false === $count ) {
			$count = 0;
			\DecaLog\Engine::eventsLogger( OEMM_SLUG )->warning( 'Unable to purge oEmbed cache.' );
		} else {
			$count = (int) ( $count / 2 );
			\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( sprintf( 'oEmbed cache purged: %d item(s) deleted.', $count ) );
		}
		return $count;
	}

	/**
	 * Purge oEmbed cache.
	 *
	 * @param null|integer|array $id   Optional. The post(s) id(s) to clear.
	 * @since 1.0.0
	 */
	public static function purge_cache( $id = null) {
		if ( isset( $id ) ) {
			global $wp_embed;
			if ( is_int( $id ) ) {
				$wp_embed->delete_oembed_caches( $id );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( sprintf( 'oEmbed cache purged for %d post(s).', 1 ) );
			}
			if ( is_array( $id ) ) {
				foreach ( $id as $i ) {
					$wp_embed->delete_oembed_caches( $i );
				}
				if ( 0 < count( $id ) ) {
					\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( sprintf( 'oEmbed cache purged for %d post(s).', count( $id ) ) );
				}
			}
		} else {
			return self::purge_caches();
		}
	}

	/**
	 * Triggers a caching of all oEmbed results.
	 *
	 * @param int $post_ID Post ID to do the caching for.
	 */
	private static function cache_oembed( $post_ID ) {
		global $wp_embed;
		$post = get_post( $post_ID );
		if ( ! empty( $post->post_content ) ) {
			$wp_embed->post_ID  = $post->ID;
			$wp_embed->usecache = false;

			$content = $wp_embed->run_shortcode( $post->post_content );
			$wp_embed->autoembed( $content );

			$wp_embed->usecache = true;
		}
	}

	/**
	 * set oEmbed caches.
	 *
	 * @since 1.0.0
	 */
	private static function set_caches() {
		global $wpdb;
		$posts = $wpdb->get_results( 'SELECT DISTINCT ID FROM ' . $wpdb->posts . " WHERE post_status = 'publish' ORDER BY ID DESC", ARRAY_A );
		foreach ( $posts as $post ) {
			self::cache_oembed( $post['ID'] );
		}
		if ( 0 < count( $posts ) ) {
			\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( sprintf( '%d post(s) have been checked to update/create oEmbed cache if needed.', count( $posts ) ) );
		}
	}

	/**
	 * Set oEmbed cache.
	 *
	 * @param null|integer|array $id   Optional. The post(s) id(s) to cache.
	 * @since 1.0.0
	 */
	public static function set_cache( $id = null) {
		if ( isset( $id ) ) {
			global $wp_embed;
			if ( is_int( $id ) ) {
				$wp_embed->delete_oembed_caches( $id );
				$wp_embed->cache_oembed( $id );
				\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( sprintf( 'oEmbed cache updated/created for %d post(s).', 1 ) );
			}
			if ( is_array( $id ) ) {
				foreach ( $id as $i ) {
					$wp_embed->delete_oembed_caches( $i );
					$wp_embed->cache_oembed( $i );
				}
				if ( 0 < count( $id ) ) {
					\DecaLog\Engine::eventsLogger( OEMM_SLUG )->info( sprintf( 'oEmbed cache updated/created for %d post(s).', count( $id ) ) );
				}
			}
		} else {
			self::purge_caches();
			self::set_caches();
		}
	}


	/**
	 * Get cached content.
	 *
	 * @return array    The detail of cached items.
	 * @since 1.0.0
	 */
	public static function get_cached() {
		$result = [];
		global $wpdb;
		$sql = 'SELECT * FROM ' . $wpdb->postmeta . " WHERE meta_key LIKE '%_oembed_%' ORDER BY post_id DESC";
		// phpcs:ignore
		$caches =  $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $caches as $cache ) {
			if ( ! array_key_exists( $cache['post_id'], $result ) ) {
				$result[ $cache['post_id'] ]['count'] = 0;
				$result[ $cache['post_id'] ]['size']  = 0;
				$result[ $cache['post_id'] ]['ttl']   = time();
			}
			if ( 0 === strpos( $cache['meta_key'], '_oembed_time' ) ) {
				$result[ $cache['post_id'] ]['ttl'] = $cache['meta_value'];
			} else {
				$result[ $cache['post_id'] ]['count'] += 1;
				$result[ $cache['post_id'] ]['size']  += strlen( $cache['meta_value'] );
			}
		}
		return $result;
	}

}
