<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Snippets\Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Snippets_Mail_CommTemplateShowSnippet extends \Gems_Snippets_ModelItemTableSnippetGeneric
{
    /**
     * One of the \MUtil_Model_Bridge_BridgeAbstract MODE constants
     *
     * @var int
     */
    protected $bridgeMode = \MUtil_Model_Bridge_BridgeAbstract::MODE_SINGLE_ROW;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $items = $model->getItemsOrdered();
        foreach($items as $name) {
            if ($model->get($name, 'type') === \MUtil_Model::TYPE_CHILD_MODEL) {
                $submodel = $model->get($name, 'model');
                $subitems = $submodel->getItemsOrdered();
            }
        }

        if (isset($subitems) && is_array($subitems)) {
            $items = array_diff($items, $subitems);
        }

        foreach($items as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        if ($model->has('row_class')) {
            // Make sure deactivated rounds are show as deleted
            foreach ($bridge->getTable()->tbody() as $tr) {
                foreach ($tr as $td) {
                    if ('td' === $td->tagName) {
                        $td->appendAttrib('class', $bridge->row_class);
                    }
                }
            }
        }

        if ($submodel) {
            $subrow = $bridge->tr();
            $subrow->th();
            $subContainer = $subrow->td();
            $this->addSubModelTable($bridge, $submodel, $subContainer);
        }
    }

    /**
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $subModel
     * @param \MUtil_Html_TdElement $subContainer
     */
    protected function addSubModelTable(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $subModel, \MUtil_Html_TdElement $subContainer)
    {
        $data         = $bridge->getRow();
        $itemBridge   = new \MUtil_Model_Bridge_DisplayBridge($subModel);
        $submodelName = $subModel->getName();
        $multi        = count($data[$submodelName]) > 1;

        foreach($data[$submodelName] as $item) {
            $itemBridge->setRow($item);
            if ($multi && isset($item['gctt_lang'])) {
                $subContainer->h3($bridge->format('gctt_lang', $item['gctt_lang']));
            }
            $subTable = $subContainer->table();
            foreach($item as $subColumnName => $subValue) {
                $subLabel = $subModel->get($subColumnName, 'label');
                if ($subLabel) {
                    $row = $subTable->tr();
                    $row->th($subLabel);
                    $row->td($itemBridge->format($subColumnName, $item[$subColumnName]));
                }
            }
        }
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $model = $this->getModel();
        if ($this->trackUsage) {
            $model->trackUsage();
        }

        $table = $this->getShowTable($model);
        $table->setRepeater($this->getRepeater($model));

        return $table;
    }
}