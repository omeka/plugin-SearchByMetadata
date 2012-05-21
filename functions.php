<?php

/**
 * Returns an array of elements matching the criteria in $elementsArray
 * Results are ANDed together
 * $elementsArray is like:
 * array('Dublin Core' => array(
 * 								'Subject' => array('subject 1', 'subject 2')
 * 								)
 * 		 //other element sets follow this pattern
 * 		);
 */


function search_by_metadata_find_items($elementsArray)
{
    $itemTable = get_db()->getTable('Item');
    $elementTable = get_db()->getTable('Element');
    $params = array('advanced_search' => array());
    foreach($elementsArray as $elementSet=>$elements) {
        foreach($elements as $element=>$terms) {
            $element = $elementTable->findByElementSetNameAndElementName($elementSet, $element);
            foreach($terms as $term) {
                $params['advanced_search'][] = array('terms' => $term,
                                                     'type' => 'is exactly',
                                                     'element_id' => $element->id );
            }
        }
    }
    return $itemTable->findBy($params);
}