<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Snippets\Token;

/**
 * Respondent token filter tabs
 *
 * Abstract class for quickly creating a tabbed bar, or rather a div that contains a number
 * of links, adding specific classes for display.
 *
 * A snippet is a piece of html output that is reused on multiple places in the code.
 *
 * Variables are intialized using the {@see \MUtil_Registry_TargetInterface} mechanism.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class TokenTabsSnippet extends \MUtil_Snippets_TabSnippetAbstract
{
    /**
     * Default href parameter values
     *
     * Clicking a tab always resets the page counter
     *
     * @var array
     */
    protected $href = array('page' => null);

    /**
     * The RESPONDENT model, not the token model
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        return 'filter';
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs()
    {
        $tabs['default'] = array($this->_('Default'), 'title' => $this->_('To do 2 weeks ahead and done'));
        $tabs['todo']    = $this->_('To do');
        $tabs['done']    = $this->_('Done');
        $tabs['missed']  = $this->_('Missed');
        $tabs['all']     = $this->_('All');

        return $tabs;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        $reqFilter = $this->request->getParam('filter');
        switch ($reqFilter) {
            case 'todo':
                //Only actions valid now that are not already done
                $filter[] = 'gto_completion_time IS NULL';
                $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                break;
            case 'done':
                //Only completed actions
                $filter[] = 'gto_completion_time IS NOT NULL';
                break;
            case 'missed':
                //Only missed actions (not filled in, valid until < today)
                $filter[] = 'gto_completion_time IS NULL';
                $filter[] = 'gto_valid_until < CURRENT_TIMESTAMP';
                break;
            case 'all':
                $filter[] = 'gto_valid_from IS NOT NULL';
                break;
            default:
                //2 weeks look ahead, valid from date is set
                $filter[] = 'gto_valid_from IS NOT NULL';
                $filter[] = 'DATEDIFF(gto_valid_from, CURRENT_TIMESTAMP) < 15';
                $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
        }
        $this->model->setMeta('tab_filter', $filter);

        return parent::hasHtmlOutput();
    }
}
