<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TracksForAppointment.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Track;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Track
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 10, 2016 6:17:20 PM
 */
class TracksForAppointment extends TracksSnippet
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        $this->caption = $this->_("Tracks using this appointment");
        $this->onEmpty = $this->_("No tracks use this appointment");
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil_Model_ModelAbstract $model)
    {
        $filter[] = $this->db->quoteInto(
                "gr2t_id_respondent_track IN (
                    SELECT gr2t2a_id_respondent_track
                    FROM gems__respondent2track2appointment
                    WHERE gr2t2a_id_appointment = ?)",
                $this->request->getParam(\Gems_Model::APPOINTMENT_ID)
                );

        // \MUtil_Model::$verbose = true;

        $model->setFilter($filter);
        $this->processSortOnly($model);
    }
}
