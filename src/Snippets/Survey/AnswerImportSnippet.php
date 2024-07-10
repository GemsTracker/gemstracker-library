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

use Gems\Locale\Locale;
use Gems\Snippets\ModelImportSnippet;
use Gems\Tracker;
use Gems\Util;
use Gems\Util\Translated;
use Mezzio\Session\SessionInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Translator\ModelTranslatorInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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
     * @var \Gems\Menu
     */
    protected $menu;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MetaModelLoader $metaModelLoader,
        SessionInterface $session,
        protected readonly Locale $locale,
        protected readonly Tracker $tracker,
        protected readonly Translated $translatedUtil,
        protected readonly Util $util,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $metaModelLoader, $session);
    }

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
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\FullDataInterface $model
     */
    protected function addStep1(FormBridgeInterface $bridge, FullDataInterface $model)
    {
        $this->addItems($bridge, ['survey', 'trans', 'mode', 'track', 'skipUnknownPatients', 'tokenCompleted', 'noToken']);
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
     * @return FullDataInterface
     */
    protected function createModel(): FullDataInterface
    {
        if (! $this->importModel instanceof FullDataInterface) {
            $surveyId = null;
            $queryParams = $this->requestInfo->getRequestQueryParams();
            if (isset($queryParams[MetaModelInterface::REQUEST_ID])) {
                $surveyId = $queryParams[MetaModelInterface::REQUEST_ID];
            }

            if ($surveyId) {
                $this->formData['survey'] = $surveyId;
                $this->_survey            = $this->tracker->getSurvey($surveyId);
                $surveys[$surveyId]       = $this->_survey->getName();
                $elementClass             = 'Exhibitor';
                $tracks                   = $this->translatedUtil->getEmptyDropdownArray() +
                        $this->util->getTrackData()->getTracksBySurvey($surveyId);
            } else {
                $empty        = $this->translatedUtil->getEmptyDropdownArray();
                $trackData    = $this->util->getTrackData();
                $surveys      = $empty + $trackData->getActiveSurveys();
                $tracks       = $empty + $trackData->getAllTracks();
                $elementClass = 'Select';
            }

            parent::createModel();

            $order = $this->importModel->getOrder('trans') - 5;

            $this->importModel->set('survey', [
                'label' => $this->_('Survey'),
                'autoSubmit' => true,
                'elementClass' => $elementClass,
                'multiOptions' => $surveys,
                'order' => $order,
                'required' => true,
                ]);

            $this->importModel->set('track', [
                'label' => $this->_('Track'),
                'autoSubmit' => true,
                'description' => $this->_('Optionally assign answers only within a single track'),
                'multiOptions' => $tracks,
                ]);

            $this->importModel->set('skipUnknownPatients', 'label', $this->_('Skip unknowns'),
                    'default', 0,
                    'description', $this->_('What to do when the respondent does not exist'),
                    'elementClass', 'Checkbox',
                    'multiOptions', $this->translatedUtil->getYesNo()
                    );

            $tokenCompleted = [
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_OVERWRITE =>
                    $this->_('Delete old token and create new'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_DOUBLE =>
                    $this->_('Create new extra set of answers'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR =>
                    $this->_('Abort the import'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_SKIP =>
                    $this->_('Skip the token'),
            ];

            $this->importModel->set('tokenCompleted', 'label', $this->_('When token completed'),
                    'default', \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR,
                    'description', $this->_('What to do when an imported token has already been completed'),
                    'elementClass', 'Radio',
                    'multiOptions', $tokenCompleted
                    );

            $tokenTreatments = [
                // \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_DOUBLE =>
                //     $this->_('Create new token'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_ERROR =>
                    $this->_('Abort the import'),
                \Gems\Model\Translator\AnswerTranslatorAbstract::TOKEN_SKIP =>
                    $this->_('Skip the token'),
            ];

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

        /**
         * @var FormBridgeInterface $bridge
         */
        $bridge = $model->getBridgeFor('form', $baseform);

        $this->_items = null;
        $this->initItems($model->getMetaModel());

        $this->addFormElementsFor($bridge, $model, $step);

        return $baseform;
    }

    /**
     * @inheritdoc
     */
    protected function getCurrentImportTranslator(): ?ModelTranslatorInterface
    {
        $translator = parent::getCurrentImportTranslator();

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
    protected function loadFormData(): array
    {
        parent::loadFormData();

        $surveyId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID);

        if (isset($this->formData['survey']) &&
                $this->formData['survey'] &&
                (! $this->_survey instanceof \Gems\Tracker\Survey)) {

            $this->_survey = $this->tracker->getSurvey($this->formData['survey']);
        }

        if ($this->_survey instanceof \Gems\Tracker\Survey) {
            // Add (optional) survey specific translators
            $extraTrans  = $this->importLoader->getAnswerImporters($this->_survey);
            if ($extraTrans) {
                // $this->importTranslators = $extraTrans + $this->importTranslators;

                $this->_translatorDescriptions = false;

                $this->importModel->getMetaModel()->set('trans', 'multiOptions', $this->getTranslatorDescriptions());
            }
        }

        if ($this->_survey instanceof \Gems\Tracker\Survey) {
            $this->targetModel = $this->_survey->getAnswerModel($this->locale->getCurrentLanguage());
            $this->importer->setTargetModel($this->targetModel);

            $source = $this->menu->getParameterSource();
            $source->offsetSet('gsu_has_pdf', $this->_survey->hasPdf() ? 1 : 0);
            $source->offsetSet('gsu_active', $this->_survey->isActive() ? 1 : 0);
        }
        // \MUtil\EchoOut\EchoOut::track($this->formData);
        return $this->formData;
    }
}
