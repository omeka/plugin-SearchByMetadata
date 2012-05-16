<?php


class SearchByMetadataPlugin extends Omeka_Plugin_Abstract
{
    protected $_hooks = array('uninstall', 'config', 'config_form');

    public function init()
    {
        $linkedElements = get_option('search_by_metadata_elements');
        $linkedElements = array('Dublin Core' => array('Subject'));
        foreach($linkedElements as $elementSet=>$elements) {
            foreach($elements as $element) {
                add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'link'));
            }
        }
    }

    public function hookInstall()
    {
        set_option('search_by_metadata_elements', array());
    }

    public function hookUninstall()
    {
        delete_option('search_by_metadata_elements');
    }

    public function hookConfig()
    {

    }

    public function hookConfigForm()
    {

    }

    public function link($text, $record, $elementText)
    {
        if (trim($text) == '') return $text;

        $elementId = $elementText->element_id;
        $url = uri('items/browse', array(
            'advanced' => array(
                array(
                    'element_id' => $elementId,
                    'type' => 'is exactly',
                    'terms' => $text
                )
            )
        ));
        return "<a href=\"$url\">$text</a>";
    }


}