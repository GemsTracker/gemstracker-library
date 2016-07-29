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

namespace Gems\Snippets\Tracker;

/**
 * Class description of ShowTrackUsageSnippet
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class ShowTrackUsageSnippet extends \Gems_Tracker_Snippets_ShowTrackUsageAbstract
{
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Signal the bridge that we need these values
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2t_id_respondent_track;
        $bridge->gr2o_patient_nr;
        $bridge->can_edit;

        $controller = $this->request->getControllerName();

        $menuList = $this->menu->getMenuList();

        $menuList->addByController($controller, 'show-track')
                ->addByController($controller, 'edit-track')
                ->addParameterSources($bridge)
                ->setLowerCase()->showDisabled();

        $bridge->setOnEmpty($this->_('No other assignments of this track.'));

        // If we have a track Id and is not excluded: mark it!
        if ($this->respondentTrackId && (! $this->excludeCurrent)) {
            $bridge->tr()->appendAttrib('class', \MUtil_Lazy::iff(\MUtil_Lazy::comp($bridge->gr2t_id_respondent_track, '==', $this->respondentTrackId), 'currentRow', null));
        }

        // Add show-track button if allowed, otherwise show, again if allowed
        $bridge->addItemLink($menuList->getActionLink($controller, 'show-track'));

        parent::addBrowseTableColumns($bridge, $model);

        // Add edit-track button if allowed (and not current
        $bridge->addItemLink($menuList->getActionLink($controller, 'edit-track'));
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();

        $model->addColumn('CONCAT(gr2t_completed, \'' . $this->_(' of ') . '\', gr2t_count)', 'progress');
        $model->set('progress', 'label', $this->_('Progress'), 'tdClass', 'rightAlign', 'thClass', 'rightAlign');

        return $model;
    }

    protected function getTitle()
    {
        if ($this->excludeCurrent) {
            return sprintf($this->_('Other assignments of this track to %s: %s'), $this->patientId, $this->getRespondentName());
        } else {
            return sprintf($this->_('Assignments of this track to %s: %s'), $this->patientId, $this->getRespondentName());
        }
    }
}
