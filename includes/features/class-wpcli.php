<?php
/**
 * WP-CLI for oEmbed Manager.
 *
 * Adds WP-CLI commands to oEmbed Manager
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */

namespace Oemm\Plugin\Feature;

use Oemm\System\Markdown;

/**
 * -.
 *
 * @package Features
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   2.4.0
 */
class Wpcli {

	/**
	 * Get the WP-CLI help file.
	 *
	 * @param   array $attributes  'style' => 'markdown', 'html'.
	 *                             'mode'  => 'raw', 'clean'.
	 * @return  string  The output of the shortcode, ready to print.
	 * @since 2.5.0
	 */
	public static function sc_get_helpfile( $attributes ) {
		$md = new Markdown();
		return $md->get_shortcode( 'WP-CLI.md', $attributes );
	}

}

add_shortcode( 'oemm-wpcli', [ 'Oemm\Plugin\Feature\Wpcli', 'sc_get_helpfile' ] );
