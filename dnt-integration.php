<?php


/**
 * This class is responsible of the "Do Not Track" integration management.
 *
 * @author Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
 * @since 1.0.0
 */
class OEMMDntIntegration extends OEMMIntegration{

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function initialize() {
        $this->add_dnt_stats();
    }

    /**
     * Add a managed integration for "Do Not Track Stats" / Pierre Lannoy.
     *
     * @since 1.0.0
     */
    private function add_dnt_stats() {
        $result = $this->get_template();
        $result['id'] = 'do-not-track-stats';
        $result['name'] = 'Do Not Track Stats';
        $result['url'] = 'https://wordpress.org/plugins/do-not-track-stats/';
        $result['image'] = plugin_dir_url(__FILE__) . 'medias/' . $result['id'] . '-icon-128x128.png';
        $result['backend_detection']['rule'] = 'defined';
        $result['backend_detection']['name'] = 'DO_NOT_TRACK_STATUS';
        $result['frontend_detection']['rule'] = 'defined';
        $result['frontend_detection']['name'] = 'DO_NOT_TRACK_STATUS';
        $result['execution']['rule'] = 'constant_value';
        $result['execution']['name'] = 'DO_NOT_TRACK_STATUS';
        $result['execution']['param'] = 'included-opposition';
        $result['execution']['reverted'] = true;
        $this->integrations[] = $result;
    }

}