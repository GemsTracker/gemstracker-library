<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 19, 2016 6:26:42 PM
 */
class CheckTrackOrganizationImportTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($lineNr = null, $organizationData = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (isset($organizationData['gor_id_organization']) && $organizationData['gor_id_organization']) {

            $oldId = $organizationData['gor_id_organization'];

        } else {
            $oldId = false;
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gor_id_organization not specified for organization at line %d.'),
                    $lineNr
                    ));
        }

        if (isset($organizationData['gor_name']) && $organizationData['gor_name']) {
            if ($oldId) {
                $orgId = $this->db->fetchOne(
                        "SELECT gor_id_organization FROM gems__organizations WHERE gor_name = ?",
                        $organizationData['gor_name']
                        );
                if ($orgId) {
                    $import['organizationIds'][$oldId] = $orgId;
                    $import['formDefaults']['gtr_organizations'][] = $orgId;
                } else {
                    $import['organizationIds'][$oldId] = false;
                }
            }
        } else {
            $orgId = false;
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gor_name not specified for organization at line %d.'),
                    $lineNr
                    ));
        }
        $batch->setVariable('import', $import);
    }
}
