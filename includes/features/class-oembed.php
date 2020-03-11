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

use Oemm\System\Logger;
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

}
