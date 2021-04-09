<?php 
	$view = get_view();
?>
<style type = "text/css">
	.boxes {
		text-align: center;
		vertical-align: middle;
	}
</style>

<h2><?php echo __('General settings'); ?></h2>

<div class="field">
    <div class="two columns alpha">
        <?php echo $view->formLabel('search_by_metadata_show_tooltip', __('Add tooltip')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('If checked, a tooltip will be shown hovering over metadata links.'); ?>
        </p>
        <?php echo $view->formCheckbox('search_by_metadata_show_tooltip', get_option('search_by_metadata_show_tooltip'), null, array('1', '0')); ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <?php echo $view->formLabel('search_by_metadata_merge_results', __('Merge results')); ?>
    </div>
    <div class="inputs five columns omega">
        <p class="explanation">
            <?php echo __('If checked, links will point to both Items and Collections; otherwise, an Item\'s link will point only to other Items, and a Collection\'s link will point only to other Collections.'); ?>
        </p>
        <?php echo $view->formCheckbox('search_by_metadata_merge_results', get_option('search_by_metadata_merge_results'), null, array('1', '0')); ?>
    </div>
</div>


<h2><?php echo __('Elements'); ?></h2>

<p>Check the metadata fields that you want to make into search links. Recommended best practice is to use a controlled
vocabulary with a plugin like <a href="https://omeka.org/classic/plugins/SimpleVocab/">Simple Vocab</a> or <a href="https://omeka.org/classic/plugins/SimpleVocabPlus/">Simple Vocab Plus</a> on the fields that will be made into links.</p>

<table id = "elements-table">
	<thead>
		<tr>
			<th class="boxes" rowspan = "1"><?php echo __('Element'); ?></th>
			<th class="boxes" colspan = "1"><?php echo __('Item'); ?></th>
			<th class="boxes" colspan = "1"><?php echo __('Collection'); ?></th>
		</tr>
	</thead>
	<tbody>
	<?php
		$current_element_set = null;
		foreach ($elements as $element):
			if ($element->set_name != $current_element_set):
				$current_element_set = $element->set_name;
	?>
		<tr>
			<th colspan = "3">
				<strong><?php echo __($current_element_set); ?></strong>
			</th>
		</tr>
		<?php endif; ?>
		<tr>
			<td><?php echo __($element->name); ?></td>
			<td class="boxes">
				<?php echo $view->formCheckbox(
					"item_elements[{$element->set_name}][{$element->name}]",
					'1', 
					array(
						'disableHidden' => true,
						'checked' => isset($settings['item_elements'][$element->set_name][$element->name])
					)
				); ?>
			</td>
			<td class="boxes">
				<?php echo $view->formCheckbox(
					"collection_elements[{$element->set_name}][{$element->name}]",
					'1', 
					array(
						'disableHidden' => true,
						'checked' => isset($settings['collection_elements'][$element->set_name][$element->name])
					)
				); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>