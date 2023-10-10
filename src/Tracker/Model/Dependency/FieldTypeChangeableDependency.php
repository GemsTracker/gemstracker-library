<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Dependency;

use Gems\Db\ResultFetcher;
use Gems\Tracker\Model\FieldMaintenanceModel;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Dependency\DependencyAbstract;

/**
 * Class that checks whether changing the field type is allowed.
 *
 * @subpackage Tracker_Model
 * @subpackage FieldTypeChangeDependency
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 18-mrt-2015 13:07:12
 */
class FieldTypeChangeableDependency extends DependencyAbstract
{
    protected array $_dependentOn = ['gtf_id_field'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected array $_effecteds = ['gtf_field_type' => ['elementClass', 'autosubmit']];

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @param string $dependsOn the model field to depend on
     */
    /*public function __construct($fieldName)
    {
        $this->_dependentOn[] = $fieldName;
        $this->fieldName = $fieldName;

        parent::__construct();
    }*/
    public function __construct(
        protected readonly string $fieldName,
        TranslatorInterface $translate,
        protected readonly ResultFetcher $resultFetcher,
    )
    {
        $this->_dependentOn[] = $fieldName;
        parent::__construct($translate);
    }

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $subChange = true;

        if (! $new) {
            if (isset($context[$this->fieldName], $context['gtf_id_field'])) {
                $sql = $this->getSql($context[$this->fieldName]);
                $fid = $context['gtf_id_field'];

                if ($sql && $fid) {
                    $subChange = ! $this->resultFetcher->fetchOne($sql, [$fid]);
                }
            }
        }

        if ($subChange) {
            return [
                'gtf_field_type' => [
                'elementClass' => 'Select',
                'autoSubmit'   => true,
                ]
            ];
        }

        return [];
    }

    /**
     * Adapt/extend this function if you need different queries
     * for other types
     *
     * @param string $subId The current subtype of field
     * @return bool|string An sql statement or false
     */
    protected function getSql(string $subId): string|null
    {
        if ($subId == FieldMaintenanceModel::FIELDS_NAME) {
            return "SELECT gr2t2f_id_field
                FROM gems__respondent2track2field
                WHERE gr2t2f_id_field = ?";
        }

        if ($subId == FieldMaintenanceModel::APPOINTMENTS_NAME) {
            return "SELECT gr2t2a_id_app_field
                FROM gems__respondent2track2appointment
                WHERE gr2t2a_id_app_field = ?";
        }

        return null;
    }
}
