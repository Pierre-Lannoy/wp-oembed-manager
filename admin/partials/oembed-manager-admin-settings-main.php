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

// phpcs:ignore
$active_tab = ( isset( $_GET['tab'] ) ? $_GET['tab'] : 'misc' );
$url        = esc_url(
	add_query_arg(
		[
			'page' => 'oemm-viewer',
		],
		admin_url( 'admin.php' )
	)
);
$note       = sprintf(__('Note: analytics reports are available via the <a href="%s">tools menu</a>.', 'oembed-manager' ), $url );

?>

<div class="wrap">

	<h2><?php echo esc_html( sprintf( esc_html__( '%s Settings', 'oembed-manager' ), OEMM_PRODUCT_NAME ) ); ?></h2>
	<?php settings_errors(); ?>

	<h2 class="nav-tab-wrapper">
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'oemm-settings',
					'tab'  => 'misc',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'misc' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Options', 'oembed-manager' ); ?></a>
		<a href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page' => 'oemm-settings',
					'tab'  => 'about',
				),
				admin_url( 'admin.php' )
			)
		);
		?>
		" class="nav-tab <?php echo 'about' === $active_tab ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php esc_html_e( 'About', 'oembed-manager' ); ?></a>
	</h2>
    
	<?php if ( 'misc' === $active_tab ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-options.php'; ?>
	<?php } ?>
	<?php if ( 'about' === $active_tab ) { ?>
		<?php include __DIR__ . '/oembed-manager-admin-settings-about.php'; ?>
	<?php } ?>

    <p>&nbsp;</p>
    <em><?php echo $note;?></em>
</div>
