<?php


class SearchByMetadataPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'uninstall', 
        'config', 
        'config_form',
        );

    public function setUp()
    {
        parent::setUp();
        $linkedElements = unserialize(get_option('search_by_metadata_elements'));
        if(is_array($linkedElements)){
            foreach($linkedElements as $elementSet=>$elements) {
                foreach($elements as $element) {
                    //don't add links on DC:Title if on browse pages,
                    //as this creates links right back to the browse page search
                    //need to punt to a special filter, since Request doesn't exist here
                    if ($elementSet == 'Dublin Core'
                        && $element == 'Title') {
                        add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'linkDcTitle'));
                    } else {
                        add_filter(array('Display', 'Item', $elementSet, $element), array($this, 'link'));
                    }
                    
                }
            }
        }
    }

    public function hookInstall()
    {
        set_option('search_by_metadata_elements', serialize(array()));
    }

    public function hookUninstall()
    {
        delete_option('search_by_metadata_elements');
    }

    public function hookConfig($args)
    {   
        $post = $args['post'];
        $elements = array();
        $elTable = get_db()->getTable('Element');
        foreach($post['element_sets'] as $elId) {
            $element = $elTable->find($elId);
            $elSet = $element->getElementSet();
            if(!array_key_exists($elSet->name, $elements)) {
                $elements[$elSet->name] = array();
            }
            $elements[$elSet->name][] = $element->name;
        }
        set_option('search_by_metadata_elements', serialize($elements));
    }

    public function hookConfigForm()
    {
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
    
    public function link($text, $args)//$record, $elementText)
    {
        
        $record = $args['record'];
        $elementText = $args['element_text'];
        if (trim($text) == '' || !$elementText) return $text;

        $elementId = $elementText->element_id;
        $url = url('items/browse', array(
            'advanced' => array(
                array(
                    'element_id' => $elementId,
                    'type' => 'is exactly',
                    'terms' =>$elementText->text,
                )
            )
        ));
        return "<a href=\"$url\">$text</a>";
    }
    


}
