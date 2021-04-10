<?php

/**
 * Search By Metadata plugin.
 *
 * @package Omeka\Plugins\SearchByMetadata
 */

class SearchByMetadataPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'initialize',
        'install',
        'upgrade',
        'uninstall', 
        'config', 
        'config_form',
    );

    public function hookInitialize()
    {
        $settings = json_decode(get_option('search_by_metadata_elements'), true);
        $this->_settings = $settings;

        if (is_admin_theme()) return;
        
        if (is_array($settings)){
            if (is_array($settings['item_elements'])) {
                // Items
                foreach ($settings['item_elements'] as $elementSetName=>$elementSet) {
                    foreach ($elementSet as $elementName=>$isEnabled) {
                        // don't add links on DC:Title if on browse pages,
                        // as this creates links right back to the browse page search;
                        // need to point to a special filter, since Request doesn't exist here
                        if ($elementSetName == 'Dublin Core' && $elementName == 'Title') {
                            add_filter(array('Display', 'Item', $elementSetName, $elementName), array($this, 'linkDcTitle'));
                        } else {
                            add_filter(array('Display', 'Item', $elementSetName, $elementName), array($this, 'link'));
                        }
                        
                    }
                }
            }
            if (is_array($settings['collection_elements'])) {
                // Collections
                foreach ($settings['collection_elements'] as $elementSetName=>$elementSet) {
                    foreach ($elementSet as $elementName=>$isEnabled) {
                        // don't add links on DC:Title if on browse pages,
                        // as this creates links right back to the browse page search;
                        // need to point to a special filter, since Request doesn't exist here
                        if ($elementSetName == 'Dublin Core' && $elementName == 'Title') {
                            add_filter(array('Display', 'Collection', $elementSetName, $elementName), array($this, 'linkDcTitle'));
                        } else {
                            add_filter(array('Display', 'Collection', $elementSetName, $elementName), array($this, 'link'));
                        }
                        
                    }
                }
            }
        }
    }

    public function hookInstall()
    {
        $defaults = array(
            'item_elements' => array(),
            'collection_elements' => array()
        );
        set_option('search_by_metadata_elements', json_encode($defaults));

        set_option('search_by_metadata_show_tooltip', 0);
        set_option('search_by_metadata_merge_results', 0);
    }

    /**
     * Upgrade the plugin.
     */
    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;

        if (version_compare($oldVersion, '2.0', '<')) {
            $settings = array(
                'item_elements' => array(),
                'collection_elements' => array()
            );

            $linkedElements = unserialize(get_option('search_by_metadata_elements'));
            if (is_array($linkedElements)) {
                foreach ($linkedElements as $elSet=>$elements) {
                    foreach ($elements as $element) {
                        $settings['item_elements'][$elSet][$element] = 1;
                    }
                }
            }

            set_option('search_by_metadata_elements', json_encode($settings));

            set_option('search_by_metadata_show_tooltip', 0);
            set_option('search_by_metadata_merge_results', 0);
        }
    }

    public function hookUninstall()
    {
        delete_option('search_by_metadata_show_tooltip');
        delete_option('search_by_metadata_merge_results');
        delete_option('search_by_metadata_elements');
    }

    public function hookConfig($args)
    {   
        $post = $args['post'];

        $settings = array(
            'item_elements' => isset($post['item_elements']) ? $post['item_elements'] : array(),
            'collection_elements' => isset($post['collection_elements']) ? $post['collection_elements'] : array()
        );
        set_option('search_by_metadata_elements', json_encode($settings));
        set_option('search_by_metadata_show_tooltip', (bool)$post['search_by_metadata_show_tooltip']);
        set_option('search_by_metadata_merge_results', (bool)$post['search_by_metadata_merge_results']);
    }

    public function hookConfigForm()
    {
        $settings = $this->_settings;

        $table = get_db()->getTable('Element');
        $select = $table->getSelect()
            ->order('elements.element_set_id')
            ->order('ISNULL(elements.order)')
            ->order('elements.order');

        $elements = $table->fetchObjects($select);
        include('config_form.php');
    }

    public function linkDcTitle($text, $args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $action = $request->getActionName();
        if ($action != 'browse') {
            return $this->link($text, $args);
        }
        return $text;
    }
    
    public function link($text, $args)
    {
        $elementText = $args['element_text'];
        if (trim($text) == '' || !$elementText) return $text;
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $controllerName = $request->getControllerName();

        $elementId = $elementText->element_id;
        $url = url($controllerName . '/browse', array(
            'advanced' => array(
                array(
                    'element_id' => $elementId,
                    'type' => 'is exactly',
                    'terms' =>$elementText->text,
                )
            )
        ));

        $tooltip = (get_option('search_by_metadata_show_tooltip') ? " title=\"" . __('Browse other %s featuring exactly this same value', $controllerName) . "\"" : "");
                    
        return "<a href=\"$url\"" . $tooltip . ">$text</a>";
    }
}
