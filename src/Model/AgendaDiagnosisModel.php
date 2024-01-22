<?php

declare(strict_types=1);

namespace Gems\Model;

use Gems\Agenda\Agenda;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;

class AgendaDiagnosisModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
        protected Agenda $agenda,
    ) {
        parent::__construct('gems__agenda_diagnoses', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gad');

        $this->applySettings();
    }

    private function applySettings(): void
    {
        $this->metaModel->set('gad_diagnosis_code', [
            'label' => $this->_('Diagnosis code'),
            'description' => $this->_('A code as defined by the coding system'),
            'required' => true
        ]);
        $this->metaModel->set('gad_description', [
            'label' => $this->_('Activity'),
            'description' => $this->_('Description of the diagnosis'),
            'required' => true
        ]);

        $this->metaModel->setIfExists('gad_coding_method', [
            'label' => $this->_('Coding system'),
            'description' => $this->_('The coding system used.'),
            'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $this->agenda->getDiagnosisCodingSystems()
        ]);

        $this->metaModel->setIfExists('gad_code', [
            'label' => $this->_('Diagnosis code'),
            'size' => 10,
            'description' => $this->_('Optional code name to link the diagnosis to program code.')
        ]);

        $this->metaModel->setIfExists('gad_active', [
            'label' => $this->_('Active'),
            'description' => $this->_('Inactive means assignable only through automatich processes.'),
            'type' => new ActivatingYesNoType($this->translatedUtil->getYesNo(), 'row_class'),
        ]);
        $this->metaModel->setIfExists('gad_filter', [
            'label' => $this->_('Filter'),
            'description' => $this->_('When checked appointments with these diagnoses are not imported.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);
    }
}