<?php
/**
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Export;

/**
 * Show info about the respondent during html/pdf export
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class RespondentSnippet extends \MUtil\Snippets\SnippetAbstract
{
    /**
     * The data for the current respondentId
     *
     * @var array
     */
    public $data;

    /**
     * @var \Gems\Model\RespondentModel
     */
    public $model;

    /**
     * The respondent we are looking at
     *
     * @var int
     */
    public $respondentId;

    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        parent::getHtmlOutput($view);

        $respondentModel = $this->model;
        $respondentData = $this->data;
        $respondentId = $this->respondentId;

        $html = $this->getHtmlSequence();
        if (empty($this->data)) {
            $html->p()->b(sprintf($this->_('Unknown respondent %s'), $respondentId));
            return $html;
        }

        $bridge = $respondentModel->getBridgeFor('itemTable', array('class' => 'browser table copy-to-clipboard-before'));
        $bridge->setRepeater(\MUtil\Lazy::repeat(array($respondentData)));
        $bridge->th($this->_('Respondent information'), array('colspan' => 4));
        $bridge->setColumnCount(2);
        foreach($respondentModel->getItemsOrdered() as $name) {
            if ($label = $respondentModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        $tableContainer = \MUtil\Html::create()->div(array('class' => 'table-container'));
        $tableContainer[] = $bridge->getTable();

        $html->h3($this->_('Respondent information') . ': ' . $respondentId);
        $html[] = $tableContainer;
        $html->hr();

        return $html;
    }

}