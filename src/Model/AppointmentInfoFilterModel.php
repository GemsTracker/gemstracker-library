<?php

namespace Gems\Model;

use Gems\Agenda\Repository\FilterRepository;
use Gems\Model\Dependency\AppointmentInfoTypeDependency;
use Gems\Repository\AppointmentInfoTypesRepository;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class AppointmentInfoFilterModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translator,
        protected readonly Translated $translatedUtil,
        protected readonly FilterRepository $filterRepository,
        protected readonly AppointmentInfoTypesRepository $appointmentInfoTypesRepository,
    )
    {
        parent::__construct('gems__appointment_info', $metaModelLoader, $sqlRunner, $translator);
        $metaModel = $this->getMetaModel();

        $metaModel->set('gai_name', [
            'label' => $this->_('Name'),
            'description' => $this->_('Name of this link'),
        ]);

        $metaModel->set('gai_id_filter', [
            'label' => $this->_('Filter'),
            'multiOptions' => $this->filterRepository->getAllFilterOptions(),
        ]);

        $typeOptions = $this->appointmentInfoTypesRepository->getInfoTypeOptions();

        $metaModel->set('gai_type', [
            'label' => $this->_('Type'),
            'multiOptions' => $this->appointmentInfoTypesRepository->getInfoTypeOptions(),
        ]);
        if (count($typeOptions) < 2) {
            $firstOption = key($typeOptions);
            $metaModel->set('gai_type', [
                'elementClass' => 'Exhibitor',
                'default' => $firstOption,
            ]);
        }

        $metaModel->set('gai_field_key', [
            'label' => $this->_('Key'),
            'description' => $this->_('key in the appointment info array'),
        ]);

        $metaModel->set('gai_field_value', [
            'label' => $this->_('Value'),
            'description' => $this->_('value in the appointment info array'),
        ]);

        $metaModel->set('gai_active', [
            'label' => $this->_('Active'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);

        $metaModel->addDependency(AppointmentInfoTypeDependency::class);

        $metaModelLoader->setChangeFields($metaModel, 'gai');
    }
}