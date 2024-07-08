<?php

namespace Gems\Tracker\Model\Dependency;

use Gems\Db\ResultFetcher;
use Gems\Repository\AppointmentInfoTypesRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Dependency\DependencyAbstract;

class ValuesAsReferenceDependency extends FromAppointmentsMaintenanceDependency
{
    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overridden in subclass
     *
     * @var array Of name => name
     */
    protected array $_dependentOn = ['gtf_id_track', 'gtf_field_type', 'gtf_field_values'];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overridden in subclass
     *
     * @var array of name => array(setting => setting)
     */
    protected array $_effecteds = [
        'gtf_field_values' => [
            'description', 'elementClass', 'formatFunction', 'label', 'minlength', 'size', 'rows', 'required',
        ],
        'htmlCalc' => ['elementClass', 'label'],
        'gtf_calculate_using' => ['description', 'elementClass', 'formatFunction', 'label', 'multiOptions'],
        'gtf_readonly' => ['default', 'disabled'],
    ];

    public function __construct(
        int $trackId,
        TranslatorInterface $translate,
        ResultFetcher $resultFetcher,
        protected readonly AppointmentInfoTypesRepository $appointmentInfoTypesRepository,
    )
    {
        parent::__construct($trackId, $translate, $resultFetcher);
    }

    public function getChanges(array $context, bool $new = false): array
    {
        return [
            'gtf_field_value_keys' => [
                'label'          => $this->_('Type'),
                'description'    => $this->_('Appointment info type'),
                'elementClass'   => 'Select',
                'required'       => true,
                'multiOptions'   => $this->appointmentInfoTypesRepository->getInfoTypeOptions(),
            ],
            'gtf_field_values' => [
                'label'          => $this->_('Info key'),
                'description'    => $this->_('Appointment info field key'),
                'elementClass'   => 'Text',
                'size'           => 30,
                'required'       => true,
            ],
            'htmlCalc' => [
                'label'        => ' ',
                'elementClass' => 'Exhibitor',
            ],
            'gtf_calculate_using' => [
                'label'          => $this->_('Calculate from'),
                'description'    => $this->_('Automatically calculate this field using other fields'),
                'elementClass'   => 'MultiCheckbox',
                'formatFunction' => [$this, 'formatValues'],
                'multiOptions'   => $this->getOptions($context['gtf_id_track']),
            ],
            'gtf_readonly' => [
                'value' => 1,
                'disabled' => false,
            ],
        ];
    }
}