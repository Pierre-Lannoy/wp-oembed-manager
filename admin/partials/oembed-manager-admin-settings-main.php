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

// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'misc' );
if ( 'misc' === $active_tab && ! ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) ) {
	$active_tab = 'producer';
}

?>

<div class="wrap">

	<h2><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'oembed-manager' ), OEMM_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper">
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				[
					'page' => 'oemm-settings',
					'tab'  => 'consumer',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'consumer' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Consumer', 'oembed-manager' ); ?></a>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				[
					'page' => 'oemm-settings',
					'tab'  => 'producer',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'producer' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Producer', 'oembed-manager' ); ?></a>
		<?php if ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) { ?>
            <a href="
            <?php
            echo esc_url(
                add_query_arg(
                    [
                        'page' => 'oemm-settings',
                        'tab'  => 'misc',
                    ],
                    admin_url( 'admin.php' )
                )
            );
            ?>
            " class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'oembed-manager' ); ?></a>
		<?php } ?>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				[
					'page' => 'oemm-settings',
					'tab'  => 'about',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'oembed-manager' ); ?></a>
		<?php if ( class_exists( 'Oemm\Plugin\Feature\Wpcli' ) ) { ?>
            <a href="
            <?php
			echo esc_url(
				add_query_arg(
					array(
						'page' => 'oemm-settings',
						'tab'  => 'wpcli',
					),
					admin_url( 'admin.php' )
				)
			);
			?>
            " class="nav-tab <?php echo 'wpcli' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;">WP-CLI</a>
		<?php } ?>
        <a href="
		<?php
		echo esc_url(
			add_query_arg(
				[
					'page' => 'oemm-settings',
					'tab'  => 'integrations',
				],
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'integrations' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'Integrations', 'oembed-manager' ); ?></a>
	</h2>
    
	<?php if ( 'misc' === $active_tab && ( Role::SUPER_ADMIN === Role::admin_type() || Role::SINGLE_ADMIN === Role::admin_type() ) ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'consumer' === $active_tab ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-consumer.php'; ?>
	<?php } ?>
	<?php if ( 'producer' === $active_tab ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-producer.php'; ?>
	<?php } ?>
	<?php if ( 'integrations' === $active_tab ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-integrations.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-about.php'; ?>
	<?php } ?>
	<?php if ( 'wpcli' === $active_tab ) { ?>
		<?php wp_enqueue_style( OEMM_ASSETS_ID ); ?>
		<?php echo do_shortcode( '[oemm-wpcli]' ); ?>
	<?php } ?>
</div>
