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

?>

<form action="
	<?php
		echo esc_url(
			add_query_arg(
				[
					'page'   => 'oemm-settings',
					'action' => 'do-save',
					'tab'    => 'producer',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
	" method="POST">
	<?php do_settings_sections( 'oemm_producer_misc' ); ?>
	<?php do_settings_sections( 'oemm_producer_rules' ); ?>
	<?php do_settings_sections( 'oemm_producer_advanced' ); ?>
	<?php wp_nonce_field( 'oemm-plugin-options' ); ?>
    <p><?php echo get_submit_button( null, 'primary', 'submit', false ); ?></p>
</form>
