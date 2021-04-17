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
        'collections_browse_sql'
    );

    public function hookInitialize()
    {
        $settings = json_decode(get_option('search_by_metadata_elements'), true);
        $this->_settings = $settings;

        if (is_admin_theme() && !get_option('search_by_metadata_admin_side')) return;
        
        if (is_array($settings)){
            if (is_array($settings['item_elements'])) {
                // Items
                foreach ($settings['item_elements'] as $elementSetName=>$elementSet) {
                    foreach ($elementSet as $elementName=>$isEnabled) {
                        // for DC:Title on browse pages,
                        // need to point to a special filter, since Request doesn't exist here
                        if ($elementSetName == 'Dublin Core' && $elementName == 'Title') {
                            add_filter(array('Display', 'Item', $elementSetName, $elementName), array($this, 'linkItemDcTitle'));
                        } else {
                            add_filter(array('Display', 'Item', $elementSetName, $elementName), array($this, 'linkItem'));
                        }
                        
                    }
                }
            }
            if (is_array($settings['collection_elements'])) {
                // Collections
                foreach ($settings['collection_elements'] as $elementSetName=>$elementSet) {
                    foreach ($elementSet as $elementName=>$isEnabled) {
                        // for DC:Title on browse pages,
                        // need to point to a special filter, since Request doesn't exist here
                        if ($elementSetName == 'Dublin Core' && $elementName == 'Title') {
                            add_filter(array('Display', 'Collection', $elementSetName, $elementName), array($this, 'linkCollectionDcTitle'));
                        } else {
                            add_filter(array('Display', 'Collection', $elementSetName, $elementName), array($this, 'linkCollection'));
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
        // set_option('search_by_metadata_merge_results', 0);
        set_option('search_by_metadata_admin_side', 0);
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
            // set_option('search_by_metadata_merge_results', 0);
            set_option('search_by_metadata_admin_side', 0);
        }
    }

    public function hookUninstall()
    {
        delete_option('search_by_metadata_show_tooltip');
        // delete_option('search_by_metadata_merge_results');
        delete_option('search_by_metadata_admin_side');
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
        set_option('search_by_metadata_admin_side', (bool)$post['search_by_metadata_admin_side']);
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
    
    /**
     * Hook into collections_browse_sql
     *
     * @select array $args
     * @params array $args
     */
    public function hookCollectionsBrowseSql($args)
    {
        $db = $this->_db;
        $select = $args['select'];
        $params = $args['params'];
        
        if ($advancedTerms = @$params['advanced']) {
            $where = '';
            $advancedIndex = 0;
            foreach ($advancedTerms as $v) {
                // Do not search on blank rows.
                if (empty($v['element_id']) || empty($v['type'])) {
                    continue;
                }

                $value = isset($v['terms']) ? $v['terms'] : null;
                $type = $v['type'];
                $elementId = (int) $v['element_id'];
                $alias = "_advanced_{$advancedIndex}";

                $joiner = isset($v['joiner']) && $advancedIndex > 0 ? $v['joiner'] : null;

                $negate = false;
                // Determine what the WHERE clause should look like.
                switch ($type) {
                    case 'does not contain':
                        $negate = true;
                    case 'contains':
                        $predicate = "LIKE " . $db->quote('%'.$value .'%');
                        break;

                    case 'is not exactly':
                        $negate = true;
                    case 'is exactly':
                        $predicate = ' = ' . $db->quote($value);
                        break;

                    case 'is empty':
                        $negate = true;
                    case 'is not empty':
                        $predicate = 'IS NOT NULL';
                        break;

                    case 'starts with':
                        $predicate = "LIKE " . $db->quote($value.'%');
                        break;

                    case 'ends with':
                        $predicate = "LIKE " . $db->quote('%'.$value);
                        break;

                    case 'does not match':
                        $negate = true;
                    case 'matches':
                        if (!strlen($value)) {
                            continue 2;
                        }
                        $predicate = 'REGEXP ' . $db->quote($value);
                        break;

                    default:
                        throw new Omeka_Record_Exception(__('Invalid search type given!'));
                }

                $predicateClause = "{$alias}.text {$predicate}";

                // Note that $elementId was earlier forced to int, so manual quoting
                // is unnecessary here
                $joinCondition = "{$alias}.record_id = collections.id AND {$alias}.record_type = 'Collection' AND {$alias}.element_id = $elementId";

                if ($negate) {
                    $joinCondition .= " AND {$predicateClause}";
                    $whereClause = "{$alias}.text IS NULL";
                } else {
                    $whereClause = $predicateClause;
                }

                $select->joinLeft(array($alias => $db->ElementText), $joinCondition, array());
                if ($where == '') {
                    $where = $whereClause;
                } elseif ($joiner == 'or') {
                    $where .= " OR $whereClause";
                } else {
                    $where .= " AND $whereClause";
                }

                $advancedIndex++;
            }

            if ($where) {
                $select->where($where);
            }
        }            
    }
    
    public function linkItemDcTitle($text, $args)
    {
        return $this->createLinkDcTitle($text, $args, 'Items');
    }

    public function linkCollectionDcTitle($text, $args)
    {
        return $this->createLinkDcTitle($text, $args, 'Collections');
    }

    /**
     * Turn DC:Title's text into link if not on Browse page,
     * as this creates links right back to the browse page search;
     */
    public function createLinkDcTitle($text, $args, $model)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $action = $request->getActionName();
        if ($action != 'browse') {
            return $this->createLink($text, $args, $model);
        }
        return $text;
    }

    public function linkItem($text, $args)
    {
        return $this->createLink($text, $args, 'Items');
    }

    public function linkCollection($text, $args)
    {
        return $this->createLink($text, $args, 'Collections');
    }
    
    /**
     * Turn element's text into link (with additional tooltip)
     */
    public function createLink($text, $args, $model)
    {
        $elementText = $args['element_text'];
        if (trim($text) == '' || !$elementText) return $text;
        $url_base = strtolower($model) . '/browse';
        $elementId = $elementText->element_id;
        $url = url($url_base, array(
            'advanced' => array(
                array(
                    'element_id' => $elementId,
                    'type' => 'is exactly',
                    'terms' =>$elementText->text
                )
            )
        ));

        $tooltip = (get_option('search_by_metadata_show_tooltip') ? " title=\"" . __('Browse other %s featuring exactly this same value', __($model)) . "\"" : "");
                    
        return "<a href=\"$url\"" . $tooltip . ">$text</a>";
    }
}
