<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 18-Apr-2018 18:40:08
 */
class TrackTableSnippet extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var \Pulse_Tracker_Respondent
     */
    protected $respondent;

    /**
     *
     * @var \Pulse_Util
     */
    protected $util;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        parent::processFilterAndSort($model);

        $filter = $model->getFilter();

        if (isset($filter['gr2o_id_organization'])) {
            $otherOrgs = $this->util->getOtherOrgsFor($filter['gr2o_id_organization']);
            if (is_array($otherOrgs)) {
                // If more than one org, do not use patient number but resp id
                if (isset($filter['gr2o_patient_nr'])) {
                    $filter['gr2o_id_user'] = $this->respondent->getId();
                    unset($filter['gr2o_patient_nr']);
                }

                $filter['gr2o_id_organization'] = $otherOrgs;

                // Second filter, should be changed as well
                if (isset($this->extraFilter['gr2t_id_organization'])) {
                    $this->extraFilter['gr2t_id_organization'] = $otherOrgs;
                }
                $model->setFilter($filter);
            }
        }
    }
}
