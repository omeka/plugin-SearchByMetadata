<p>Check the metadata fields that you want to make search links. Recommended best practice is to use a controlled
vocabulary on the fields to make links.</a>

<?php
$elTable = get_db()->getTable('Element');
$data = $elTable->findPairsForSelectForm();
$linkedElements = unserialize(get_option('search_by_metadata_elements'));
$values = array();
foreach($linkedElements as $elSet=>$elements) {
    foreach($elements as $element) {
        $elObject = $elTable->findByElementSetNameAndElementName($elSet, $element);
        $values[] = $elObject->id;
    }


}

foreach($data as $elSet=>$options) {
    echo "<div class='field'>";
    echo "<h2>$elSet</h2>";
    echo __v()->formMultiCheckbox('element_sets', $values, null, $options, '');
    echo "</div>";
}


?>














