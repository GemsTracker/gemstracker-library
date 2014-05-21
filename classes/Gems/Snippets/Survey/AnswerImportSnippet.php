<?php

/**
 * Copyright (c) 2014, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AnswerImportSnippet.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3 17-apr-2014 17:12:39
 */
class Gems_Snippets_Survey_AnswerImportSnippet extends MUtil_Snippets_Standard_ModelImportSnippet
{
    /**
     *
     * @var Gems_Tracker_Survey
     */
    protected $_survey;

    /**
     *
     * @var Gems_Import_ImportLoader
     */
    protected $importLoader;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var Gems_Util
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
     * @param MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addStep1(MUtil_Model_Bridge_FormBridgeInterface $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->addItems($bridge, 'survey', 'trans', 'mode', 'track', 'tokenCompleted', 'noToken');
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
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->importModel instanceof MUtil_Model_ModelAbstract) {
            $surveyId = $this->request->getParam(MUtil_Model::REQUEST_ID);

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

            $tokenCompleted = array(
                Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_OVERWRITE =>
                    $this->_('Delete old token and create new'),
                Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_DOUBLE =>
                    $this->_('Create new extra set of answers'),
                Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR =>
                    $this->_('Abort the import'),
            );

            $this->importModel->set('tokenCompleted', 'label', $this->_('When token completed'),
                    'default', Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR,
                    'description', $this->_('What to do when an imported token is already answered'),
                    'elementClass', 'Radio',
                    'multiOptions', $tokenCompleted
                    );

            $tokenTreatments = array(
                Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_DOUBLE =>
                    $this->_('Create new token'),
                Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR =>
                    $this->_('Abort the import'),
            );

            $this->importModel->set('noToken', 'label', $this->_('Token does not exist'),
                    'default', Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR,
                    'description', $this->_('What to do when no token exist to import to'),
                    'elementClass', 'Hidden',  // 'Radio',
                    'multiOptions', $tokenTreatments
                    );

            $this->importModel->set('trans', 'separator', '<br/>');
        }

        return $this->importModel;
    }

    /**
     * Try to get the current translator
     *
     * @return MUtil_Model_ModelTranslatorInterface or false if none is current
     */
    protected function getImportTranslator()
    {
        $translator = parent::getImportTranslator();

        if ($translator instanceof Gems_Model_Translator_AnswerTranslatorAbstract) {
            // Set answer specific options
            $surveyId = isset($this->formData['survey']) ? $this->formData['survey'] : null;
            $tokenCompleted = isset($this->formData['tokenCompleted']) ?
                    $this->formData['tokenCompleted'] :
                    Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR;
            $noToken = isset($this->formData['noToken']) ?
                    $this->formData['noToken'] :
                    Gems_Model_Translator_AnswerTranslatorAbstract::TOKEN_ERROR;

            $trackId = isset($this->formData['track']) ? $this->formData['track'] : null;

            $translator->setSurveyId($surveyId);
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

        $surveyId = $this->request->getParam(MUtil_Model::REQUEST_ID);

        if (isset($this->formData['survey']) &&
                $this->formData['survey'] &&
                (! $this->_survey instanceof Gems_Tracker_Survey)) {

            $this->_survey = $this->loader->getTracker()->getSurvey($this->formData['survey']);
        }

        if ($this->_survey instanceof Gems_Tracker_Survey) {
            // Add (optional) survey specific translators
            $extraTrans  = $this->importLoader->getAnswerImporters($this->_survey);
            if ($extraTrans) {
                $this->importTranslators = $extraTrans + $this->importTranslators;

                $this->_translatorDescriptions = false;

                $this->importModel->set('trans', 'multiOptions', $this->getTranslatorDescriptions());
            }
        }

        if ($this->_survey instanceof Gems_Tracker_Survey) {
            $this->targetModel = $this->_survey->getAnswerModel($this->locale->toString());
            $this->importer->setTargetModel($this->targetModel);

            $source = $this->menu->getParameterSource();
            $source->offsetSet('gsu_has_pdf', $this->_survey->hasPdf() ? 1 : 0);
            $source->offsetSet('gsu_active', $this->_survey->isActive() ? 1 : 0);
        }
        // MUtil_Echo::track($this->formData);
    }
}
