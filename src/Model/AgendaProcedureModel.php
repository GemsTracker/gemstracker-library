<?php

declare(strict_types=1);

namespace Gems\Model;

use Gems\Util;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;

class AgendaProcedureModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
        protected readonly Util $util,
    ) {
        parent::__construct('gems__agenda_procedures', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gapr');

        $this->addColumn("CASE WHEN gapr_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        $this->applySettings();
    }

    public function applySettings(): void
    {
        $this->metaModel->set('gapr_name', [
            'label' => $this->_('Procedure'),
            'description' => $this->_('A procedure describes an appointments effects on a respondent: e.g. an excercise, an explanantion, a massage, mindfullness, a (specific) operation, etc...'),
            'required' => true
        ]);

        $this->metaModel->setIfExists('gapr_id_organization', [
            'label' => $this->_('Organization'),
            'description' => $this->_('Optional, an import match with an organization has priority over those without.'),
            'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $this->util->getDbLookup()->getOrganizations()
        ]);

        $this->metaModel->setIfExists('gapr_name_for_resp', [
            'label' => $this->_('Respondent explanation'),
            'description' => $this->_('Alternative description to use with respondents.')
        ]);
        $this->metaModel->setIfExists('gapr_match_to', [
            'label' => $this->_('Import matches'),
            'description' => $this->_("Split multiple import matches using '|'.")
        ]);

        $this->metaModel->setIfExists('gapr_code', [
            'label' => $this->_('Procedure code'),
            'size' => 10,
            'description' => $this->_('Optional code name to link the procedure to program code.')
        ]);

        $this->metaModel->setIfExists('gapr_active', [
            'label' => $this->_('Active'),
            'description' => $this->_('Inactive means assignable only through automatich processes.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo(),
            ActivatingYesNoType::$activatingValue => 1,
            ActivatingYesNoType::$deactivatingValue => 0
        ]);
        $this->metaModel->setIfExists('gapr_filter', [
            'label' => $this->_('Filter'),
            'description' => $this->_('When checked appointments with these procedures are not imported.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);
    }
}