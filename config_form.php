<p>Check the metadata fields that you want to make into search links. Recommended best practice is to use a controlled
vocabulary with the <a href="http://omeka.org/add-ons/plugins/simple-vocab/">Simple Vocab</a> plugin on the fields that will be made into links.</p>

<?php
$elTable = get_db()->getTable('Element');
$select = $elTable->getSelectForFindBy(array('record_types' => array('Item', 'All')));
$select->reset(Zend_Db_Select::COLUMNS);
$select->from(array(), array(
    'id' => 'elements.id',
    'name' => 'elements.name',
    'set_name' => 'element_sets.name',
));
$data = array();
$elements = $elTable->fetchAll($select);
foreach ($elements as $element) {
    $data[__($element['set_name'])][$element['id']] = __($element['name']);
}

$view = get_view();
$values = array();
if(is_array($linkedElements)) {
    foreach($linkedElements as $elSet=>$elements) {
        foreach($elements as $element) {
            $elObject = $elTable->findByElementSetNameAndElementName($elSet, $element);
            $values[] = $elObject->id;
        }
    }
}
?>
<?php foreach($data as $elSet => $options): ?>
<h2><?php echo html_escape($elSet); ?></h2>
<div class="field" style="overflow: hidden">
    <?php echo $view->formMultiCheckbox('element_sets', $values, null, $options, ''); ?>
</div>
<?php endforeach;
