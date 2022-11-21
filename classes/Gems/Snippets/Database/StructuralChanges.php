<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Database
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Database;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Database
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 8:16:38 PM
 */
class StructuralChanges extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var string The id of a div that contains the table.
     */
    protected $containingId;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var array The input data for the model
     */
    protected $searchData = false;

    /**
     * Containing patch levels
     *
     * @var array
     */
    protected $patchLevels;

    /**
     * The form for selecting the patch level
     *
     * @return \Gems\Form
     */
    protected function _getSelectForm()
    {
        $form = new \Gems\Form(array('name' => 'autosubmit', 'class' => 'form-inline', 'role' => 'form'));

        $form->setHtml('div');
        $div = $form->getHtml();
        $div->class = 'search';

        $span = $div->div(array('class' => 'panel panel-default'))->div(array('class' => 'inputgroup panel-body'));

        $element = $form->createElement('select', 'gpa_level', array(
            'multiOptions' => $this->patchLevels,
            'onchange' => 'this.form.submit();',
            'onkeyup' => 'this.form.submit();',
            ));

        $element->setValue($this->getPatchLevel());
        $span->input($element);

        $form->addElement($element);

        $submit = $form->createElement('submit', 'search', array(
            'label' => $this->_('Search'),
            'class' => 'button small',
            ));

        $span->input($submit);
        $form->addElement($submit);

        return $form;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view = null)
    {
        if (! $this->patchLevels) {
            $this->addMessage($this->_('This new project has no structural changes to show.'));
            return;
        }

        $patchLevel = $this->getPatchLevel();

        $seq = $this->getHtmlSequence();

        $seq->append($this->_getSelectForm());

        $div = $seq->div(array('id' => 'autofilter_target'));
        $div->h2(sprintf($this->_('Structural changes in patch level %d'), $patchLevel));
        $div->pInfo($this->_('Download: '))
                ->a(
                        array('download' => 1, 'gpa_level' => $patchLevel),
                        sprintf('patchlevel.%d.sql', $patchLevel),
                        array('type' => 'application/download')
                        );

        $lastLocation = '';
        $lastName     = '';
        $noOutput     = true;

        foreach ($this->getStructuralPatches($patchLevel) as $patch) {
            if ($patch['gpa_location'] != $lastLocation) {
                $div->h3(sprintf($this->_('Group %s'), $patch['gpa_location']));
                $lastLocation = $patch['gpa_location'];
            }
            if ($patch['gpa_name'] != $lastName) {
                $div->h4(sprintf($this->_('Patch %s'), $patch['gpa_name']));
                $lastName = $patch['gpa_name'];
            }
            $div->pre(wordwrap($patch['gpa_sql'], 80, "\n    "));
            $noOutput = false;
        }

        if ($noOutput) {
            $div->pInfo(sprintf($this->_('No structural changes in patchlevel %d'), $patchLevel));
        }

        return $seq;
    }

    /**
     * Get the structural patches for the given patch level
     * @param int $patchLevel
     * @return array
     */
    public function getStructuralPatches($patchLevel)
    {
        $patches = $this->db->fetchAll(
                "SELECT * FROM gems__patches WHERE gpa_level = ? ORDER BY gpa_location, gpa_name, gpa_order",
                $patchLevel
                );

        foreach ($patches as $patchId => $patch) {
            if (\MUtil\StringUtil\StringUtil::startsWith(trim($patch['gpa_sql']), 'INSERT', true) ||
                    \MUtil\StringUtil\StringUtil::startsWith(trim($patch['gpa_sql']), 'UPDATE', true) ||
                    \MUtil\StringUtil\StringUtil::startsWith(trim($patch['gpa_sql']), 'DELETE', true)) {
                unset($patches[$patchId]);
            }
        }

        return $patches;
    }

    /**
     * Get the content as text
     *
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getTextOutput()
    {
        $patchLevel = $this->getPatchLevel();

        $commands     = array(sprintf('-- Patch level %d structural changes', $patchLevel));
        $lastLocation = '';
        $lastName     = '';
        $noOutput     = true;
        foreach ($this->getStructuralPatches($patchLevel) as $patch) {
            if ($patch['gpa_location'] != $lastLocation) {
                $commands[] = sprintf("\n-- DATABASE LOCATION: %s", $patch['gpa_location']);
                $lastLocation = $patch['gpa_location'];
            }
            if ($patch['gpa_name'] != $lastName) {
                $commands[] = sprintf("\n-- PATCH: %s", $patch['gpa_name']);
                $lastName = $patch['gpa_name'];
            }
            $commands[] = $patch['gpa_sql'] . ';';
            $noOutput = false;
        }

        if ($noOutput) {
            $commands[] = sprintf("\n-- No structural changes in patchlevel %d", $patchLevel);
        }
        $commands[] = '';

        return implode("\n", $commands);
    }

    /**
     * Current patch level
     *
     * @return int
     */
    protected function getPatchLevel()
    {
        if (isset($this->searchData['gpa_level']) && $this->searchData['gpa_level']) {
            return $this->searchData['gpa_level'];
        }

        return reset($this->patchLevels);
    }

    public function outputText(\Zend_View_Abstract $view, \Zend_Controller_Action_HelperBroker $helper)
    {
        $view->layout()->disableLayout();
        $helper->viewRenderer->setNoRender(true);

        header("Content-Type: application/download");
        header(sprintf('Content-Disposition: attachment; filename="patchlevel.%d.sql"', $this->getPatchLevel()));
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: cache");                          // HTTP/1.0

        echo $this->getTextOutput();

        exit;
    }
}
