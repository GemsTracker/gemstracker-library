<?php

declare(strict_types=1);

namespace Gems\Model;

use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class LogMaintenanceModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
    ) {
        parent::__construct('gems__log_setup', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gls');

        $this->applySettings();
    }

    public function applySettings(): void
    {
        $this->metaModel->set('gls_name', [
            'label' => $this->_('Action'),
            'elementClass' => 'Exhibitor',
        ]);

        $this->metaModel->set('gls_when_no_user', [
            'label' => $this->_('Log when no user'),
            'description' => $this->_('Always log this action, even when no one is logged in.'),
            'elementClass' => 'CheckBox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);

        $this->metaModel->set('gls_on_action', [
            'label' => $this->_('Log view'),
            'description' => $this->_('Always log when viewed / opened.'),
            'elementClass' => 'CheckBox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);

        $this->metaModel->set('gls_on_post', [
            'label' => $this->_('Log change tries'),
            'description' => $this->_('Log when trying to change the data.'),
            'elementClass' => 'CheckBox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);

        $this->metaModel->set('gls_on_change', [
            'label' => $this->_('Log data change'),
            'description' => $this->_('Log when data changes.'),
            'elementClass' => 'CheckBox',
            'multiOptions' => $this->translatedUtil->getYesNo()
        ]);
    }
}