<?php

declare(strict_types=1);

namespace Gems\Model;

use Gems\Util;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;

class AgendaStaffModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
        protected readonly Util $util,
    ) {
        parent::__construct('gems__agenda_staff', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gas');

        $this->addColumn("CASE WHEN gas_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        $this->applySettings();
    }

    private function applySettings(): void
    {
        $dblookup = $this->util->getDbLookup();

        $this->metaModel->set('gas_name', [
            'label' => $this->_('Name'),
            'required' => true
        ]);
        $this->metaModel->set('gas_function', ['label' => $this->_('Function')]);


        $this->metaModel->setIfExists('gas_id_organization', [
            'label' => $this->_('Organization'),
            'multiOptions' => $dblookup->getOrganizations(),
            'required' => true
        ]);

        $this->metaModel->setIfExists('gas_id_user', [
            'label' => $this->_('GemsTracker user'),
            'description' => $this->_('Optional: link this health care provider to a GemsTracker Staff user.'),
            'multiOptions' => $this->translatedUtil->getEmptyDropdownArray() + $dblookup->getStaff()
        ]);
        $this->metaModel->setIfExists('gas_match_to', [
            'label' => $this->_('Import matches'),
            'description' => $this->_("Split multiple import matches using '|'.")
        ]);

        $this->metaModel->setIfExists('gas_active', [
            'label' => $this->_('Active'),
            'description' => $this->_('Inactive means assignable only through automatich processes.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo(),
            ActivatingYesNoType::$activatingValue => 1,
            ActivatingYesNoType::$deactivatingValue => 0
        ]);
        $this->metaModel->setIfExists('gas_filter', [
            'label' => $this->_('Filter'),
            'description' => $this->_('When checked appointments with this staff member are not imported.'),
            'elementClass' => 'Checkbox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);
    }
}