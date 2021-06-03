<?php
/**
 * integrations
 *
 * Handles integrations.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin\Feature;


use Oemm\System\Option;

/**
 * This class is responsible of the integrations management.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
abstract class Integration {

	protected $plugin_url;
	protected $already_detected = false;
	protected $integrations     = [];

	/**
	 * Initializes the class.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		static $instance = null;
		if ( ! $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Construct the class and set its properties.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	abstract public function initialize();

	/**
	 * Detect a specific integration.
	 *
	 * @param array $integration The integration to test.
	 * @param boolean $frontend Optional. Is the detection takes place in frontend rendering?
	 * @return boolean True if it's detected, false otherwise
	 * @since 1.0.0
	 */
	private function x_detect( $integration, $frontend = false ) {
		$detected = false;
		$target   = 'backend_detection';
		if ( $frontend ) {
			$target = 'frontend_detection';
		}
		switch ( $integration[ $target ]['rule'] ) {
			case 'function_exists':
				if ( '' !== $integration[ $target ]['name'] ) {
					$detected = function_exists( $integration[ $target ]['name'] );
				}
				break;
			case 'defined':
				if ( '' !== $integration[ $target ]['name'] ) {
					$detected = defined( $integration[ $target ]['name'] );
				}
				break;
		}
		return $detected;
	}

	/**
	 * Detect a specific integration.
	 *
	 * @param string $cookie The cookie value.
	 * @param string $key The key to extract.
	 * @param string $format The cookie format.
	 * @return string The value.
	 * @since 1.2.0
	 */
	private function x_extract_cookie_value( $cookie, $key, $format ) {
		$result = '';
		switch ( $format ) {
			case 'json':
				$cookie = str_replace( '\"', '"', $cookie );
				$cookie = json_decode( $cookie, true );
				if ( is_array( $cookie ) ) {
					if ( array_key_exists( $key, $cookie ) ) {
						$result = (string) $cookie[ $key ];
					}
				}
				break;
			case 'raw-single':
				$result = (string) $cookie;
				break;
		}
		return $result;
	}

	/**
	 * Evaluate a specific integration.
	 *
	 * @param array $integration The integration to evaluate.
	 * @param string $param The param to evaluate.
	 * @return boolean True if it's correctly evaluated, false otherwise
	 * @since 1.0.0
	 */
	private function x_evaluate( $integration, $param = null ) {
		$evaluation = false;
		switch ( $integration['execution']['rule'] ) {
			case 'call_user_func':
				if ( '' !== $integration['execution']['name'] ) {
					if ( function_exists( $integration['execution']['name'] ) ) {
						if ( $param ) {
							$evaluation = call_user_func( $integration['execution']['name'], $param );
						} else {
							$evaluation = call_user_func( $integration['execution']['name'] );
						}
					}
				}
				break;
			case 'constant_value':
				if ( '' !== $integration['execution']['name'] ) {
					if ( defined( $integration['execution']['name'] ) ) {
						if ( $param ) {
							$evaluation = ( constant( $integration['execution']['name'] ) === $param );
						} else {
							$evaluation = true;
						}
					}
				}
				break;
			case 'cookie':
				if ( '' !== $integration['execution']['name'] ) {
					if ( '' !== $integration['execution']['param'] || 'raw-single' === $integration['execution']['format'] ) {
						if ( isset( $_COOKIE ) && is_array( $_COOKIE ) ) {
							if ( array_key_exists( $integration['execution']['name'], $_COOKIE ) ) {
								$comp_val = (string) $integration['execution']['value'];
								if ( 'get_option' === $integration['execution']['param'] ) {
									$p        = explode( '/', $integration['execution']['value'] );
									$comp_val = null;
									if ( count( $p ) > 0 ) {
										$comp_val = get_option( $p[0] );
									}
									if ( count( $p ) > 1 && isset( $comp_val ) && is_array( $comp_val ) ) {
										if ( array_key_exists( $p[1], $comp_val ) ) {
											$comp_val = $comp_val[ $p[1] ];
										}
									}
								}
								$evaluation = ( $comp_val === $this->x_extract_cookie_value( $_COOKIE[ $integration['execution']['name'] ], $integration['execution']['param'], $integration['execution']['format'] ) );
							}
						}
					}
				}
				break;
		}
		if ( $integration['execution']['reverted'] ) {
			$evaluation = ! $evaluation;
		}
		return $evaluation;
	}


	/**
	 * Get an integration template.
	 *
	 * @return array The integration template.
	 * @since 1.0.0
	 */
	protected function get_template() {
		return [
			'id'                 => 'none',
			'name'               => '',
			'url'                => '',
			'image'              => '',
			'detected'           => false,
			'backend_detection'  => [
				'rule' => '',
				'name' => '',
			],
			'frontend_detection' => [
				'rule' => '',
				'name' => '',
			],
			'execution'          => [
				'rule'      => '',
				'name'      => '',
				'param'     => '',
				'value'     => '',
				'format'    => '',
				'use_param' => false,
				'reverted'  => false,
				'help'      => '',
			],
		];
	}

	/**
	 * Get a specific integration.
	 *
	 * @param string $id The integration id.
	 * @return boolean|array The integration array if found, false otherwise.
	 * @since 1.0.0
	 */
	public function get( $id ) {
		$result = false;
		foreach ( $this->integrations as $integration ) {
			if ( $integration['id'] === $id ) {
				$result = $integration;
				break;
			}
		}
		return $result;
	}

	/**
	 * Evaluate a specific integration.
	 *
	 * @param string $id The integration id.
	 * @param string $param The param to evaluate.
	 * @return boolean The result of the evaluation.
	 * @since 1.0.0
	 */
	public function evaluate( $id, $param = null ) {
		$result = true;
		if ( false !== $item = $this->get( $id ) ) {
			if ( $this->x_detect( $item, true ) ) {
				if ( ! $item['execution']['use_param'] ) {
					$param = null;
					if ( '' !== $item['execution']['param'] ) {
						$param = $item['execution']['param'];
					}
				}
				$result = $this->x_evaluate( $item, $param );
			}
		}
		return $result;
	}

	/**
	 * Get all integrations.
	 *
	 * @return array The integration array.
	 * @since 1.0.0
	 */
	public function get_items() {
		return $this->integrations;
	}

	/**
	 * Detect all integrations.
	 *
	 * @return object $this
	 * @since 1.0.0
	 */
	public function detect() {
		if ( $this->already_detected ) {
			return $this;
		}
		foreach ( $this->integrations as &$integration ) {
			$integration['detected'] = $this->x_detect( $integration );
		}
		$this->already_detected = true;
		return $this;
	}

	/**
	 * Count activated integrations.
	 *
	 * @return integer Count of activated integrations.
	 * @since 1.0.0
	 */
	public function count_activated() {
		if ( ! $this->already_detected ) {
			$this->detect();
		}
		$result = 0;
		foreach ( $this->integrations as $integration ) {
			if ( $integration['detected'] ) {
				++$result;
			}
		}
		return $result;
	}

}
