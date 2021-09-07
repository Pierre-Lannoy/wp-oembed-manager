<?php
/**
 * Main plugin file.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       oEmbed Manager
 * Plugin URI:        https://github.com/Pierre-Lannoy/wp-oembed-manager
 * Description:       Manage oEmbed capabilities of your website and take a new step in the GDPR compliance of your embedded content.
 * Version:           2.5.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Pierre Lannoy
 * Author URI:        https://pierre.lannoy.fr
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       oembed-manager
 * Domain Path:       /languages
 * Network:           true
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/includes/system/class-option.php';
require_once __DIR__ . '/includes/system/class-environment.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/includes/libraries/class-libraries.php';
require_once __DIR__ . '/includes/libraries/autoload.php';

/**
 * The code that runs during plugin activation.
 *
 * @since 1.0.0
 */
function oemm_activate() {
	Oemm\Plugin\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * @since 1.0.0
 */
function oemm_deactivate() {
	Oemm\Plugin\Deactivator::deactivate();
}

/**
 * The code that runs during plugin uninstallation.
 *
 * @since 1.0.0
 */
function oemm_uninstall() {
	Oemm\Plugin\Uninstaller::uninstall();
}

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function oemm_run() {
	\DecaLog\Engine::initPlugin( OEMM_SLUG, OEMM_PRODUCT_NAME, OEMM_VERSION, \Oemm\Plugin\Core::get_base64_logo() );
	require_once __DIR__ . '/includes/features/class-wpcli.php';
	$plugin = new Oemm\Plugin\Core();
	$plugin->run();
}

register_activation_hook( __FILE__, 'oemm_activate' );
register_deactivation_hook( __FILE__, 'oemm_deactivate' );
register_uninstall_hook( __FILE__, 'oemm_uninstall' );
oemm_run();
