<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Survey;

use Gems\Snippets\ModelImportSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 17-apr-2014 17:12:39
 */
class AnswerImportSnippet extends ModelImportSnippet
{
    /**
     *
     * @var \Gems\Tracker\Survey
     */
    protected $_survey;

    /**
     *
     * @var \Gems\Import\ImportLoader
     */
    protected $importLoader;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Add the next button
     */
    protected function addNextButton()
    {
        parent::addNextButton();

        if (! (isset($this->formData['survey']) && $this->formData['survey'])) {
            $this->_nextButton->setAttrib('disabled', 'disabled');
        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addStep1(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $this->addItems($bridge, 'survey', 'trans', 'mode', 'track', 'skipUnknownPatients', 'tokenCompleted', 'noToken');
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     * /
    public function afterRegistry()
    {
        parent::afterRegistry();
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->importModel instanceof \MUtil\Model\ModelAbstract) {
            $surveyId = $this->request->getParam(\MUtil\Model::REQUEST_ID);

            if ($surveyId) {
                $this->formData['survey'] = $surveyId;
                $this->_survey            = $this->loader->getTracker()->getSurvey($surveyId);
                $surveys[$surveyId]       = $this->_survey->getName();
                $elementClass             = 'Exhibitor';
                $tracks                   = $this->util->getTranslated()->getEmptyDropdownArray() +
                        $this->util->getTrackData()->getTracksBySurvey($surveyId);
            } else {
                $empty        = $this->util->getTranslated()->getEmptyDropdownArray();
                $trackData    = $this->util->getTrackData();
                $surveys      = $empty + $trackData->getActiveSurveys();
                $tracks       = $empty + $trackData->getAllTracks();
                $elementClass = 'Select';
            }

            parent::createModel();

            $order = $this->importModel->getOrder('trans') - 5;

            $this->importModel->set('survey', 'label', $this->_('Survey'),
                    'elementClass', $elementClass,
                    'multiOptions', $surveys,
                    'onchange', 'this.form.submit();',
                    'order', $order,
                    'required', true);

            $this->importModel->set('track', 'label', $this->_('Track'),
                    'description', $this->_('Optionally assign answers only within a single track'),
                    'multiOptions', $tracks //,
                    // 'onchange', 'this.form.submit();'
                    );

            $this->importModel->set('skipUnknownPatients', 'label', $this->_('Skip unknowns'),
                    'default', 0,
                    'description', $this->_('What to do when the respondent does not exist'),
                    'elementClass', 'Checkbox',
                    'multiOptions', $this->util->getTranslated()->getYesNo()
                    );

            $tokenCompleted = array(
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_OVERWRITE =>
                    $this->_('Delete old token and create new'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_DOUBLE =>
                    $this->_('Create new extra set of answers'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR =>
                    $this->_('Abort the import'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_SKIP =>
                    $this->_('Skip the token'),
            );

            $this->importModel->set('tokenCompleted', 'label', $this->_('When token completed'),
                    'default', \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR,
                    'description', $this->_('What to do when an imported token has already been completed'),
                    'elementClass', 'Radio',
                    'multiOptions', $tokenCompleted
                    );

            $tokenTreatments = array(
                // \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_DOUBLE =>
                //     $this->_('Create new token'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR =>
                    $this->_('Abort the import'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_SKIP =>
                    $this->_('Skip the token'),
            );

            $this->importModel->set('noToken', 'label', $this->_('Token does not exist'),
                    'default', \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR,
                    'description', $this->_('What to do when no token exist to import to'),
                    'elementClass', 'Radio',
                    'multiOptions', $tokenTreatments
                    );

            $this->importModel->set('tokenCompleted', 'separator', '');
        }

        return $this->importModel;
    }

    /**
     * Creates from the model a \Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @param int $step The current step
     * @return \Zend_Form
     */
    protected function getFormFor($step)
    {
        $model    = $this->getModel();
        $baseform = $this->createForm();

        if ($baseform instanceof \MUtil\Form) {
            $table = new \MUtil\Html\TableElement();
            $table->setAsFormLayout($baseform, true, true);

            // There is only one row with formLayout, so all in output fields get class.
            $table['tbody'][0][0]->appendAttrib('class', $this->labelClass);
        }
        $baseform->setAttrib('class', $this->class);

        $bridge = $model->getBridgeFor('form', $baseform);

        $this->_items = null;
        $this->initItems();

        $this->addFormElementsFor($bridge, $model, $step);

        return $baseform;
    }

    /**
     * Try to get the current translator
     *
     * @return \MUtil\Model\ModelTranslatorInterface or false if none is current
     */
    protected function getImportTranslator()
    {
        $translator = parent::getImportTranslator();

        if ($translator instanceof \Gems\Model\Translator\AnswerTranslatorAbstract) {
            // Set answer specific options
            $surveyId = isset($this->formData['survey']) ? $this->formData['survey'] : null;
            $tokenCompleted = isset($this->formData['tokenCompleted']) ?
                    $this->formData['tokenCompleted'] :
                    \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR;
            $skipUnknownPatients = isset($this->formData['skipUnknownPatients']) &&
                    $this->formData['skipUnknownPatients'];
            $noToken = isset($this->formData['noToken']) ?
                    $this->formData['noToken'] :
                    \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR;

            $trackId = isset($this->formData['track']) ? $this->formData['track'] : null;

            $translator->setSurveyId($surveyId);
            $translator->setSkipUnknownPatients($skipUnknownPatients);
            $translator->setTokenCompleted($tokenCompleted);
            $translator->setNoToken($noToken);
            $translator->setTrackId($trackId);
        }

        return $translator;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        parent::loadFormData();

        $surveyId = $this->request->getParam(\MUtil\Model::REQUEST_ID);

        if (isset($this->formData['survey']) &&
                $this->formData['survey'] &&
                (! $this->_survey instanceof \Gems\Tracker\Survey)) {

            $this->_survey = $this->loader->getTracker()->getSurvey($this->formData['survey']);
        }

        if ($this->_survey instanceof \Gems\Tracker\Survey) {
            // Add (optional) survey specific translators
            $extraTrans  = $this->importLoader->getAnswerImporters($this->_survey);
            if ($extraTrans) {
                $this->importTranslators = $extraTrans + $this->importTranslators;

                $this->_translatorDescriptions = false;

                $this->importModel->set('trans', 'multiOptions', $this->getTranslatorDescriptions());
            }
        }

        if ($this->_survey instanceof \Gems\Tracker\Survey) {
            $this->targetModel = $this->_survey->getAnswerModel($this->locale->toString());
            $this->importer->setTargetModel($this->targetModel);

            $source = $this->menu->getParameterSource();
            $source->offsetSet('gsu_has_pdf', $this->_survey->hasPdf() ? 1 : 0);
            $source->offsetSet('gsu_active', $this->_survey->isActive() ? 1 : 0);
        }
        // \MUtil\EchoOut\EchoOut::track($this->formData);
    }
}
