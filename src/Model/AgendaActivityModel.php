<?php

namespace Gems\Model;

use Gems\Repository\OrganizationRepository;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;

class AgendaActivityModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
        protected readonly OrganizationRepository $organizationRepository,
    )
    {
        parent::__construct('gems__agenda_activities', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gaa');

        $this->applySettings();
    }

    public function applySettings(): void
    {
        $this->metaModel->set('gaa_id_activity', [
            'label' => $this->_('ID'),
            'apiName' => 'id',
            'elementClass' => 'Hidden',
        ]);

        $this->metaModel->set('gaa_name', [
            'label' => $this->_('Name'),
            'description' => $this->_('An activity is a high level description about an appointment:
e.g. consult, check-up, diet, operation, physiotherapy or other.'),
            'apiName' => 'name',
            'minlength' => 3,
        ]);

        $this->metaModel->set('gaa_id_organization', [
            'label' => $this->_('Organization'),
            'apiName' => 'organization',
            'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $this->organizationRepository->getOrganizations()
        ]);

        $this->metaModel->set('gaa_name_for_resp',   [
            'label' => $this->_('Respondent explanation'),
            'description' => $this->_('Alternative description to use with respondents.'),
        ]);

        $this->metaModel->set('gaa_match_to', [
            'label' => $this->_('Import matches'),
            'description' => $this->_("Split multiple import matches using '|'."),
        ]);

        $this->metaModel->set('gaa_code', [
            'label' => $this->_('Activity code'),
            'size' => 10,
            'description' => $this->_('Optional code name to link the activity to program code.')
        ]);

        $this->metaModel->set('gaa_active', [
            'label' => $this->_('Active'),
            'apiName' => 'active',
            'type' => new ActivatingYesNoType($this->translatedUtil->getYesNo(), 'row_class'),
        ]);

        $this->metaModel->set('gaa_filter', [
            'label' => $this->_('Filter'),
            'description' => $this->_('When checked appointments with these activities are not imported.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo(),
        ]);
    }
}