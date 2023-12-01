<?php

declare(strict_types=1);

namespace Gems\Model\TrackBuilder;

use Gems\Encryption\ValueEncryptor;
use Gems\Model\MetaModelLoader;
use Gems\Model\SqlTableModel;
use Gems\Model\Type\EncryptedField;
use Gems\Tracker;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

class SourceModel extends SqlTableModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected Tracker $tracker,
        protected ValueEncryptor $valueEncryptor,
        protected Translated $translatedUtil,
    ) {
        parent::__construct('gems__sources', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gso');

        $this->applySettings();
    }

    private function applySettings(): void
    {
        $this->metaModel->set('gso_source_name', [
            'label' => $this->_('Name'),
            'description' => $this->_('E.g. the name of the project - for single source projects.'),
            'size' => 15,
            'minlength' => 4,
            'validators[unique]' => ModelUniqueValidator::class
        ]);
        $this->metaModel->set('gso_ls_url', [
            'label' => $this->_('Source Url'),
            'default' => 'http://',
            'description' => $this->_('For creating token-survey url.'),
            'size' => 50,
            'validators[unique]' => ModelUniqueValidator::class,
            'validators[url]' => 'Uri'
        ]);

        $sourceClasses = $this->tracker->getSourceClasses();
        end($sourceClasses);
        $this->metaModel->set('gso_ls_class', [
            'label' => $this->_('Adaptor class'),
            'default' => key($sourceClasses),
            'multiOptions' => $sourceClasses
        ]);

        $sourceDatabaseClasses = $this->tracker->getSourceDatabaseClasses();

        $this->metaModel->set('gso_ls_adapter', [
            'label' => $this->_('Database Server'),
            'default' => reset($sourceDatabaseClasses),
            'description' => $this->_('The database server used by the source.'),
            'multiOptions' => $sourceDatabaseClasses
        ]);
        $this->metaModel->set('gso_ls_table_prefix', [
            'label' => $this->_('Table prefix'),
            'default' => 'ls__',
            'description' => $this->_('Do not forget the underscores.'),
            'size' => 15,
            'order' => 50
        ]);

        $this->metaModel->set('gso_status', [
            'label' => $this->_('Status'),
            'default' => 'Not checked',
            'elementClass' => 'Exhibitor',
        ]);
        $this->metaModel->set('gso_last_synch', [
            'label' => $this->_('Last synchronisation'),
            'elementClass' => 'Exhibitor'
        ]);
    }

    public function applyDetailSettings(string $action): void
    {
        $instructionLabel = $this->_('Leave empty for the Gems database settings.');

        $this->metaModel->set('gso_ls_dbhost', [
            'label' => $this->_('Database host'),
            'description' => $instructionLabel,
            'size' => 15
        ]);
        $this->metaModel->set('gso_ls_dbport', [
            'label' => $this->_('Database port'),
            'description' => $instructionLabel . ' ' . $this->_('Usually port 3306'),
            'size' => 6,
            'validators[int]' => 'Digits',
            'validators[between]' => ['Between', true, [0, 65535]],
            'order' => 60
        ]);
        $this->metaModel->set('gso_ls_database', [
            'label' => $this->_('Database'),
            'description' => $instructionLabel,
            'size' => 15
        ]);
        $this->metaModel->set('gso_ls_username', [
            'label' => $this->_('Database Username'),
            'description' => $instructionLabel,
            'size' => 15
        ]);

        $this->metaModel->set('gso_ls_password', [
            'label' => $this->_('Database Password'),
            'elementClass' => 'Password',
            'renderPassword' => true,
            'required' => false,
            'size' => 15,
        ]);

        if ($action === 'create') {
            $this->metaModel->set('gso_ls_password', [
                'repeatLabel' => $this->_('Repeat password'),
                'description' => $instructionLabel,
            ]);
        } elseif ($action === 'edit') {
            $this->metaModel->set('gso_ls_password', [
                'repeatLabel' => $this->_('Repeat password'),
                'description' => $this->_('Enter new or remove stars to empty'),
            ]);
        }

        $type = new EncryptedField($this->valueEncryptor, true);
        $type->apply($this->metaModel, 'gso_ls_password');

        $this->metaModel->set('gso_ls_charset', [
            'label' => $this->_('Charset'),
            'description' => $instructionLabel,
            'size' => 15
        ]);
        $this->metaModel->set('gso_active', [
            'label' => $this->_('Active'),
            'default' => 0,
            'multiOptions' => $this->translatedUtil->getYesNo(),
        ]);
    }
}