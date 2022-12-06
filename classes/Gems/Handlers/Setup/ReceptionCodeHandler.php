<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Setup;

use Gems\Actions\ProjectSettings;
use Gems\Html;
use Gems\Model;
use Gems\Util;
use Gems\Util\Translated;
use MUtil\Model\ModelAbstract;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 * Controller for maintaining reception codes.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class ReceptionCodeHandler extends \Gems\Handlers\ModelSnippetLegacyHandlerAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $autofilterParameters = [
        'extraSort' => [
            'grc_id_reception_code' => SORT_ASC,
        ],
    ];

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public array $cacheTags = ['receptionCode', 'receptionCodes'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected Model $modelLoader,
        protected Translated $translatedUtil,
        protected Util $util,
    ) {
        parent::__construct($responder, $translate);
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action): ModelAbstract
    {
        $rcLib = $this->util->getReceptionCodeLibrary();
        $yesNo  = $this->translatedUtil->getYesNo();

        $model  = new \MUtil\Model\TableModel('gems__reception_codes');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('grc_id_reception_code', 'label', $this->_('Code'), 'size', '10');
        $model->set('grc_description',       'label', $this->_('Description'), 'size', '30', 'translate', true);

        $model->set('grc_success',           'label', $this->_('Is success code'),
            'multiOptions', $yesNo ,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code is a success code.'));
        $model->set('grc_active',            'label', $this->_('Active'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('Only active codes can be selected.'));
        if ($detailed) {
            $model->set('desc1', 'elementClass', 'Html',
                    'label', ' ',
                    'value', Html::create('h4', $this->_('Can be assigned to'))
                    );
        }
        $model->set('grc_for_respondents',   'label', $this->_('Respondents'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a respondent.'));
        $model->set('grc_for_tracks',        'label', $this->_('Tracks'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a track.'));
        $model->set('grc_for_surveys',       'label', $this->_('Tokens'),
            'multiOptions', $rcLib->getSurveyApplicationValues(),
            'description', $this->_('This reception code can be assigned to a token.'));
        if ($detailed) {
            $model->set('desc2', 'elementClass', 'Html',
                    'label', ' ',
                     'value', Html::create('h4', $this->_('Additional actions'))
                    );
        }
        $model->set('grc_redo_survey',       'label', $this->_('Redo survey'),
            'multiOptions', $rcLib->getRedoValues(),
            'description', $this->_('Redo a survey on this reception code.'));
        $model->set('grc_overwrite_answers', 'label', $this->_('Overwrite existing consents'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('Remove the consent from already answered surveys.'));

        if ($detailed) {
            $model->set('grc_id_reception_code', 'validator', $model->createUniqueValidator('grc_id_reception_code'));
            $model->set('grc_description',       'validator', $model->createUniqueValidator('grc_description'));
        }

        if ('create' == $action || 'edit' == $action) {
            $this->modelLoader->addDatabaseTranslationEditFields($model);
        } else {
            $this->modelLoader->addDatabaseTranslations($model);
        }

        \Gems\Model::setChangeFieldsByPrefix($model, 'grc');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle(): string
    {
        return $this->_('Reception codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('reception code', 'reception codes', $count);
    }
}
