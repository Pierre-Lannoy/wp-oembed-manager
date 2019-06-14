<?php

/**
 * @author Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 1.0.0
 */

$active_tab = (isset($_GET['tab']) ? $_GET['tab'] : 'consumer');
$buttons = str_replace('</p>', '', get_submit_button()) . ' &nbsp;&nbsp;&nbsp; ' . str_replace('<p class="submit">', '', get_submit_button(__('Reset to Defaults', 'oembed-manager'), 'secondary', 'reset'));
$intro = __('The <strong>oEmbed format</strong> allows you to embed external content in your website (<em>consumer</em> role) and allows other websites to embed your content (<em>producer</em> role).', 'oembed-manager');
$intro .= '<br/>' . sprintf(__('By default, %s acts as an oEmbed consumer and producer.', 'oembed-manager'), get_bloginfo('name'));
$intro .= ' ' . __('You can modify this and adjust behavior in the following tabs:', 'oembed-manager');
$enabled = false;
if (isset($integrations)) {
    foreach ($integrations as $integration) {
        if ($integration['enabled']) {
            $enabled = true;
            break;
        }
    }
}


?>

<div class="wrap">

    <h2><?php echo __('oEmbed settings', 'oembed-manager');?></h2>
    <?php settings_errors(); ?>
    <p><?php echo $intro;?></p>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo oemm_get_settings_page_url('oemm-settings', 'consumer'); ?>" class="nav-tab <?php echo $active_tab == 'consumer' ? 'nav-tab-active' : ''; ?>"><?php echo __('Consumer', 'oembed-manager');?></a>
        <a href="<?php echo oemm_get_settings_page_url('oemm-settings', 'producer'); ?>" class="nav-tab <?php echo $active_tab == 'producer' ? 'nav-tab-active' : ''; ?>"><?php echo __('Producer', 'oembed-manager');?></a>
        <a href="<?php echo oemm_get_settings_page_url('oemm-settings', 'tools'); ?>" class="nav-tab <?php echo $active_tab == 'tools' ? 'nav-tab-active' : ''; ?>"><?php echo __('Tools', 'oembed-manager');?></a>
        <a href="<?php echo oemm_get_settings_page_url('oemm-settings', 'integrations'); ?>" class="nav-tab <?php echo $active_tab == 'integrations' ? 'nav-tab-active' : ''; ?>" style="float:right;"><?php echo __('Available integrations', 'oembed-manager');?></a>
    </h2>

    <?php if ($active_tab === 'tools') { ?>
        <?php if ($cached === 0) {?>
            <p><?php echo __('Your WordPress cache does not contain oEmbed content.', 'oembed-manager');?></p>
        <?php } else { ?>
            <p><?php echo sprintf(_n('Your WordPress cache contains %s oEmbed content.', 'Your WordPress cache contains %s oEmbed contents.', $cached, 'oembed-manager'), $cached);?></p>
        <?php } ?>
        <p><a class="button button-primary <?php echo $cached===0?'button-disabled':'';?>" href="<?php echo esc_url(oemm_get_settings_page_url('oemm-settings', $active_tab, 'reset-cache')); ?>"><?php echo __('Purge Cache', 'oembed-manager');?></a></p>
    <?php } else { ?>
    <form action="<?php echo esc_url(oemm_get_settings_page_url('oemm-settings', $active_tab)); ?>" method="POST">
        <input type="hidden" name="target" value="<?php echo $active_tab; ?>" />
        <?php do_settings_sections('oemm_'.$active_tab); ?>
        <?php if ($active_tab === 'consumer') { ?>
            <span class="oemm-exclusion-section">
                <p>&nbsp;</p>
                <h2><?php echo __('Exceptions rules', 'oembed-manager');?></h2>
                <p><?php echo __('With the exceptions rules, you can prevent the display of styled and formatted content in some specific cases.', 'oembed-manager');?><br />
                    <?php echo __('These rules are particularly useful (but not sufficient) to respect the GDPR, by blocking third-party cookies in common scenarios.', 'oembed-manager');?></p>
                <?php if (!$enabled) {?>
                    <p><?php echo sprintf(__('You currently have no plugins supported by <strong>oEmbed Manager</strong> to create exception rules. Please, take a look at <a href="%s">available integrations</a>.', 'oembed-manager'), oemm_get_settings_page_url('oemm-settings', 'integrations'));?></p>
                <?php } ?>
                <?php do_settings_sections('oemm_exception'); ?>
            </span>
            <p>&nbsp;</p>
            <h2><?php echo __('Advanced options', 'oembed-manager');?></h2>
            <p><?php echo __('Here, you can fine tune the oEmbed consumer operations.', 'oembed-manager');?><br />
                <?php echo __('If these parameters do not mean anything for you, just let them set to their default.', 'oembed-manager');?></p>
            <?php do_settings_sections('oemm_advanced'); ?>
        <?php } ?>
        <?php if ($active_tab === 'integrations') { ?>
            <?php include (__DIR__.'/integrations.php');?>
        <?php } ?>
        <?php if ($active_tab !== 'integrations') { ?>
            <?php wp_nonce_field('oemm-settings'); ?>
            <?php echo $buttons;?>
        <?php } ?>
    </form>
    <?php } ?>
</div>