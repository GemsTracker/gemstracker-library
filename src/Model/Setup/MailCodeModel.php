<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Setup;

use Gems\Model\MetaModelLoader;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @since      Class available since version 1.0
 */
class MailCodeModel extends \Gems\Model\SqlTableModel implements \Zalt\SnippetsActions\ApplyActionInterface
{
    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        protected readonly Translated $translatedUtil,
    )
    {
        parent::__construct('gems__mail_codes', $metaModelLoader, $sqlRunner, $translate);

        $metaModelLoader->setChangeFields($this->metaModel, 'gmc');
        $this->metaModel->setKeys([MetaModelInterface::REQUEST_ID => 'gmc_id']);

        $this->applySettings();
    }

    public function applyAction(SnippetActionInterface $action): void
    {
        $this->metaModelLoader->addDatabaseTranslations($this->metaModel, $action->isDetailed());
    }

    public function applySettings()
    {
        $yesNo  = $this->translatedUtil->getYesNo();

        $this->metaModel->set('gmc_id', [
            'label' => $this->_('Value'),
            'description' => $this->_('The higher the number, the likelier to mail'),
            'size'  => '3',
            'validators[digits]'  => 'Digits',
            'validators[unique]' => ModelUniqueValidator::class,
            ]);

        $this->metaModel->set('gmc_mail_to_target', [
            'label' => $this->_('Respondent description'),
            'description' => $this->_('Description at the respondent / track level.'),
            'required' => true,
            'size' => '20',
            'translate' => true,
            'validators[unique]' => ModelUniqueValidator::class,
            ]);

        $this->metaModel->set('gmc_mail_cause_target', [
            'label' => $this->_('Survey description'),
            'description' => $this->_('Description at the survey level.'),
            'required' => true,
            'size' => '20',
            'translate' => true,
            'validators[unique]' => ModelUniqueValidator::class,
            ]);

        $this->metaModel->set('gmc_code', [
            'label' => $this->_('Mail code code-field'),
            'description' => $this->_('Optional code name to link the survey to program code.'),
            'size' => 10,
            ]);

        $this->metaModel->set('gmc_for_surveys', [
            'label' => $this->_('Surveys'),
            'elementClass' => 'CheckBox',
            'description' => $this->_('This mail code can be assigned to a survey.'),
            'multiOptions' => $yesNo,
            ]);

        $this->metaModel->set('gmc_for_respondents', [
            'label' => $this->_('Respondents'),
            'elementClass' => 'CheckBox',
            'description' => $this->_('This mail code can be assigned to a respondent.'),
            'multiOptions' => $yesNo,
            ]);

        $this->metaModel->set('gmc_for_tracks', [
            'label' => $this->_('Tracks'),
            'elementClass' => 'CheckBox',
            'description' => $this->_('This mail code can be assigned to a track.'),
            'multiOptions' => $yesNo,
            ]);
    }
}