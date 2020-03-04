<?php
/**
 * "consent" integration
 *
 * Handles "consent" integration.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

namespace Oemm\Plugin\Feature;

use Oemm\System\Logger;
use Oemm\System\Option;
use Oemm\Plugin\Feature\Integration;

/**
 * This class is responsible of the "consent" integration management.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */
class Consent extends Integration {

	/**
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		$this->add_gdpr();
	}

	/**
	 * Add a managed integration for "GDPR" / Trew Knowledge.
	 *
	 * @since 1.0.0
	 */
	private function add_gdpr() {
		$result                               = $this->get_template();
		$result['id']                         = 'gdpr';
		$result['name']                       = 'GDPR';
		$result['url']                        = 'https://wordpress.org/plugins/gdpr/';
		$result['image']                      = OEMM_ADMIN_DIR . 'medias/' . $result['id'] . '-icon.svg';
		$result['backend_detection']['rule']  = 'function_exists';
		$result['backend_detection']['name']  = 'has_consent';
		$result['frontend_detection']['rule'] = 'function_exists';
		$result['frontend_detection']['name'] = 'has_consent';
		$result['execution']['rule']          = 'call_user_func';
		$result['execution']['name']          = 'has_consent';
		$result['execution']['use_param']     = true;
		$result['execution']['help']          = __( 'consent ID', 'oembed-manager' );
		$this->integrations[]                 = $result;
	}

}
