<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use Oemm\Plugin\Feature\oEmbed;

wp_enqueue_style( OEMM_ASSETS_ID );
wp_enqueue_script( OEMM_ASSETS_ID );

$hidden = '';
foreach ( oEmbed::get_descriptions() as $integrations ) {
	if ( $integrations['enabled'] ) {
		foreach ( $integrations['items'] as $item ) {
			if ( $item['detected'] ) {
				$hidden .= '<input type="hidden" name="exception_' . $integrations['prefix'] . '_id" id="exception_' . $integrations['prefix'] . '_id" value="' . $item['id'] . '">';
			}
		}
	}
}
?>

<form action="
	<?php
		echo esc_url(
			add_query_arg(
				[
					'page'   => 'oemm-settings',
					'action' => 'do-save',
					'tab'    => 'consumer',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
	" method="POST">
	<?php echo $hidden; ?>
	<?php do_settings_sections( 'oemm_consumer_misc_section' ); ?>
	<?php do_settings_sections( 'oemm_consumer_rules_section' ); ?>
	<?php do_settings_sections( 'oemm_consumer_advanced_section' ); ?>
	<?php wp_nonce_field( 'oemm-plugin-options' ); ?>
    <p><?php echo get_submit_button( null, 'primary', 'submit', false ); ?></p>
</form>
