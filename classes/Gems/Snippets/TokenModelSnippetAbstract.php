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

/**
 * Extra code for displaying token models.
 *
 * Adds columns to the model and adds extra logic for calc_used_date sorting.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class Gems_Snippets_TokenModelSnippetAbstract extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * A model, not necessarily the token model
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     */
    protected function addTokenLinks(\MUtil_Model_Bridge_TableBridge $bridge)
    {
        $title = \MUtil_Html::create()->strong($this->_('+'));

        $showLinks[]   = $this->createMenuLink($bridge, 'track',  'show', $title);

        // Remove nulls
        $showLinks   = array_filter($showLinks);

        if ($showLinks) {
            foreach ($showLinks as $showLink) {
                if ($showLink) {
                    $showLink->title = array($this->_('Token'), $bridge->gto_id_token->strtoupper());
                }
            }
        }
        $bridge->addItemLink($showLinks);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if ($this->model instanceof \Gems_Tracker_Model_StandardTokenModel) {
            $model = $this->model;
        } else {
            $model = $this->loader->getTracker()->getTokenModel();
        }
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN gto_valid_from ELSE gto_completion_time END',
            'calc_used_date',
            'gto_valid_from');
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL THEN gto_valid_from ELSE NULL END',
            'calc_valid_from',
            'gto_valid_from');
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL AND grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND (gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP) THEN gto_id_token ELSE NULL END',
            'calc_id_token',
            'gto_id_token');
        $model->addColumn(
            'CASE WHEN gto_completion_time IS NULL AND grc_success = 1 AND gto_valid_from <= CURRENT_TIMESTAMP AND gto_completion_time IS NULL AND gto_valid_until < CURRENT_TIMESTAMP THEN 1 ELSE 0 END',
            'was_missed');
        return $model;
    }

    /**
     * calc_used_date has special sort, see bugs 108 and 127
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function sortCalcDateCheck(\MUtil_Model_ModelAbstract $model)
    {
        $sort = $model->getSort();

        if (isset($sort['calc_used_date'])) {
            $add        = true;
            $resultSort = array();

            foreach ($sort as $key => $asc) {
                if ('calc_used_date' === $key) {
                    if ($add) {
                        $resultSort['is_completed']        = $asc;
                        $resultSort['gto_completion_time'] = $asc == SORT_ASC ? SORT_DESC : SORT_ASC;
                        $resultSort['calc_valid_from']     = $asc;
                        $add = false; // We can add this only once
                    }
                } else {
                    $resultSort[$key] = $asc;
                }
            }

            if (! $add) {
                $model->setSort($resultSort);
            }
        }
    }
}
