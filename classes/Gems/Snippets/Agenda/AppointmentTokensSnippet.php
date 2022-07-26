<?php

/**
 *
 * @package    Gems
 * @subpackage AppointmentTokensSnippet
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Agenda;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentTokensSnippet
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 14, 2016 3:19:57 PM
 */
class AppointmentTokensSnippet extends \Gems\Snippets\Token\RespondentTokenSnippet
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
        $this->caption = $this->_("Tokens set by this appointment");
        $this->onEmpty = $this->_("No tokens are set by this appointment");
    }

    /**
     * Overrule to implement snippet specific filtering and sorting.
     *
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function processFilterAndSort(\MUtil\Model\ModelAbstract $model)
    {
        parent::processFilterAndSort($model);

        $appId = $this->request->getParam(\Gems\Model::APPOINTMENT_ID);

        if ($appId) {
            $appKeyPrefix = $this->db->quote(FieldsDefinition::makeKey(FieldMaintenanceModel::APPOINTMENTS_NAME, ''));
            $appSource    = $this->db->quote(\Gems\Tracker\Engine\StepEngineAbstract::APPOINTMENT_TABLE);

            $or[] = $this->db->quoteInto(
                    "gro_valid_after_source = $appSource AND
                        (gto_id_respondent_track, gro_valid_after_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT($appKeyPrefix, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = ?)",
                    $appId
                    );
            $or[] = $this->db->quoteInto(
                    "gro_valid_for_source = $appSource AND
                        (gto_id_respondent_track, gro_valid_for_field) IN
                            (SELECT gr2t2a_id_respondent_track, CONCAT($appKeyPrefix, gr2t2a_id_app_field)
                                FROM gems__respondent2track2appointment
                                WHERE gr2t2a_id_appointment = ?)",
                    $appId
                    );
        }

        $model->addFilter(array('(' . implode(') OR (', $or) . ')'));
    }
}
