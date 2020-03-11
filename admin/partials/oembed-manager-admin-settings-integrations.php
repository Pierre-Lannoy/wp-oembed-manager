<?php

/**
 * @author Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 1.0.0
 */

use Oemm\Plugin\Feature\oEmbed;

$view_anchor = __('View on WordPress.org', 'oembed-manager');
$installed   = '(' . sprintf(__('this plugin is activated on %s', 'oembed-manager'), get_bloginfo('name')) . ')';

?>

<?php foreach (oEmbed::get_descriptions() as $integration) { ?>
    <p>&nbsp;</p>
    <h2><?php echo $integration['title'];?></h2>
    <p><?php echo $integration['description'];?></p>
    <?php foreach ($integration['items'] as $item) { ?>
        <div style="margin: 8px 8px 20px 8px;">
            <div style="margin-right: 12px;float: left;">
                <img src="<?php echo $item['image'];?>" width="48px" height="48px">
            </div>
            <div>
                <span style="line-height: 1.8em;font-size: 1.1em;"><strong><?php echo $item['name'];?></strong></span>
                <?php if ($item['detected']) { ?>
                    <span style="color:#999999;"><?php echo $installed;?></span>
                <?php } ?>
                <br/>
                <span style="font-size: 0.9em;"><a href="<?php echo $item['url'];?>"><?php echo $view_anchor;?></a></span>
            </div>
        </div>
    <?php } ?>
    <p>&nbsp;</p>
<?php } ?>
