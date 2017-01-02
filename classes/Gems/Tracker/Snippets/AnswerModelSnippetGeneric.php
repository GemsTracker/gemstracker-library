<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Displays answers to a survey.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Snippets_AnswerModelSnippetGeneric extends \Gems_Snippets_ModelTableSnippetAbstract
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array('grc_success' => SORT_DESC, 'gto_round_order' => SORT_ASC, 'gto_valid_from' => SORT_ASC);

    /**
     * Empty or a \Gems_Tracker_Snippets_AnswerNameFilterInterface object that is
     * used to filter the answers that are displayed.
     *
     * @var \Gems_Tracker_Snippets_AnswerNameFilterInterface
     */
    protected $answerFilter;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table answer';

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var string Format used for displaying dates.
     */
    protected $dateFormat = \Zend_Date::DATE_MEDIUM;

    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     * Switch to put the display of the cancel and print buttons.
     *
     * @var boolean
     */
    protected $showButtons = true;

    /**
     * Switch to enable/disable the 'take' button underneath each
     * open token.
     *
     * @var boolean
     */
    protected $showTakeButton = true;

    /**
     * Switch to put the display of the headers on or off
     *
     * @var boolean
     */
    protected $showHeaders = true;

    /**
     * Switch to put the display of the current token as select to true or false.
     *
     * @var boolean
     */
    protected $showSelected = true;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * Required: id of the selected token to show
     *
     * @var string
     */
    protected $tokenId;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $br = \MUtil_Html::create('br');
        if ($this->showSelected) {
            $selectedClass = \MUtil_Lazy::iff(\MUtil_Lazy::comp($bridge->gto_id_token, '==', $this->tokenId), 'selectedColumn', null);
        } else {
            $selectedClass = null;
        }

        $bridge->th($this->_('Status'));
        $td = $bridge->tdh(\MUtil_Lazy::first($bridge->grc_description, $this->_('OK')));
        $td->appendAttrib('class', $selectedClass);

        $bridge->th($this->_('Question'));
        if ($model->has('grr_name') && $model->has('gtf_field_name')) {
            $td = $bridge->tdh(
                    \MUtil_Lazy::iif($bridge->grr_name, array($bridge->grr_name, $br)),
                    \MUtil_Lazy::iif($bridge->gtf_field_name, array($bridge->gtf_field_name, $br)),
                    $bridge->gto_round_description,
                    \MUtil_Lazy::iif($bridge->gto_round_description, $br),
                    \MUtil_Lazy::iif($bridge->gto_completion_time, $bridge->gto_completion_time, $bridge->gto_valid_from)
                    );
        } else {
            $td = $bridge->tdh(
                    $bridge->gto_round_description,
                    \MUtil_Lazy::iif($bridge->gto_round_description, $br),
                    \MUtil_Lazy::iif($bridge->gto_completion_time, $bridge->gto_completion_time, $bridge->gto_valid_from)
                    );
        }
        $td->appendAttrib('class', $selectedClass);
        $td->appendAttrib('class', $bridge->row_class);

        // Apply filter on the answers displayed
        $answerNames = $model->getItemsOrdered();
        if ($this->answerFilter instanceof \Gems_Tracker_Snippets_AnswerNameFilterInterface) {
            $answerNames = $this->answerFilter->filterAnswers($bridge, $model, $answerNames);
        }

        foreach($answerNames as $name) {
            $label = $model->get($name, 'label');
            if (null !== $label) {     // Was strlen($label), but this ruled out empty sub-questions
                $bridge->thd($label, array('class' => $model->get($name, 'thClass')));
                $td = $bridge->td($bridge->$name);
                $td->appendAttrib('class', 'answer');
                $td->appendAttrib('class', $selectedClass);
                $td->appendAttrib('class', $bridge->row_class);
            }
        }

        $bridge->th($this->_('Token'));

        $tokenUpper = $bridge->gto_id_token->strtoupper();
        if ($this->showTakeButton && $menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $source = new \Gems_Menu_ParameterSource();
            $source->setTokenId($bridge->gto_id_token);
            $source->offsetSet('can_be_taken', $bridge->can_be_taken);

            $link = $menuItem->toActionLink($source);

            if ($link) {
                $link->title = array($this->_('Token'), $tokenUpper);
            }

            $td = $bridge->tdh($bridge->can_be_taken->if($link, $tokenUpper));
        } else {
            $td = $bridge->tdh($tokenUpper);
        }
        $td->appendAttrib('class', $selectedClass);
        $td->appendAttrib('class', $bridge->row_class);
    }

    /**
     * Add the buttons to the result div
     *
     * @param \MUtil_Html_HtmlElement $html
     */
    protected function addButtons(\MUtil_Html_HtmlElement $html)
    {
        $buttonDiv = $html->buttonDiv();
        $buttonDiv->actionLink(array(), $this->_('Close'), array('onclick' => 'window.close();'));
        $buttonDiv->actionLink(array(), $this->_('Print'), array('onclick' => 'window.print();'));
    }

    /**
     * Add elements that form the header
     *
     * @param \MUtil_Html_HtmlElement $htmlDiv
     */
    public function addHeaderInfo(\MUtil_Html_HtmlElement $htmlDiv)
    {
        $htmlDiv->h3(sprintf($this->_('%s answers for patient number %s'), $this->token->getSurveyName(), $this->token->getPatientNumber()));

        if (! $this->currentUser->isFieldMaskedWhole('name')) {
            $htmlDiv->pInfo(sprintf(
                    $this->_('Answers for token %s, patient number %s: %s.'),
                    strtoupper($this->tokenId),
                    $this->token->getPatientNumber(),
                    $this->token->getRespondentName()))
                    ->appendAttrib('class', 'noprint');
        }
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

        // If loaded inline by Ajax request, disable the buttons
        if ($this->request->isXmlHttpRequest()) {
            $this->showButtons = false;
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->token->getSurveyAnswerModel($this->locale->getLanguage());

        $model->set('gto_valid_from', 'dateFormat', $this->dateFormat);
        $model->set('gto_completion_time', 'dateFormat', $this->dateFormat);

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $htmlDiv = \MUtil_Html::create()->div(array('class' => 'answer-container'));

        if ($this->tokenId) {
            if ($this->token->exists) {
                if ($this->showHeaders) {
                    $this->addHeaderInfo($htmlDiv);
                }

                $table = parent::getHtmlOutput($view);
                $table->setPivot(true, 2, 1);

                $this->applyHtmlAttributes($table);
                $this->class = false;
                $htmlDiv[] = $table;

            } else {
                $htmlDiv->ul(sprintf($this->_('Token %s not found.'), $this->tokenId), array('class' => 'errors'));
            }

        } else {
            $htmlDiv->ul($this->_('No token specified.'), array('class' => 'errors'));
        }

        if ($this->showButtons) {
            $this->addButtons($htmlDiv);
        }
        return $htmlDiv;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->tokenId) {
            if (isset($this->token)) {
                $this->tokenId = $this->token->getTokenId();
            }
        } elseif (! $this->token) {
            $this->token = $this->loader->getTracker()->getToken($this->tokenId);
        }

        // Output always true, returns an error message as html when anything is wrong
        return true;
    }
}
