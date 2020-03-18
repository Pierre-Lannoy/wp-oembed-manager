<?php
/**
 * Provide a admin-facing tools for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use Oemm\Plugin\Feature\Posts;

$scripts = new Posts();
$scripts->prepare_items();

wp_enqueue_script( OEMM_ASSETS_ID );
wp_enqueue_style( OEMM_ASSETS_ID );

?>

<div class="wrap">
	<h2><?php echo esc_html__( 'oEmbed Cache Management', 'oembed-manager' );; ?></h2>
	<?php settings_errors(); ?>
	<?php $scripts->views(); ?>
    <form id="oemm-tools" method="post" action="<?php echo $scripts->get_url(); ?>">
        <input type="hidden" name="page" value="oemm-tools" />
	    <?php $scripts->display(); ?>
    </form>
</div>
