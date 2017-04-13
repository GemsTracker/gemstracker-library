<?php

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Handles export of all tracks/surveys for a respondent
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_Export_RespondentExport extends \MUtil_Translate_TranslateableAbstract
{
    /**
     * Group answers
     *
     * @var boolean
     */
    protected $_group;

    /**
     * @var \Gems_Pdf
     */
    protected $_pdf;

    /**
     *
     * @var string
     */
    protected $_reportFooter      = 'Export_ReportFooterSnippet';

    /**
     *
     * @var string
     */
    protected $_reportHeader      = 'Export_ReportHeaderSnippet';

    /**
     *
     * @var string
     */
    protected $_respondentSnippet = 'Export_RespondentSnippet';

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \GemsEscort
     */
    protected $escort;

    /**
     *
     * @var \MUtil_Html_Sequence
     */
    protected $html;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Holds the optional token filter.
     *
     * When set, a token needs to have all elements in the tokenFilter in order to have _isTokenInFilter
     * return true. The tokenFilter is an array containing an array with one or more of the following elements:
     * <pre>
     *  code            The track code
     *  surveyid        The survey ID
     *  tokenid         the token ID
     * </pre>
     *
     * @var array
     */
    public $tokenFilter = array();

    /**
     * Holds the optional track filter.
     *
     * When set, a track needs to have all elements in the trackFilter in order to have _isTrackInFilter return true.
     * The trackFilter is an array containing an array with one or more of the following elements:
     * <pre>
     *  code            The track code
     *  trackid         The track ID
     *  resptrackid     The respondent-track ID
     *  respid          The respondent ID
     * </pre>
     *
     * @var array
     */
    public $trackFilter = array();

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Returns true when this token should be displayed
     *
     * @param \Gems_Tracker_Token $token
     * @return boolean
     */
    public function _displayToken($token)
    {
        if ($token->isCompleted()) {
            return true;
        }

        return false;
    }

    /**
     * Determines if this particular token should be included
     * in the report
     *
     * @param  \Gems_Tracker_Token $token
     * @return boolean This dummy implementation always returns true
     */
    protected function _isTokenInFilter(\Gems_Tracker_Token $token)
    {
        $result = false;

        // Only if token has a success code
        if ($token->getReceptionCode()->isSuccess()) {
            $result = true;
        }

        if ($result) {
            $tokenInfo = array(
                'code'     => $token->getSurvey()->getCode(),
                'surveyid' => $token->getSurveyId(),
                'tokenid'  => $token->getTokenId()
            );

            // Now check if the tokenfilter is true
            if (empty($this->tokenFilter)) {
                $result = true;
            } else {
                $result = false;
                // Now read the filter and split by track code or track id
                foreach ($this->tokenFilter as $filter)
                {
                    $remaining = array_diff_assoc($filter, $tokenInfo);
                    if (empty($remaining)) {
                        $result = true;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Determines if this particular track should be included
     * in the report
     *
     * @param  \Gems_Tracker_RespondentTrack $track
     * @return boolean This dummy implementation always returns true
     */
    protected function _isTrackInFilter(\Gems_Tracker_RespondentTrack $track)
    {
        $result    = false;
        $trackInfo = array(
            'code'        => $track->getCode(),
            'trackid'     => $track->getTrackId(),
            'resptrackid' => $track->getRespondentTrackId(),
            'respid'      => $track->getRespondentId(),
        );

        if (empty($this->trackFilter)) {
            $result = true;
        } else {
            // Now read the filter and split by track code or track id
            foreach($this->trackFilter as $filter) {
                $remaining = array_diff_assoc($filter, $trackInfo);
                if (empty($remaining)) {
                    $result = true;
                    break;
                }
            }
        }

        // Only if track has a success code
        if ($result && $track->getReceptionCode()->isSuccess()) {
            return true;
        }

        return false;
    }

    /**
     * Exports all the tokens of a single track, grouped by round
     *
     * @param \Gems_Tracker_RespondentTrack $track
     */
    protected function _exportTrackTokens(\Gems_Tracker_RespondentTrack $track)
    {
        $groupSurveys = $this->_group;
        $token        = $track->getFirstToken();
        $engine       = $track->getTrackEngine();
        $surveys      = array();

        $table = $this->html->table(array('class' => 'browser table'));
        $table->th($this->_('Survey'))
              ->th($this->_('Round'))
              ->th($this->_('Token'))
              ->th($this->_('Status'));
        $this->html->br();

        while ($token) {
            //Should this token be in the list?
            if (!$this->_isTokenInFilter($token)) {
                $token = $token->getNextToken();
                continue;
            }

            $table->tr()->td($token->getSurveyName())
                        ->td(($engine->getTrackType() == 'T' ? $token->getRoundDescription() : $this->_('Single Survey')))
                        ->td(strtoupper($token->getTokenId()))
                        ->td($token->getStatus());

            //Should we display the answers?
            if (!$this->_displayToken($token)) {
                $token = $token->getNextToken();
                continue;
            }

            $showToken = false;
            if (! $groupSurveys) {
                // For single survey tracks or when $groupSurvey === false we show all tokens
                $showToken = true;
            } else {
                // For multi survey tracks and $groupSurveys === true, we show only the first token
                // as the snippet takes care of showing the other tokens
                if (!isset($surveys[$token->getSurveyId()])) {
                    $showToken = true;
                    $surveys[$token->getSurveyId()] = 1;
                }
            }
            
            if ($showToken) {
                $params = array(
                    'token'          => $token,
                    'tokenId'        => $token->getTokenId(),
                    'showHeaders'    => false,
                    'showButtons'    => false,
                    'showSelected'   => false,
                    'showTakeButton' => false,
                    'grouped'        => $groupSurveys);

                $snippets = $token->getAnswerSnippetNames();

                if (!is_array($snippets)) {
                    $snippets = array($snippets);
                }

                list($snippets, $snippetParams) = \MUtil_Ra::keySplit($snippets);
                $params = $params + $snippetParams;

                $this->html->snippet('Export_SurveyHeaderSnippet', 'token', $token);

                foreach($snippets as $snippet) {
                    $this->html->snippet($snippet, $params);
                }

                $this->html->br();
            }

            $token = $token->getNextToken();
        }
    }

    /**
     * Exports a single track
     *
     * @param \Gems_Tracker_RespondentTrack $respTrack
     */
    protected function _exportTrack(\Gems_Tracker_RespondentTrack $respTrack)
    {
        if (!$this->_isTrackInFilter($respTrack)) {
            return;
        }

        $trackModel = $this->loader->getTracker()->getRespondentTrackModel();
        $trackModel->applyDetailSettings($respTrack->getTrackEngine(), false);
        $trackModel->resetOrder();
        $trackModel->set('gtr_track_name',    'label', $this->_('Track'));
        $trackModel->set('gr2t_track_info',   'label', $this->_('Description'),
            'description', $this->_('Enter the particulars concerning the assignment to this respondent.'));
        $trackModel->set('assigned_by',       'label', $this->_('Assigned by'));
        $trackModel->set('gr2t_start_date',   'label', $this->_('Start'),
            'formatFunction', $this->util->getTranslated()->formatDate,
            'default', \MUtil_Date::format(new \Zend_Date(), 'dd-MM-yyyy'));
        $trackModel->set('gr2t_reception_code');
        $trackModel->set('gr2t_comment',       'label', $this->_('Comment'));
        $trackModel->setFilter(array('gr2t_id_respondent_track' => $respTrack->getRespondentTrackId()));
        $trackData = $trackModel->loadFirst();

        $this->html->h3($this->_('Track') . ' ' . $trackData['gtr_track_name']);

        $bridge = $trackModel->getBridgeFor('itemTable', array('class' => 'browser table'));
        $bridge->setRepeater(\MUtil_Lazy::repeat(array($trackData)));
        $bridge->th($this->_('Track information'), array('colspan' => 2));
        $bridge->setColumnCount(1);
        foreach($trackModel->getItemsOrdered() as $name) {
            if ($label = $trackModel->get($name, 'label')) {
                $bridge->addItem($name, $label);
            }
        }

        $tableContainer = \MUtil_Html::create()->div(array('class' => 'table-container'));
        $tableContainer[] = $bridge->getTable();
        $this->html[] = $tableContainer;
        $this->html->br();

        $this->_exportTrackTokens($respTrack);

        $this->html->hr();
    }

    /**
     * Exports a single respondent
     *
     * @param string $respondentId
     */
    protected function _exportRespondent($respondentId)
    {
        $respondentModel = $this->loader->getModels()->getRespondentModel(false);

        //Insert orgId when set
        if (is_array($respondentId) && isset($respondentId['gr2o_id_organization'])) {
            $filter['gr2o_id_organization'] = $respondentId['gr2o_id_organization'];
            $respondentId = $respondentId['gr2o_patient_nr'];
        } else {
            // Or accept to find in current organization
            // $filter['gr2o_id_organization'] = $this->currentUser->getCurrentOrganizationId();
            // Or use any allowed organization?
            $filter['gr2o_id_organization'] = array_keys($this->currentUser->getAllowedOrganizations());
        }
        $filter['gr2o_patient_nr'] = $respondentId;

        $respondentModel->setFilter($filter);
        $respondentData = $respondentModel->loadFirst();

        $this->html->snippet($this->_respondentSnippet,
            'model', $respondentModel,
            'data', $respondentData,
            'respondentId', $respondentId);

        $tracker = $this->loader->getTracker();
        $tracks = $tracker->getRespondentTracks($respondentData['gr2o_id_user'], $respondentData['gr2o_id_organization']);

        foreach ($tracks as $trackId => $track) {
            $this->_exportTrack($track);
        }
    }

    /**
     *
     * @param int $respTrackId
     * @return \Gems_Export_RespondentExport
     */
    public function addRespondentTrackFilter($respTrackId)
    {
        $this->trackFilter[]['resptrackid'] = $respTrackId;
        return $this;
    }

    /**
     *
     * @param string $tokenId
     * @return \Gems_Export_RespondentExport
     */
    public function addTokenFilter($tokenId)
    {
        $this->tokenFilter[]['tokenid'] = $tokenId;
        return $this;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->_pdf    = $this->loader->getPdf();
        $this->escort  = \GemsEscort::getInstance();
        $this->html    = new \MUtil_Html_Sequence();
        $this->request = \Zend_Controller_Front::getInstance()->getRequest();

        // Do not know why, but for some reason menu is not loaded automatically.
        $this->menu   = $this->loader->getMenu();
    }

    /**
     * Constructs the form
     *
     * @param boolean $hideGroup When true group checkbox is hidden
     * @return \Gems_Form_TableForm
     */
    public function getForm($hideGroup = false)
    {
        $form = new \Gems_Form();
        $form->setAttrib('target', '_blank');

        if ($hideGroup) {
            $element = new \Zend_Form_Element_Hidden('group');
        } else {
            $element = new \Zend_Form_Element_Checkbox('group');
            $element->setLabel($this->_('Group surveys'));
        }
        $element->setValue(1);
        $form->addElement($element);

        $element = new \Zend_Form_Element_Select('format');
        $element->setLabel($this->_('Output format'));
        $outputFormats = array('html' => 'HTML');
        if ($this->_pdf->hasPdfExport()) {
            $outputFormats['pdf'] = 'PDF';
            $element->setValue('pdf');
        }
        $element->setMultiOptions($outputFormats);
        $form->addElement($element);

        $element = new \Zend_Form_Element_Submit('export');
        $element->setLabel($this->_('Export'))
                ->setAttrib('class', 'button');
        $form->addElement($element);

        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());
        $links->addCurrentParent($this->_('Cancel'));
        if (count($links)) {
            $element = new \MUtil_Form_Element_Html('menuLinks');
            $element->setValue($links);
            // $element->setOrder(999);
            $form->addElement($element);
        }

        return $form;
    }

    /**
     * Renders the entire report (including layout)
     *
     * @param array|string[] $respondentId
     * @param boolean $group Group same surveys or not
     * @param string $format html|pdf, the output format to use
     */
    public function render($respondents, $group = true, $format = 'html')
    {
        $this->_group = $group;

        $this->html->snippet($this->_reportHeader);

        $respondentCount = count($respondents);
        $respondentIdx   = 0;
        foreach ($respondents as $respondentId) {
            $respondentIdx++;
            $this->_exportRespondent($respondentId);

            if ($respondentIdx < $respondentCount) {
                // Add some whitespace between patients
                $this->html->div('', array('style' => 'height: 100px'));
            }
        }

        $this->html->snippet($this->_reportFooter,  'respondents', $respondents);

        $this->menu->setVisible(false);
        if ($this->escort instanceof \Gems_Project_Layout_MultiLayoutInterface) {
            $this->escort->layoutSwitch();
        }
        $this->escort->postDispatch($this->request);

        \Zend_Controller_Action_HelperBroker::getExistingHelper('layout')->disableLayout();
        \Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer')->setNoRender(true);

        $this->view->layout()->content = $this->html->render($this->view);

        $content = $this->view->layout->render();

        if ($format == 'pdf') {
            if (is_array($respondentId) && isset($respondentId['gr2o_id_organization'])) {
                $respondentId = $respondentId['gr2o_patient_nr'];
            }
            $filename = 'respondent-export-' . strtolower($respondentId) . '.pdf';
            $content = $this->_pdf->convertFromHtml($content);
            $this->_pdf->echoPdfContent($content, $filename, true);
        } else {
            echo $content;
        }

        $this->menu->setVisible(true);
    }

}