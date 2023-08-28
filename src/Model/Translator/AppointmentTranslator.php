<?php

/**
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model\Translator;

use Gems\Agenda\Agenda;
use Gems\Db\ResultFetcher;
use Gems\Loader;
use Gems\Repository\OrganizationRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Model_Translator
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class AppointmentTranslator extends \Gems\Model\Translator\StraightTranslator
{
    /**
     * Datetime import formats
     *
     * @var array
     */
    public array $datetimeFormats = [
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s',
        'Y-m-d H:i:sP',
        'Y-m-d H:i:s',
        ];

    /**
     * @var string The import source identifier
     */
    protected $importSource = 'import';
    
    /**
     * The name of the field to store the organization id in
     *
     * @var string
     */
    protected string $orgIdField = 'gap_id_organization';

    public function __construct(
        TranslatorInterface $translator,
        OrganizationRepository $organizationRepository,
        ResultFetcher $resultFetcher,
        protected Agenda $agenda,
    )
    {
        parent::__construct($translator, $organizationRepository, $resultFetcher);
    }

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil\Model\ModelException
     */
    public function getFieldsTranslations(): array
    {
        $this->targetModel->setAlias('gas_name_attended_by', 'gap_id_attended_by');
        $this->targetModel->setAlias('gas_name_referred_by', 'gap_id_referred_by');
        $this->targetModel->setAlias('gaa_name', 'gap_id_activity');
        $this->targetModel->setAlias('gapr_name', 'gap_id_procedure');
        $this->targetModel->setAlias('glo_name', 'gap_id_location');

        return array(
            'gap_patient_nr'      => 'gr2o_patient_nr',
            'gap_organization_id' => 'gap_id_organization',
            'gap_id_in_source'    => 'gap_id_in_source',
            'gap_admission_time'  => 'gap_admission_time',
            'gap_discharge_time'  => 'gap_discharge_time',
            'gap_admission_code'  => 'gap_code',
            'gap_status_code'     => 'gap_status',
            'gap_attended_by'     => 'gas_name_attended_by',
            'gap_referred_by'     => 'gas_name_referred_by',
            'gap_activity'        => 'gaa_name',
            'gap_procedure'       => 'gapr_name',
            'gap_location'        => 'glo_name',
            'gap_subject'         => 'gap_subject',
            'gap_comment'         => 'gap_comment',

            // Autofill fields - without a source field but possibly set in this translator
            'gap_id_user',
            'gap_last_synch',
        );
    }

    public function startImport(): AppointmentTranslator
    {
        if ($this->targetModel instanceof \MUtil\Model\ModelAbstract) {
            // No multiOptions as a new items can be created during import
            $fields = array(
                'gap_id_attended_by', 'gap_id_referred_by', 'gap_id_activity',  'gap_id_procedure', 'gap_id_location',
            );
            foreach ($fields as $name) {
                $this->targetModel->del($name, 'multiOptions');
            }
        }

        return parent::startImport();
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key)
    {
        $row = parent::translateRowValues($row, $key);

        if (! $row) {
            return false;
        }

        // Set fixed values for import
        $row['gap_source']      = $this->importSource;
        $row['gap_manual_edit'] = 0;

        if (! isset($row['gap_id_user'])) {
            if (isset($row['gr2o_patient_nr'], $row[$this->orgIdField])) {

                $sql = 'SELECT gr2o_id_user
                        FROM gems__respondent2org
                        WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?';

                $id = $this->resultFetcher->fetchOne($sql, [$row['gr2o_patient_nr'], $row[$this->orgIdField]]);

                if ($id) {
                    $row['gap_id_user'] = $id;
                }
            }
            if (! isset($row['gap_id_user'])) {
                // No user no import if still not set
                return false;
            }
        }

        if (isset($row['gap_admission_time'], $row['gap_discharge_time']) &&
                ($row['gap_admission_time'] instanceof \DateTimeInterface) &&
                ($row['gap_discharge_time'] instanceof \DateTimeInterface)) {
            
            $diff = $row['gap_discharge_time']->diff($row['gap_admission_time']);
            if ($diff->days > 366) {
                if ($row['gap_discharge_time']->diff(new \DateTimeImmutable())->days > 366) {
                    // $row['gap_discharge_time'] = null;
                }
            }
        }

        $skip = false;
        if (isset($row['gas_name_attended_by'])) {
            $row['gap_id_attended_by'] = $this->agenda->matchHealthcareStaff(
                    $row['gas_name_attended_by'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_attended_by']);
        }
        if (!$skip && isset($row['gas_name_referred_by'])) {
            $row['gap_id_referred_by'] = $this->agenda->matchHealthcareStaff(
                    $row['gas_name_referred_by'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_referred_by']);
        }
        if (!$skip && isset($row['gaa_name'])) {
            $row['gap_id_activity'] = $this->agenda->matchActivity(
                    $row['gaa_name'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_activity']);
        }
        if (!$skip && isset($row['gapr_name'])) {
            $row['gap_id_procedure'] = $this->agenda->matchProcedure(
                    $row['gapr_name'],
                    $row[$this->orgIdField]
                    );
            $skip = $skip || (false === $row['gap_id_procedure']);
        }
        if (!$skip && isset($row['glo_name'])) {
            $location = $this->agenda->matchLocation(
                    $row['glo_name'],
                    $row[$this->orgIdField]
                    );
            $row['gap_id_location'] = is_null($location) ? null : $location['glo_id_location'];
            $skip = $skip || is_null($location) || $location['glo_filter'];
        }
        if ($skip) {
            return null;
        }
        
        // This value has a fixed meaning! 
        $row['gap_last_synch'] = new \DateTimeImmutable();
        // \MUtil\EchoOut\EchoOut::track($row);

        return $row;
    }
}
