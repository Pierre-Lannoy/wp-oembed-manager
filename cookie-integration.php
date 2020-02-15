<?php


/**
 * This class is responsible of the "third-party cookies" integration management.
 *
 * @author Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 1.0.0
 */
class OEMMCookieIntegration extends OEMMIntegration{

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function initialize() {
        $this->add_uk_cookie_consent();
        $this->add_cookie_notice();
        $this->add_eu_cookie_law();
        $this->add_gdpr();
        $this->add_gdpr_cookie_compliance();
        $this->add_cookie_law_info();
    }

    /**
     * Add a managed integration for "Cookie Consent" / Catapult_Themes.
     *
     * @since 1.2.0
     */
    private function add_uk_cookie_consent() {
        $result = $this->get_template();
        $result['id'] = 'uk-cookie-consent';
        $result['name'] = 'Cookie Consent';
        $result['url'] = 'https://wordpress.org/plugins/uk-cookie-consent/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon-128x128.png';
        $result['backend_detection']['rule'] = 'function_exists';
        $result['backend_detection']['name'] = 'ctcc_create_policy_page';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'ctcc_create_policy_page';
        $result['execution']['rule'] = 'cookie';
        $result['execution']['name'] = 'catAccCookies';
        $result['execution']['format'] = 'raw-single';
        $result['execution']['param'] = 'get_option';
        $result['execution']['value'] = 'ctcc_options_settings/cookie_version';
        $this->integrations[] = $result;
    }

    /**
     * Add a managed integration for "Cookie Notice for GDPR" / dFactory.
     *
     * @since 1.0.0
     */
    private function add_cookie_notice() {
        $result = $this->get_template();
        $result['id'] = 'cookie-notice';
        $result['name'] = 'Cookie Notice for GDPR';
        $result['url'] = 'https://wordpress.org/plugins/cookie-notice/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon-128x128.png';
        $result['backend_detection']['rule'] = 'function_exists';
        $result['backend_detection']['name'] = 'cn_cookies_accepted';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'cn_cookies_accepted';
        $result['execution']['rule'] = 'call_user_func';
        $result['execution']['name'] = 'cn_cookies_accepted';
        $this->integrations[] = $result;
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
        $result['backend_detection']['name'] = 'is_allowed_cookie';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'is_allowed_cookie';
        $result['execution']['rule'] = 'call_user_func';
        $result['execution']['name'] = 'is_allowed_cookie';
        $result['execution']['use_param'] = true;
        $result['execution']['help'] = __('cookie name', 'oembed-manager');
        $this->integrations[] = $result;
    }

    /**
     * Add a managed integration for "EU Cookie Law" / Alex Moss, Marco Milesi, Peadig, Shane Jones.
     *
     * @since 1.1.0
     */
    private function add_eu_cookie_law() {
        $result = $this->get_template();
        $result['id'] = 'eu-cookie-law';
        $result['name'] = 'EU Cookie Law';
        $result['url'] = 'https://wordpress.org/plugins/eu-cookie-law/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon-128x128.jpg';
        $result['backend_detection']['rule'] = 'function_exists';
        $result['backend_detection']['name'] = 'peadig_eucookie_options';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'ecl_get_cookie_domain';
        $result['execution']['rule'] = 'call_user_func';
        $result['execution']['name'] = 'cookie_accepted';
        $this->integrations[] = $result;
    }

    /**
     * Add a managed integration for "GDPR Cookie Compliance" / Moove Agency.
     *
     * @since 1.2.0
     */
    private function add_gdpr_cookie_compliance() {
        $result = $this->get_template();
        $result['id'] = 'gdpr-cookie-compliance';
        $result['name'] = 'GDPR Cookie Compliance';
        $result['url'] = 'https://wordpress.org/plugins/gdpr-cookie-compliance/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon-128x128.png';
        $result['backend_detection']['rule'] = 'function_exists';
        $result['backend_detection']['name'] = 'moove_gdpr_activate';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'moove_gdpr_activate';
        $result['execution']['rule'] = 'cookie';
        $result['execution']['name'] = 'moove_gdpr_popup';
        $result['execution']['format'] = 'json';
        $result['execution']['param'] = 'thirdparty';
        $result['execution']['value'] = '1';
        $this->integrations[] = $result;
    }

    /**
     * Add a managed integration for "GDPR Cookie Consent" / WebToffee.
     *
     * @since 1.2.0
     */
    private function add_cookie_law_info() {
        $result = $this->get_template();
        $result['id'] = 'cookie-law-info';
        $result['name'] = 'GDPR Cookie Consent';
        $result['url'] = 'https://wordpress.org/plugins/cookie-law-info/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon-128x128.png';
        $result['backend_detection']['rule'] = 'function_exists';
        $result['backend_detection']['name'] = 'run_cookie_law_info';
        $result['frontend_detection']['rule'] = 'function_exists';
        $result['frontend_detection']['name'] = 'run_cookie_law_info';
        $result['execution']['rule'] = 'cookie';
        $result['execution']['name'] = 'viewed_cookie_policy';
        $result['execution']['format'] = 'raw-single';
        $result['execution']['value'] = 'yes';
        $this->integrations[] = $result;
    }

}