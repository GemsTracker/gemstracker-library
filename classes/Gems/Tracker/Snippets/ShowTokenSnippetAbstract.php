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
 * Generic extension for displaying tokens
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Tracker_Snippets_ShowTokenSnippetAbstract extends \MUtil_Snippets_ModelVerticalTableSnippetAbstract
{
    /**
     * @var \Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer table table-bordered table-condensed compliance';

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Required
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
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The display data of the token shown
     *
     * @var \Gems_Tracker_Token
     */
    protected $token;

    /**
     * Optional: id of the selected token to show
     *
     * Can be derived from $request or $token
     *
     * @var string
     */
    protected $tokenId;

    /**
     * Show the token in an mini form for cut & paste.
     *
     * But only when the token is not answered.
     *
     * @var boolean
     */
    protected $useFakeForm = true;

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader && $this->menu && $this->request;
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->token->getModel();

        $model->applyFormatting();
        if ($this->useFakeForm && $this->token->hasSuccesCode() && (! $this->token->isCompleted())) {
            $model->set('gto_id_token', 'formatFunction', array(__CLASS__, 'makeFakeForm'));
        } else {
            $model->set('gto_id_token', 'formatFunction', 'strtoupper');
        }
        $model->setBridgeFor('itemTable', 'ThreeColumnTableBridge');

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
        if ($this->tokenId) {
            if ($this->token->exists) {
                $htmlDiv   = \MUtil_Html::div();

                $htmlDiv->h3($this->getTitle());

                // Always add the script, it will only be used if the answer button class
                // is set to inline-answers (in the menu or elsewhere)
                $view->headScript()->appendFile($this->basepath->getBasePath() . '/gems/js/gems.respondentAnswersModal.js');
                
                $table = parent::getHtmlOutput($view);
                $this->applyHtmlAttributes($table);
                $htmlDiv[] = $table;

                return $htmlDiv;

            } else {
                $this->addMessage(sprintf($this->_('Token %s not found.'), $this->tokenId));
            }

        } else {
            $this->addMessage($this->_('No token specified.'));
        }
    }

    /**
     *
     * @return string The header title to display
     */
    abstract protected function getTitle();

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
            if ($this->token) {
                $this->tokenId = $this->token->getTokenId();
            } elseif ($this->request) {
                $this->tokenId = $this->request->getParam(\MUtil_Model::REQUEST_ID);
            }
        }

        if ($this->tokenId && (! $this->token)) {
            $this->token = $this->loader->getTracker()->getToken($this->tokenId);
        }

        // Output always true, returns an error message as html when anything is wrong
        return true;
    }

    /**
     * Creates a fake form so that it is (slightly) easier to
     * copy and paste a token.
     *
     * @param string $value Gems token value
     * @return \Gems_Form
     */
    public static function makeFakeForm($value)
    {
        $form = new \Gems_Form();
        $form->removeDecorator('HtmlTag');

        $element = new \Zend_Form_Element_Text('gto_id_token');
        $element->class = 'token_copy';
        $element->setDecorators(array('ViewHelper', array('HtmlTag', 'Div')));

        $form->addElement($element);
        $form->isValid(array('gto_id_token' => \MUtil_Lazy::call('strtoupper', $value)));

        return $form;
    }
}
