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

use Oemm\System\Role;

wp_enqueue_script( 'oemm-moment-with-locale' );
wp_enqueue_script( 'oemm-daterangepicker' );
wp_enqueue_script( 'oemm-switchery' );
wp_enqueue_script( 'oemm-chartist' );
wp_enqueue_script( 'oemm-chartist-tooltip' );
wp_enqueue_script( 'oemm-jvectormap' );
wp_enqueue_script( 'oemm-jvectormap-world' );
wp_enqueue_script( OEMM_ASSETS_ID );
wp_enqueue_style( OEMM_ASSETS_ID );
wp_enqueue_style( 'oemm-daterangepicker' );
wp_enqueue_style( 'oemm-switchery' );
wp_enqueue_style( 'oemm-tooltip' );
wp_enqueue_style( 'oemm-chartist' );
wp_enqueue_style( 'oemm-chartist-tooltip' );
wp_enqueue_style( 'oemm-jvectormap' );


?>

<div class="wrap">
	<div class="oemm-dashboard">
		<div class="oemm-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="oemm-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <?php if ( 'summary' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="oemm-row">
                <div class="oemm-box oemm-box-40-60-line">
                    <?php echo $analytics->get_top_domain_box() ?>
                    <?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'domain' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="oemm-row">
                <div class="oemm-box oemm-box-40-60-line">
					<?php echo $analytics->get_top_authority_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'authority' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="oemm-row">
                <div class="oemm-box oemm-box-40-60-line">
					<?php echo $analytics->get_top_endpoint_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( ( 'summary' === $analytics->type || 'domain' === $analytics->type || 'authority' === $analytics->type || 'endpoint' === $analytics->type ) && '' === $analytics->extra ) { ?>
			<?php echo $analytics->get_main_chart() ?>
            <div class="oemm-row">
                <div class="oemm-box oemm-box-33-33-33-line">
					<?php echo $analytics->get_codes_box() ?>
					<?php echo $analytics->get_security_box() ?>
					<?php echo $analytics->get_method_box() ?>
                </div>
            </div>
			<?php if ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site) { ?>
                <div class="oemm-row last-row">
					<?php echo $analytics->get_sites_list() ?>
                </div>
			<?php } ?>
		<?php } ?>

		<?php if ( 'domains' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="oemm-row">
	            <?php echo $analytics->get_domains_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'authorities' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="oemm-row">
				<?php echo $analytics->get_authorities_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'endpoints' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="oemm-row">
				<?php echo $analytics->get_endpoints_list() ?>
            </div>
		<?php } ?>
		<?php if ( '' !== $analytics->extra ) { ?>
            <div class="oemm-row">
				<?php echo $analytics->get_extra_list() ?>
            </div>
		<?php } ?>
	</div>
</div>
