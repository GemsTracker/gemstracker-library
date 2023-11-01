<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Setup;

use Gems\Handlers\Setup\ReceptionCodeHandler;
use Gems\Html;
use Gems\Model\MetaModelLoader;
use Gems\Util\ReceptionCodeLibrary;
use Gems\Util\Translated;
use Laminas\Validator\Regex;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\Model\Type\ActivatingYesNoType;
use Zalt\SnippetsActions\ApplyActionInterface;
use Zalt\SnippetsActions\SnippetActionInterface;
use Zalt\Validator\IsNot;
use Zalt\Validator\Model\ModelUniqueValidator;

/**
 * @package    Gems
 * @subpackage Model\Setup
 * @since      Class available since version 1.0
 */
class ReceptionCodeModel extends \Gems\Model\SqlTableModel implements ApplyActionInterface
{
    public function __construct(
        MetaModelLoader $metaModelLoader, 
        SqlRunnerInterface $sqlRunner, 
        TranslatorInterface $translate,
        protected readonly ReceptionCodeLibrary $receptionCodeLibrary,
        protected readonly Translated $translateUtil,
    )
    {
        parent::__construct('gems__reception_codes', $metaModelLoader, $sqlRunner, $translate);
        
        $metaModelLoader->setChangeFields($this->metaModel, 'grc');
        
        $this->applySettings();
    }

    public function applyAction(SnippetActionInterface $action): void
    {
        if ($action->isDetailed()) {
            $this->metaModel->set('desc1', [
                'elementClass' => 'Html',
                'label' => ' ',
                'value' => Html::create('h4', $this->_('Can be assigned to')),
                ]);
            $this->metaModel->set('desc2', [
                'elementClass' => 'Html',
                'label' => ' ',
                'value' => Html::create('h4', $this->_('Additional actions')),
                ]);
        }
//        if ($action->isEditing()) {
//            $this->modelLoader->addDatabaseTranslationEditFields($this->metaModel);
//        } else {
//            $this->modelLoader->addDatabaseTranslations($this->metaModel);
//        }
    }

    public function applySettings()
    {
        $yesNo = $this->translateUtil->getYesNo();

        $regex = new Regex('/^' . ReceptionCodeHandler::$parameters['id'] . '$/');
        $regex->setMessage($this->_('Only letters, numbers, underscores (_) and dashes (-) are allowed.'), Regex::NOT_MATCH);

        $this->metaModel->set('grc_id_reception_code', [
            'label'             => $this->_('Code'),
            'minlength'         => 2,
            'size'              => '10',
            'validators[notin]' => new IsNot(['export', 'create'], $this->_("The code '%value%' is not allowed.")),
            'validators[regex]' => $regex,
            'validators[uniq]'  => ModelUniqueValidator::class,
            ]);
        $this->metaModel->set('grc_description', [      
            'label' => $this->_('Description'), 
            'size' => '30', 
            'translate' => true,
            'validators[uniq]' => ModelUniqueValidator::class,
            ]);

        $this->metaModel->set('grc_success', [          
            'label' => $this->_('Is success code'),
            'multiOptions' => $yesNo ,
            'elementClass' => 'CheckBox',
            'description' => $this->_('This reception code is a success code.'),
            ]);
        $this->metaModel->set('grc_active', [           
            'label' => $this->_('Active'),
            'description' => $this->_('Only active codes can be selected.'),
            'type' => new ActivatingYesNoType($yesNo, 'row_class'),
            ]);
        $this->metaModel->set('grc_for_respondents', [
            'label' => $this->_('Respondents'),
            'multiOptions' => $yesNo,
            'elementClass' => 'CheckBox',
            'description' => $this->_('This reception code can be assigned to a respondent.'),
            ]);
        $this->metaModel->set('grc_for_tracks', [
            'label' => $this->_('Tracks'),
            'multiOptions' => $yesNo,
            'elementClass' => 'CheckBox',
            'description' => $this->_('This reception code can be assigned to a track.'),
            ]);
        $this->metaModel->set('grc_for_surveys', [
            'label' => $this->_('Tokens'),
            'multiOptions' => $this->receptionCodeLibrary->getSurveyApplicationValues(),
            'description' => $this->_('This reception code can be assigned to a token.'),
            ]);
        $this->metaModel->set('grc_redo_survey', [
            'label' => $this->_('Redo survey'),
            'multiOptions' => $this->receptionCodeLibrary->getRedoValues(),
            'description' => $this->_('Redo a survey on this reception code.'),
            ]);
        $this->metaModel->set('grc_overwrite_answers', [
            'label' => $this->_('Overwrite existing consents'),
            'multiOptions' => $yesNo,
            'elementClass' => 'CheckBox',
            'description' => $this->_('Remove the consent from already answered surveys.'),
            ]);
    }
}