<?php

/**
 * @author Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       oEmbed Manager
 * Plugin URI:        https://wordpress.org/plugins/oembed-manager/
 * Description:       Manage oEmbed capabilities of your website and take a new step in the GDPR compliance of your embedded content.
 * Version:           1.2.8
 * Author:            Pierre Lannoy
 * Author URI:        https://pierre.lannoy.fr
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       oembed-manager
 */

// If this file is called directly, abort.
if (!defined( 'WPINC')) {
    die;
}

require_once (__DIR__.'/integration.php');
require_once (__DIR__.'/consent-integration.php');
require_once (__DIR__.'/cookie-integration.php');
require_once (__DIR__.'/dnt-integration.php');

/**
 * Main class of the plugin.
 *
 * @since 1.0.0
 */
class OEmbedManager {

    private static $full_name = 'oEmbed Manager';
    private static $menu_name = 'oEmbed';
    public static $plugin_dir;
    public static $plugin_url;
    private static $lowest_priority = PHP_INT_MAX;
    private static $html_priority = PHP_INT_MAX;
    private static $oemm_disable_producer = 0;
    private static $oemm_disable_consumer = 0;
    private static $oemm_consumer_mode = 0;
    private static $oemm_advanced_clickable = 0;
    private static $oemm_advanced_ttl = 24;  //hours
    private static $oemm_advanced_timeout = 5;  //seconds
    private static $oemm_advanced_size = 150;  //Kb
    private static $oemm_exception_consent_block = 0;
    private static $oemm_exception_cookie_block = 0;
    private static $oemm_exception_dnt_block = 0;
    private static $oemm_exception_consent_param = '';
    private static $oemm_exception_cookie_param = '';
    private static $oemm_exception_dnt_param = '';
    private static $oemm_exception_consent_id = '';
    private static $oemm_exception_cookie_id = '';
    private static $oemm_exception_dnt_id = '';
    private static $oemm_exception_consent_text;
    private static $oemm_exception_cookie_text;
    private static $oemm_exception_dnt_text;

    public static function init() {
        static $instance = null;
        self::$oemm_exception_consent_text = '<p>' . __('In order to respect your opposition to our privacy policy, this embedded content have been disabled.', 'oembed-manager') . '</p>';
        self::$oemm_exception_cookie_text = '<p>' . __('In order to respect your choice about cookies, this embedded content have been disabled.', 'oembed-manager') . '</p>';
        self::$oemm_exception_dnt_text = '<p>' . __('In order to honor the <em>Do Not Track</em> request sent by your browser, this embedded content have been disabled to prevent third-party tracking cookies.', 'oembed-manager') . '</p>';
        if (!$instance) {
            self::$plugin_dir = plugin_dir_path(__FILE__);
            self::$plugin_url = plugin_dir_url(__FILE__);
            $instance = new OEmbedManager;
        }
        return $instance;
    }

    /**
     * Initializes the instance.
     *
     * @since 1.0.0
     */
    protected function __construct() {
        load_plugin_textdomain('oembed-manager');
        register_activation_hook(__FILE__, array($this,'plugin_activate'));
        register_deactivation_hook(__FILE__, array($this,'plugin_deactivate'));
        add_action('init', array($this, 'set_consumer'), self::$lowest_priority);
        add_action('init', array($this, 'set_producer'), self::$lowest_priority);
        add_action('admin_menu', array($this,'init_admin_menu'));
        add_action('admin_init', array($this,'init_settings_sections'));
        add_filter('plugin_action_links_' . plugin_basename( __FILE__), array($this, 'add_plugin_action_links'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);
    }

    /**
     * Set up the plugin environment upon activation.
     *
     * @since 1.0.0
     */
    public function plugin_activate() {
        // Initialize options
        $this->reset_producer();
        $this->reset_consumer();
    }

    /**
     * Cleans the plugin environment upon deactivation.
     *
     * @since 1.0.0
     */
    public function plugin_deactivate() {
        // Remove options
        delete_option('oemm_disable_producer');
        delete_option('oemm_disable_consumer');
        delete_option('oemm_consumer_mode');
        delete_option('oemm_advanced_clickable');
        delete_option('oemm_advanced_ttl');
        delete_option('oemm_advanced_timeout');
        delete_option('oemm_advanced_size');
        delete_option('oemm_exception_consent_block');
        delete_option('oemm_exception_cookie_block');
        delete_option('oemm_exception_dnt_block');
        delete_option('oemm_exception_consent_param');
        delete_option('oemm_exception_cookie_param');
        delete_option('oemm_exception_dnt_param');
        delete_option('oemm_exception_consent_id');
        delete_option('oemm_exception_cookie_id');
        delete_option('oemm_exception_dnt_id');
        delete_option('oemm_exception_consent_text');
        delete_option('oemm_exception_cookie_text');
        delete_option('oemm_exception_dnt_text');
        // Finalize
        remove_filter('rewrite_rules_array', array($this, 'disable_rewrite_rules'));
        remove_filter('embed_oembed_html', array($this, 'modify_oembed_html'), self::$html_priority);
        remove_filter('video_embed_html', array($this, 'modify_video_oembed_html'), self::$html_priority);
    }

    /**
     * Add links in the "Plugin" column on the Plugins page.
     *
     * @param array $links List of links to print in the "Plugin" column on the Plugins page.
     * @return array Extended list of links to print in the "Plugin" column on the Plugins page.
     * @since 1.0.0
     */
    public function add_plugin_action_links(array $links) {
        $links[] = '<a href="' . oemm_get_settings_page_url('oemm-settings') . '">' . __('Settings', 'oembed-manager') . '</a>';
        return $links;
    }
    /**
     * Add links in the "Description" column on the Plugins page.
     *
     * @param array  $links List of links to print in the "Description" column on the Plugins page.
     * @param string $file  Name of the plugin.
     * @return array Extended list of links to print in the "Description" column on the Plugins page.
     * @since 1.0.0
     */
    public function add_plugin_row_meta( array $links, $file ) {
        if ( plugin_basename( __FILE__) === $file ) {
            $links[] = '<a href="https://wordpress.org/support/plugin/oembed-manager">' . __('Support', 'oembed-manager') . '</a>';
            $links[] = '<a href="https://support.laquadrature.net/" title="' . esc_attr__( 'With your donation, support an advocacy group defending the rights and freedoms of citizens on the Internet.', 'oembed-manager') . '"><strong>' . __('Donate', 'oembed-manager') . '</strong></a>';
        }
        return $links;
    }

    /**
     * Set the items in the settings & tools menus.
     *
     * @since 1.0.0
     */
    public function init_admin_menu() {
        add_submenu_page('options-general.php', self::$full_name, self::$menu_name, apply_filters('oemm_manage_options_capability', 'manage_options'), 'oemm-settings', array($this, 'get_settings_page'));
    }

    /**
     * Initializes settings sections.
     *
     * @since 1.0.0
     */
    public function init_settings_sections() {
        add_settings_section('oemm_consumer_section', null, array($this, 'consumer_section_callback'), 'oemm_consumer');
        add_settings_section('oemm_exception_section', null, array($this, 'exception_section_callback'), 'oemm_exception');
        add_settings_section('oemm_advanced_section', null, array($this, 'advanced_section_callback'), 'oemm_advanced');
        add_settings_section('oemm_producer_section', null, array($this, 'producer_section_callback'), 'oemm_producer');
    }

    /**
     * Set consumer settings fields.
     *
     * @since 1.0.0
     */
    public function consumer_section_callback() {
        add_settings_field('oemm_disable_consumer', __('oEmbed consumer', 'oembed-manager'),
            array($this, 'oemm_disable_consumer_callback'), 'oemm_consumer', 'oemm_consumer_section', array());
        register_setting('oemm_consumer', 'oemm_disable_consumer');
        add_settings_field('oemm_consumer_mode', __('Display', 'oembed-manager'),
            array($this, 'oemm_consumer_mode_callback'), 'oemm_consumer', 'oemm_consumer_section', array());
        register_setting('oemm_consumer', 'oemm_consumer_mode');
    }

    /**
     * Set exception settings fields.
     *
     * @since 1.0.0
     */
    public function exception_section_callback() {
        $integration_list = $this->get_integrations();
        foreach ($integration_list as $integrations) {
            if ($integrations['enabled']) {
                foreach ($integrations['items'] as $item) {
                    if ($item['detected']) {
                        add_settings_field('oemm_exception_' . $integrations['prefix'] . '_block', $integrations['title'],
                            array($this, 'oemm_exception_' . $integrations['prefix'] . '_block_callback'), 'oemm_exception', 'oemm_exception_section', array($item));
                        register_setting('oemm_exception', 'oemm_exception_' . $integrations['prefix'] . '_block');
                        break;
                    }
                }
                add_settings_field('oemm_exception_' . $integrations['prefix'] . '_text', '',
                    array($this, 'oemm_exception_' . $integrations['prefix'] . '_text_callback'), 'oemm_exception', 'oemm_exception_section', array());
                register_setting('oemm_exception', 'oemm_exception_' . $integrations['prefix'] . '_text');
            }
        }
    }

    /**
     * Set advanced settings fields.
     *
     * @since 1.0.0
     */
    public function advanced_section_callback() {
        add_settings_field('oemm_advanced_clickable', __('Links', 'oembed-manager'),
            array($this, 'oemm_advanced_clickable_callback'), 'oemm_advanced', 'oemm_advanced_section', array());
        register_setting('oemm_advanced', 'oemm_advanced_clickable');
        add_settings_field('oemm_advanced_ttl', __('Cache', 'oembed-manager'),
            array($this, 'oemm_advanced_ttl_callback'), 'oemm_advanced', 'oemm_advanced_section', array());
        register_setting('oemm_advanced', 'oemm_advanced_ttl');
        add_settings_field('oemm_advanced_timeout', __('Connection timeout', 'oembed-manager'),
            array($this, 'oemm_advanced_timeout_callback'), 'oemm_advanced', 'oemm_advanced_section', array());
        register_setting('oemm_advanced', 'oemm_advanced_timeout');
        add_settings_field('oemm_advanced_size', __('Maximum size', 'oembed-manager'),
            array($this, 'oemm_advanced_size_callback'), 'oemm_advanced', 'oemm_advanced_section', array());
        register_setting('oemm_advanced', 'oemm_advanced_size');
    }

    /**
     * Echoes a check box to disable consumer.
     *
     * @since 1.0.0
     */
    public function oemm_disable_consumer_callback($args) {
        $output = $this->field_checkbox(__('Disabled', 'oembed-manager'), 'oemm_disable_consumer', (bool)get_option('oemm_disable_consumer', self::$oemm_disable_consumer), sprintf(__('Prevents you and your contributors to embed external content in %s.', 'oembed-manager'), get_bloginfo('name')));
        $output .= '<script language="javascript" type="text/javascript">';
        $output .= '  jQuery(document).ready(function($) {';
        $output .= '    $("#oemm_disable_consumer").change(function(){';
        $output .= '    var controls = $(".wrap :input[type!=\'hidden\']").not(".button").not("#oemm_disable_consumer");';
        $output .= '    if ($("#oemm_disable_consumer").prop("checked")) {controls.attr("disabled", "disabled");} else {controls.removeAttr("disabled");}';
        $output .= '    });';
        $output .= '    $("#oemm_consumer_mode").change(function(){';
        $output .= '      if ($("#oemm_consumer_mode").val() == 0) {';
        $output .= '        $(".oemm-exclusion-section").show();';
        $output .= '      }';
        $output .= '      else {';
        $output .= '        $(".oemm-exclusion-section").hide();';
        $output .= '      }';
        $output .= '    });';
        $output .= '    $("#oemm_disable_consumer").change();';
        $output .= '    $("#oemm_consumer_mode").change();';
        $output .= '  });';
        $output .= '</script>';
        echo $output;
    }

    /**
     * Echoes a select box to choose consumer mode.
     *
     * @since 1.0.0
     */
    public function oemm_consumer_mode_callback($args) {
        $choices = array();
        $choices[] = array(0, __('Styled and formated content', 'oembed-manager'));
        $choices[] = array(1, __('Just the URL', 'oembed-manager'));
        $choices[] = array(2, __('Nothing', 'oembed-manager'));
        echo $this->field_select($choices, get_option('oemm_consumer_mode', self::$oemm_consumer_mode), 'oemm_consumer_mode', __('How the embedded content must be displayed.', 'oembed-manager'));
    }

    /**
     * Echoes a check box to block consent requests.
     *
     * @since 1.0.0
     */
    public function oemm_exception_consent_block_callback($args) {
        $item = $args[0];
        $description = sprintf(__('If checked, no embedded content will be outputted as long as the plugin %s does not have collected the consent.', 'oembed-manager'), '<strong>' . $item['name'] . '</strong>');
        echo '<input type="hidden" name="oemm_exception_consent_id" value="' . $item['id'] . '">';
        if ($item['execution']['use_param']) {
            $check = __('Don\'t display if this consent is not given:', 'oembed-manager');
            echo $this->field_checkandtextbox($check, get_option('oemm_exception_consent_param', self::$oemm_exception_consent_param), 'oemm_exception_consent_block', 'oemm_exception_consent_param', (bool)get_option('oemm_exception_consent_block', self::$oemm_exception_consent_block), $description, $item['execution']['help']);
        }
        else {
            $check = __('Don\'t display if consent is not given', 'oembed-manager');
            echo $this->field_checkbox($check, 'oemm_exception_consent_block', (bool)get_option('oemm_exception_consent_block', self::$oemm_exception_consent_block), $description);
        }
    }

    /**
     * Echoes a text box to set text for blocked consent requests.
     *
     * @since 1.0.0
     */
    public function oemm_exception_consent_text_callback($args) {
        echo $this->field_text(get_option('oemm_exception_consent_text', self::$oemm_exception_consent_text), __('Replacement text', 'oembed-manager'), 'oemm_exception_consent_text',  __('Replacement text displayed while consent is not collected.', 'oembed-manager') . ' ' . __('Could be plain HTML. Let blank to fully hide the placeholder.', 'oembed-manager'));
    }

    /**
     * Echoes a check box to block cookie requests.
     *
     * @since 1.0.0
     */
    public function oemm_exception_cookie_block_callback($args) {
        $item = $args[0];
        $description = sprintf(__('If checked, no embedded content will be outputted as long as the plugin %s does not have collected the cookie consent.', 'oembed-manager'), '<strong>' . $item['name'] . '</strong>');
        echo '<input type="hidden" name="oemm_exception_cookie_id" value="' . $item['id'] . '">';
        if ($item['execution']['use_param']) {
            $check = __('Don\'t display if this cookie consent is not given:', 'oembed-manager');
            echo $this->field_checkandtextbox($check, get_option('oemm_exception_cookie_param', self::$oemm_exception_cookie_param), 'oemm_exception_cookie_block', 'oemm_exception_cookie_param', (bool)get_option('oemm_exception_cookie_block', self::$oemm_exception_cookie_block), $description, $item['execution']['help']);
        }
        else {
            $check = __('Don\'t display if cookie consent is not given', 'oembed-manager');
            echo $this->field_checkbox($check, 'oemm_exception_cookie_block', (bool)get_option('oemm_exception_cookie_block', self::$oemm_exception_cookie_block), $description);
        }
    }

    /**
     * Echoes a text box to set text for blocked cookie requests.
     *
     * @since 1.0.0
     */
    public function oemm_exception_cookie_text_callback($args) {
        echo $this->field_text(get_option('oemm_exception_cookie_text', self::$oemm_exception_cookie_text), __('Replacement text', 'oembed-manager'), 'oemm_exception_cookie_text',  __('Replacement text displayed while cookie consent is not given.', 'oembed-manager') . ' ' . __('Could be plain HTML. Let blank to fully hide the placeholder.', 'oembed-manager'));
    }

    /**
     * Echoes a check box to block dnt requests.
     *
     * @since 1.0.0
     */
    public function oemm_exception_dnt_block_callback($args) {
        $item = $args[0];
        $description = sprintf(__('If checked, no embedded content will be outputted if the plugin %s detects a Do Not Track header.', 'oembed-manager'), '<strong>' . $item['name'] . '</strong>');
        echo '<input type="hidden" name="oemm_exception_dnt_id" value="' . $item['id'] . '">';
        if ($item['execution']['use_param']) {
            $check = __('Honor <em>Do Not Track</em> requests', 'oembed-manager');
            echo $this->field_checkandtextbox($check, get_option('oemm_exception_dnt_param', self::$oemm_exception_dnt_param), 'oemm_exception_dnt_block', 'oemm_exception_dnt_param', (bool)get_option('oemm_exception_dnt_block', self::$oemm_exception_dnt_block), $description, $item['execution']['help']);
        }
        else {
            $check = __('Honor <em>Do Not Track</em> requests', 'oembed-manager');
            echo $this->field_checkbox($check, 'oemm_exception_dnt_block', (bool)get_option('oemm_exception_dnt_block', self::$oemm_exception_dnt_block), $description);
        }
    }

    /**
     * Echoes a text box to set text for blocked dnt requests.
     *
     * @since 1.0.0
     */
    public function oemm_exception_dnt_text_callback($args) {
        echo $this->field_text(get_option('oemm_exception_dnt_text', self::$oemm_exception_dnt_text), __('Replacement text', 'oembed-manager'), 'oemm_exception_dnt_text',  __('Replacement text displayed if <em>Do Not Track</em> header is sent.', 'oembed-manager') . ' ' . __('Could be plain HTML. Let blank to fully hide the placeholder.', 'oembed-manager'));
    }

    /**
     * Echoes a check box to enable clickable links.
     *
     * @since 1.0.0
     */
    public function oemm_advanced_clickable_callback($args) {
        echo $this->field_checkbox(__('Clickable URL', 'oembed-manager'), 'oemm_advanced_clickable', (bool)get_option('oemm_advanced_clickable', self::$oemm_advanced_clickable), __('If WordPress outputs oEmbed URLs, transform them into clickable links.', 'oembed-manager'));
    }

    /**
     * Echoes a select box to choose the cache TTL.
     *
     * @since 1.0.0
     */
    public function oemm_advanced_ttl_callback($args) {
        $choices = array();
        $choices[] = array(1, __('1 hour', 'oembed-manager'));
        $choices[] = array(24, __('1 day', 'oembed-manager'));
        $choices[] = array(168, __('1 week', 'oembed-manager'));
        $choices[] = array(720, __('1 month', 'oembed-manager'));
        $choices[] = array(8736, __('1 year', 'oembed-manager'));
        echo $this->field_select($choices, get_option('oemm_advanced_ttl', self::$oemm_advanced_ttl), 'oemm_advanced_ttl', __('How long the embedded content is cached by WordPress.', 'oembed-manager'));
    }

    /**
     * Echoes a select box to choose the connection timeout.
     *
     * @since 1.0.0
     */
    public function oemm_advanced_timeout_callback($args) {
        $choices = array();
        foreach (array(5, 10, 20, 40) as $time) {
            $choices[] = array($time, sprintf(__('%s seconds', 'oembed-manager'), $time));
        }
        echo $this->field_select($choices, get_option('oemm_advanced_timeout', self::$oemm_advanced_timeout), 'oemm_advanced_timeout', __('How long WordPress can wait the external website when fetching content.', 'oembed-manager'));
    }

    /**
     * Echoes a select box to choose the connection size.
     *
     * @since 1.0.0
     */
    public function oemm_advanced_size_callback($args) {
        $choices = array();
        foreach (array(75, 150, 300) as $time) {
            $choices[] = array($time, sprintf(__('%s kilobytes', 'oembed-manager'), $time));
        }
        echo $this->field_select($choices, get_option('oemm_advanced_size', self::$oemm_advanced_size), 'oemm_advanced_size', __('How much WordPress can retrieve of the original content.', 'oembed-manager'));
    }

    /**
     * Set producer settings fields.
     *
     * @since 1.0.0
     */
    public function producer_section_callback() {
        add_settings_field('oemm_disable_producer', __('oEmbed producer', 'oembed-manager'),
            array($this, 'oemm_disable_producer_callback'), 'oemm_producer', 'oemm_producer_section', array());
        register_setting('oemm_producer', 'oemm_disable_producer');
    }

    /**
     * Echoes a check box to disable producer.
     *
     * @since 1.0.0
     */
    public function oemm_disable_producer_callback($args) {
        echo $this->field_checkbox(__('Disabled', 'oembed-manager'), 'oemm_disable_producer', (bool)get_option('oemm_disable_producer', self::$oemm_disable_producer), sprintf(__('Prevents other websites to embed content from %s.', 'oembed-manager'), get_bloginfo('name')));
    }

    /**
     * Get a checkbox form field.
     *
     * @param string $text The text of the checkbox.
     * @param string $id The id (and the name) of the control.
     * @param boolean $checked Is the checkbox on?
     * @param string $description Optional. A description to display.
     * @return string The HTML string ready to print.
     * @since 1.0.0
     */
    protected function field_checkbox($text, $id, $checked=false, $description=null) {
        $html = '<fieldset><label><input name="' . $id . '" id="' . $id . '" type="checkbox" value="1"' . ($checked ? ' checked="checked"' : '') . '/>' . $text . '</label></fieldset>';
        if (isset($description)) {
            $html .= '<p class="description">' . $description . '</p>';
        }
        return $html;
    }

    /**
     * Get a checkbox form field.
     *
     * @param string $text The text of the checkbox.
     * @param string $value The value of the text input.
     * @param string $cid The id (and the name) of the checkbox.
     * @param string $tid The id (and the name) of the textarea.
     * @param boolean $checked Is the checkbox on?
     * @param string $description Optional. A description to display.
     * @param string $placeholder Optional. The placeholder of the textbox.
     * @return string The HTML string ready to print.
     * @since 1.0.0
     */
    protected function field_checkandtextbox($text, $value, $cid, $tid, $checked=false, $description=null, $placeholder = '') {
        $html = '<fieldset><label><input name="' . $cid . '" id="' . $cid . '" type="checkbox" value="1"' . ($checked ? ' checked="checked"' : '') . '/>' . $text . '</label>';
        $html .= '&nbsp;<label><input type="text" name="' . $tid . '" id="' . $tid . '" value="' . $value . '" placeholder="' . $placeholder . '"/></label></fieldset>';
        if (isset($description)) {
            $html .= '<p class="description">' . $description . '</p>';
        }
        return $html;
    }

    /**
     * Get a textarea form field.
     *
     * @param string $text The text of the textarea.
     * @param string $placeholder The placeholder of the textarea.
     * @param string $id The id (and the name) of the control.
     * @param string $description Optional. A description to display.
     * @return string The HTML string ready to print.
     * @since 1.0.0
     */
    protected function field_text($text, $placeholder, $id, $description=null) {
        $html = '<fieldset><label><textarea name="' . $id . '" id="' . $id . '" placeholder="' . $placeholder . '" cols="100" rows="2">' . $text . '</textarea></label></fieldset>';
        if (isset($description)) {
            $html .= '<p class="description">' . $description . '</p>';
        }
        return $html;
    }

    /**
     * Get a select form field.
     *
     * @param array $list The list of options.
     * @param int|string $value The selected value.
     * @param string $id The id (and the name) of the control.
     * @param string $description Optional. A description to display.
     * @return string The HTML string ready to print.
     * @since 1.0.0
     */
    protected function field_select($list, $value, $id, $description=null) {
        $html = '';
        foreach ($list as $val) {
            $html .= '<option value="' . $val[0] . '"' . ( $val[0] == $value ? ' selected="selected"' : '') . '>' . $val[1] . '</option>';
        }
        $html = '<select name="' . $id . '" id="' . $id . '">' . $html . '</select>';
        if (isset($description)) {
            $html .= '<p class="description">' . $description . '</p>';
        }
        return $html;
    }

    /**
     * Get a text form field.
     *
     * @param string $id The id (and the name) of the control.
     * @param string $value The string to put in the text field.
     * @param string $description Optional. A description to display.
     * @return string The HTML string ready to print.
     * @since 1.0.0
     */
    protected function field_input_text($id, $value='', $description=null) {
        $html = '<input name="' . $id . '" type="text" id="' . $id . '" value="' . $value . '" style="width:100%;"/>';
        if (isset($description)) {
            $html .= '<p class="description">' . $description . '</p>';
        }
        return $html;
    }

    /**
     * Reset the consumer settings to defaults.
     *
     * @since 1.0.0
     */
    private function reset_consumer() {
        update_option('oemm_disable_consumer', self::$oemm_disable_consumer);
        update_option('oemm_consumer_mode', self::$oemm_consumer_mode);
        update_option('oemm_advanced_clickable', self::$oemm_advanced_clickable);
        update_option('oemm_advanced_ttl', self::$oemm_advanced_ttl);
        update_option('oemm_advanced_timeout', self::$oemm_advanced_timeout);
        update_option('oemm_advanced_size', self::$oemm_advanced_size);
        update_option('oemm_exception_consent_block', self::$oemm_exception_consent_block);
        update_option('oemm_exception_cookie_block', self::$oemm_exception_cookie_block);
        update_option('oemm_exception_dnt_block', self::$oemm_exception_dnt_block);
        update_option('oemm_exception_consent_param', self::$oemm_exception_consent_param);
        update_option('oemm_exception_cookie_param', self::$oemm_exception_cookie_param);
        update_option('oemm_exception_dnt_param', self::$oemm_exception_dnt_param);
        update_option('oemm_exception_consent_id', self::$oemm_exception_consent_id);
        update_option('oemm_exception_cookie_id', self::$oemm_exception_cookie_id);
        update_option('oemm_exception_dnt_id', self::$oemm_exception_dnt_id);
        update_option('oemm_exception_consent_text', self::$oemm_exception_consent_text);
        update_option('oemm_exception_cookie_text', self::$oemm_exception_cookie_text);
        update_option('oemm_exception_dnt_text', self::$oemm_exception_dnt_text);
    }

    /**
     * Update the consumer settings.
     *
     * @since 1.0.0
     */
    private function update_consumer() {
        $oemm_consumer_mode = null;
        $oemm_advanced_ttl = null;
        $oemm_advanced_timeout = null;
        $oemm_advanced_size = null;
        $oemm_exception_consent_text = self::$oemm_exception_consent_text;
        $oemm_exception_cookie_text = self::$oemm_exception_cookie_text;
        $oemm_exception_dnt_text = self::$oemm_exception_dnt_text;
        $oemm_exception_consent_id = self::$oemm_exception_consent_id;
        $oemm_exception_cookie_id = self::$oemm_exception_cookie_id;
        $oemm_exception_dnt_id = self::$oemm_exception_dnt_id;
        $oemm_exception_consent_param = get_option ('oemm_exception_consent_param', self::$oemm_exception_consent_param);
        $oemm_exception_cookie_param = get_option ('oemm_exception_cookie_param', self::$oemm_exception_cookie_param);
        $oemm_exception_dnt_param = get_option ('oemm_exception_dnt_param', self::$oemm_exception_dnt_param);
        $allowedtags = array(
            'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ),
            'abbr' => array( 'title' => array() ), 'acronym' => array( 'title' => array() ),
            'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
            'div' => array( 'class' => array() ), 'span' => array( 'class' => array() ),
            'p' => array(), 'br' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
            'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
            'img' => array( 'src' => array(), 'class' => array(), 'alt' => array() ),
            'blockquote' => array( 'cite' => true ),
        );
        if (array_key_exists('oemm_consumer_mode', $_POST)) {
            $oemm_consumer_mode = (int)$_POST['oemm_consumer_mode'];
        }
        if (array_key_exists('oemm_advanced_ttl', $_POST)) {
            $temp = (int)$_POST['oemm_advanced_ttl'];
            if ($temp > 0) {
                $oemm_advanced_ttl = $temp;
            }
        }
        if (array_key_exists('oemm_advanced_timeout', $_POST)) {
            $temp = (int)$_POST['oemm_advanced_timeout'];
            if ($temp > 0 && $temp < 41) {
                $oemm_advanced_timeout = $temp;
            }
        }
        if (array_key_exists('oemm_advanced_size', $_POST)) {
            $temp = (int)$_POST['oemm_advanced_size'];
            if ($temp > 0 && $temp < 1000) {
                $oemm_advanced_size = $temp;
            }
        }
        if (array_key_exists('oemm_exception_consent_text', $_POST)) {
            $oemm_exception_consent_text = wp_kses($_POST['oemm_exception_consent_text'], $allowedtags);
        }
        if (array_key_exists('oemm_exception_cookie_text', $_POST)) {
            $oemm_exception_cookie_text = wp_kses($_POST['oemm_exception_cookie_text'], $allowedtags);
        }
        if (array_key_exists('oemm_exception_dnt_text', $_POST)) {
            $oemm_exception_dnt_text = wp_kses($_POST['oemm_exception_dnt_text'], $allowedtags);
        }
        if (array_key_exists('oemm_exception_consent_param', $_POST)) {
            $oemm_exception_consent_param = sanitize_text_field($_POST['oemm_exception_consent_param']);
        }
        if (array_key_exists('oemm_exception_cookie_param', $_POST)) {
            $oemm_exception_cookie_param = sanitize_text_field($_POST['oemm_exception_cookie_param']);
        }
        if (array_key_exists('oemm_exception_dnt_param', $_POST)) {
            $oemm_exception_dnt_param = sanitize_text_field($_POST['oemm_exception_dnt_param']);
        }
        if (array_key_exists('oemm_exception_consent_id', $_POST)) {
            $oemm_exception_consent_id = sanitize_text_field($_POST['oemm_exception_consent_id']);
        }
        if (array_key_exists('oemm_exception_cookie_id', $_POST)) {
            $oemm_exception_cookie_id = sanitize_text_field($_POST['oemm_exception_cookie_id']);
        }
        if (array_key_exists('oemm_exception_dnt_id', $_POST)) {
            $oemm_exception_dnt_id = sanitize_text_field($_POST['oemm_exception_dnt_id']);
        }
        update_option('oemm_disable_consumer', (array_key_exists('oemm_disable_consumer', $_POST) ? 1 : 0));
        update_option('oemm_consumer_mode', (isset($oemm_consumer_mode) ? $oemm_consumer_mode : self::$oemm_consumer_mode));
        update_option('oemm_advanced_clickable', (array_key_exists('oemm_advanced_clickable', $_POST) ? 1 : 0));
        update_option('oemm_advanced_ttl', (isset($oemm_advanced_ttl) ? $oemm_advanced_ttl : self::$oemm_advanced_ttl));
        update_option('oemm_advanced_timeout', (isset($oemm_advanced_timeout) ? $oemm_advanced_timeout : self::$oemm_advanced_timeout));
        update_option('oemm_advanced_size', (isset($oemm_advanced_size) ? $oemm_advanced_size : self::$oemm_advanced_size));
        update_option('oemm_exception_consent_block', (array_key_exists('oemm_exception_consent_block', $_POST) ? 1 : 0));
        update_option('oemm_exception_cookie_block', (array_key_exists('oemm_exception_cookie_block', $_POST) ? 1 : 0));
        update_option('oemm_exception_dnt_block', (array_key_exists('oemm_exception_dnt_block', $_POST) ? 1 : 0));
        update_option('oemm_exception_consent_param', $oemm_exception_consent_param);
        update_option('oemm_exception_cookie_param', $oemm_exception_cookie_param);
        update_option('oemm_exception_dnt_param', $oemm_exception_dnt_param);
        update_option('oemm_exception_consent_id', $oemm_exception_consent_id);
        update_option('oemm_exception_cookie_id', $oemm_exception_cookie_id);
        update_option('oemm_exception_dnt_id', $oemm_exception_dnt_id);
        update_option('oemm_exception_consent_text', $oemm_exception_consent_text);
        update_option('oemm_exception_cookie_text', $oemm_exception_cookie_text);
        update_option('oemm_exception_dnt_text', $oemm_exception_dnt_text);
    }

    /**
     * Reset the producer settings to defaults.
     *
     * @since 1.0.0
     */
    private function reset_producer() {
        update_option('oemm_disable_producer', self::$oemm_disable_producer);
    }

    /**
     * Update the producer settings.
     *
     * @since 1.0.0
     */
    private function update_producer() {
        update_option('oemm_disable_producer', (array_key_exists('oemm_disable_producer', $_POST) ? 1 : 0));
    }

    /**
     * Get all the integrations.
     *
     * @return array An array containing all integrations.
     * @since 1.0.0
     */
    public function get_integrations() {
        $integrations = array();
        $integrations[] = array('title' =>  __('Consent management', 'oembed-manager'),
            'description' =>  __('These plugins help <strong>oEmbed Manager</strong> to build exception rules, based on the optional consent of your visitors:', 'oembed-manager'),
            'prefix' => 'consent',
            'items' =>  OEMMConsentIntegration::init()->detect()->get_items(),
            'enabled' => OEMMConsentIntegration::init()->detect()->count_activated() !== 0);
        $integrations[] = array('title' =>  __('Cookies management', 'oembed-manager'),
            'description' =>  __('These plugins help <strong>oEmbed Manager</strong> to build exception rules, based on the consent of your visitors about cookies:', 'oembed-manager'),
            'prefix' => 'cookie',
            'items' =>  OEMMCookieIntegration::init()->detect()->get_items(),
            'enabled' => OEMMCookieIntegration::init()->detect()->count_activated() !== 0);
        $integrations[] = array('title' =>  __('<em>Do Not Track</em> policy', 'oembed-manager'),
            'description' =>  __('These plugins help <strong>oEmbed Manager</strong> to build exception rules, based on the <em>Do Not Track</em> header sent by your visitors\' browser:', 'oembed-manager'),
            'prefix' => 'dnt',
            'items' =>  OEMMDntIntegration::init()->detect()->get_items(),
            'enabled' => OEMMDntIntegration::init()->detect()->count_activated() !== 0);
        return $integrations;
    }

    /**
     * Get the content of the settings page.
     *
     * @since 1.0.0
     */
    public function get_settings_page() {
        $error_message = __('Settings have not been updated. Please try again.', 'oembed-manager');
        $message = '';
        if (!empty($_POST)) {
            if (array_key_exists('_wpnonce', $_POST)) {
                if (wp_verify_nonce($_POST['_wpnonce'], 'oemm-settings')) {
                    if (array_key_exists('submit', $_POST)) {
                        if (array_key_exists('target', $_POST)) {
                            switch (strtolower($_POST['target'])) {
                                case 'consumer' :
                                    $this->update_consumer();
                                    $this->set_consumer();
                                    $this->purge_caches();
                                    $message = __('oEmbed consumer settings have been updated.', 'oembed-manager');
                                    break;
                                case 'producer' :
                                    $this->update_producer();
                                    $this->set_producer();
                                    flush_rewrite_rules(true);
                                    $message = __('oEmbed producer settings have been updated.', 'oembed-manager');
                                    break;
                            }
                            if ($message !== '') {
                                add_settings_error('oemm_no_error', 0, $message, 'updated');
                            }
                        }
                        else {
                            add_settings_error('oemm_target_error', 1, $error_message, 'error');
                        }
                    }
                    elseif (array_key_exists('reset', $_POST)) {
                        if (array_key_exists('target', $_POST)) {
                            switch (strtolower($_POST['target'])) {
                                case 'consumer' :
                                    $this->reset_consumer();
                                    $this->purge_caches();
                                    $message = __('oEmbed consumer settings have reset to defaults.', 'oembed-manager');
                                    break;
                                case 'producer' :
                                    $this->reset_producer();
                                    flush_rewrite_rules(true);
                                    $message = __('oEmbed producer settings have reset to defaults.', 'oembed-manager');
                                    break;
                            }
                            if ($message !== '') {
                                add_settings_error('oemm_no_error', 0, $message, 'updated');
                            }
                        }
                        else {
                            add_settings_error('oemm_target_error', 1, $error_message, 'error');
                        }
                    }
                }
                else {
                    add_settings_error('oemm_nonce_error', 2, $error_message, 'error');
                }
            }
            else {
                add_settings_error('oemm_nonce_error', 3, $error_message, 'error');
            }
        }
        elseif (!empty($_GET)) {
            $action = filter_input(INPUT_GET, 'action');
            if ($action) {
                switch ($action) {
                    case 'reset-cache':
                        $this->purge_caches();
                        $message = __('oEmbed cache have been purged.', 'oembed-manager');
                        break;
                }
                if ($message !== '') {
                    add_settings_error('oemm_no_error', 0, $message, 'updated');
                }
            }

        }
        $tab = filter_input(INPUT_GET, 'tab');
        $cached = 0;
        if ($tab) {
            if ($tab === 'tools') {
                $cached = $this->count_caches();
            }
            if ($tab === 'consumer' || $tab === 'integrations') {
                $integrations = $this->get_integrations();

            }
        }
        include(self::$plugin_dir.'partials/settings.php');
    }

    /**
     * Disable oEmbed rewrite rules.
     *
     * @param array $rules The WP rewrite rules.
     * @return array The modified rules.
     *
     * @since 1.0.0
     */
    public function disable_rewrite_rules($rules) {
        foreach ($rules as $rule => $rewrite) {
            if (strpos($rewrite, 'embed=true') !== false) {
                unset($rules[$rule]);
            }
        }
        return $rules;
    }

    /**
     * Remove oEmbed query vars.
     *
     * @since 1.0.0
     */
    public function remove_query_vars() {
        global $wp;
        $wp->public_query_vars = array_diff($wp->public_query_vars, array('embed'));
    }

    /**
     * Remove oEmbed autoembed.
     *
     * @since 1.0.0
     */
    public function remove_autoembed() {
        global $wp_embed;
        remove_filter('the_content', array($wp_embed, 'autoembed'), 8);
    }

    /**
     * Remove oEmbed related plugins from TinyMCE.
     *
     * @param array $plugins List of TinyMCE plugins.
     * @return array The modified list.
     *
     * @since 1.0.0
     */
    public function remove_tiny_mce_plugin($plugins) {
        return array_diff($plugins, array('wpembed', 'wpview'));
    }

    /**
     * Modify video oEmbed html output.
     *
     * @param string $html The (cached) HTML result, stored in post meta.
     * @return string The modified HTML, ready to print.
     *
     * @since 1.1.0
     */
    public function modify_video_oembed_html($html) {
        return $this->modify_oembed_html($html);
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
    public function modify_oembed_html($html, $url='', $attr=array(), $post_id=0) {
        if ((int)get_option('oemm_consumer_mode', self::$oemm_consumer_mode) === 2 ) {
            return '';
        }
        if ((int)get_option('oemm_consumer_mode', self::$oemm_consumer_mode) === 1) {
            $output = $url;
            if ((bool)get_option('oemm_advanced_clickable', self::$oemm_advanced_clickable)) {
                $output = '<a href="' . $url. '">' . $url . '</a>';
            }
            return $output;
        }
        
        // Verify consent exclusions
        if ((bool)get_option('oemm_exception_consent_block', self::$oemm_exception_consent_block)) {
            if (!OEMMConsentIntegration::init()->evaluate(get_option('oemm_exception_consent_id', 'unknown'), get_option('oemm_exception_consent_param', null))) {
                return get_option('oemm_exception_consent_text', self::$oemm_exception_consent_text);
            }
        }

        // Verify cookie exclusions
        if ((bool)get_option('oemm_exception_cookie_block', self::$oemm_exception_cookie_block)) {
            if (!OEMMCookieIntegration::init()->evaluate(get_option('oemm_exception_cookie_id', 'unknown'), get_option('oemm_exception_cookie_param', null))) {
                return get_option('oemm_exception_cookie_text', self::$oemm_exception_cookie_text);
            }
        }

        // Verify DNT exclusions
        if ((bool)get_option('oemm_exception_dnt_block', self::$oemm_exception_dnt_block)) {
            if (!OEMMDntIntegration::init()->evaluate(get_option('oemm_exception_dnt_id', 'unknown'), get_option('oemm_exception_dnt_param', null))) {
                return get_option('oemm_exception_dnt_text', self::$oemm_exception_dnt_text);
            }
        }

        return $html;
    }

    /**
     * Get the cache ttl for oEmbed (consumer).
     *
     * @return integer The cache ttl in seconds.
     *
     * @since 1.0.0
     */
    public function get_cache_ttl() {
        return (int)round(get_option('oemm_advanced_ttl', self::$oemm_advanced_ttl) * 3600);
    }

    /**
     * Add/modify some args for fetching external websites.
     *
     * @param array $args The current args.
     * @return array The modified args.
     *
     * @since 1.0.0
     */
    public function modify_fetch_args($args){
        if (!isset($args)) {
            $args = array();
        }
        $args['timeout'] = get_option('oemm_advanced_timeout', self::$oemm_advanced_timeout);
        $args['limit_response_size'] = (int)round(get_option('oemm_advanced_size', self::$oemm_advanced_size) * 1024);
        return $args;
    }

    /**
     * Set consumer mode.
     *
     * @since 1.0.0
     */
    public function set_consumer() {
        if ((bool)get_option('oemm_disable_consumer', self::$oemm_disable_consumer)) {
            $this->remove_autoembed();
            remove_filter('the_content_feed', '_oembed_filter_feed_content');
            remove_action('plugins_loaded', 'wp_maybe_load_embeds', 0);
            add_filter('pre_option_embed_autourls', '__return_false');
            add_filter('embed_oembed_discover', '__return_false');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            remove_filter('excerpt_more', 'wp_embed_excerpt_more', 20);
            remove_filter('the_excerpt_embed', 'wptexturize');
            remove_filter('the_excerpt_embed', 'convert_chars');
            remove_filter('the_excerpt_embed', 'wpautop');
            remove_filter('the_excerpt_embed', 'shortcode_unautop');
            remove_filter('the_excerpt_embed', 'wp_embed_excerpt_attachment');
            remove_filter('oembed_dataparse', 'wp_filter_oembed_result');
            remove_filter('oembed_response_data', 'get_oembed_response_data_rich');
            remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result');
            add_filter( 'tiny_mce_plugins', array($this, 'remove_tiny_mce_plugin'));
        }
        else {
            add_filter('embed_oembed_html', array($this, 'modify_oembed_html'), self::$html_priority, 4);
            add_filter('video_embed_html', array($this, 'modify_video_oembed_html'), self::$html_priority, 1);
            add_filter('oembed_ttl', array($this, 'get_cache_ttl'), self::$lowest_priority);
            add_filter('oembed_remote_get_args', array($this, 'modify_fetch_args'), self::$lowest_priority);
        }
    }

    /**
     * Set producer mode.
     *
     * @since 1.0.0
     */
    public function set_producer() {
        if ((bool)get_option('oemm_disable_producer', self::$oemm_disable_producer)) {
            $this->remove_query_vars();
            remove_action('rest_api_init', 'wp_oembed_register_route');
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_filter('rest_pre_serve_request', '_oembed_rest_pre_serve_request');
            remove_action('embed_head', 'enqueue_embed_scripts', 1);
            remove_action('embed_head', 'print_embed_styles');
            remove_action('embed_head', 'wp_print_head_scripts', 20);
            remove_action('embed_head', 'wp_print_styles', 20);
            remove_action('embed_head', 'wp_no_robots');
            remove_action('embed_head', 'rel_canonical');
            remove_action('embed_head', 'locale_stylesheet', 30);
            remove_action('embed_content_meta', 'print_embed_comments_button');
            remove_action('embed_content_meta', 'print_embed_sharing_button');
            remove_action('embed_footer', 'print_embed_sharing_dialog');
            remove_action('embed_footer', 'print_embed_scripts');
            remove_action('embed_footer', 'wp_print_footer_scripts', 20);
            add_filter('rewrite_rules_array', array($this, 'disable_rewrite_rules'));
        }
        else {
            remove_filter('rewrite_rules_array', array($this, 'disable_rewrite_rules'));

        }
    }

    /**
     * Purge oEmbed caches.
     *
     * @since 1.0.0
     */
    public function purge_caches() {
        global $wpdb;
        return $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE '%_oembed_%'");
    }

    /**
     * Count oEmbed caches.
     *
     * @return integer Number of cached oEmbed.
     *
     * @since 1.0.0
     */
    public function count_caches() {
        global $wpdb;
        $wpdb->flush();
        return (integer)round($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key LIKE '%_oembed_%'") / 2, 0);
    }

}

// Utilities

function oemm_get_settings_page_url($page='oemm-settings', $tab=null, $action=null) {
    $args = array('page' => $page);
    if (isset($tab)) {
        $args['tab'] = $tab;
    }
    if (isset($action)) {
        $args['action'] = $action;
    }
    $url = add_query_arg($args, admin_url('options-general.php'));
    return $url;
}

// Init the plugin

OEmbedManager::init();