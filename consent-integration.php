<?php


/**
 * This class is responsible of the "consent" integration management.
 *
 * @author Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 1.0.0
 */
class OEMMConsentIntegration extends OEMMIntegration{

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
        $result = $this->get_template();
        $result['id'] = 'gdpr';
        $result['name'] = 'GDPR';
        $result['url'] = 'https://wordpress.org/plugins/gdpr/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon.svg';
        $result['backend_detection']['rule'] = 'function_exists';
        $result['backend_detection']['name'] = 'has_consent';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'has_consent';
        $result['execution']['rule'] = 'call_user_func';
        $result['execution']['name'] = 'has_consent';
        $result['execution']['use_param'] = true;
        $result['execution']['help'] = __('consent ID', 'oembed-manager');
        $this->integrations[] = $result;
    }

}