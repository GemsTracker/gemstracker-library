<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

/**
 * Generic extension for displaying tokens
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class ShowTokenSnippetAbstract extends \MUtil\Snippets\ModelVerticalTableSnippetAbstract
{
    /**
     * @var \Gems\Util\BasePath
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
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     * Required
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Gems\Menu
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
     * @var \Gems\Tracker\Token
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
     * @return \MUtil\Model\ModelAbstract
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
        $model->applyParameters(['gto_id_token' => $this->tokenId]);

        return $model;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($this->tokenId) {
            if ($this->token->exists) {
                $htmlDiv   = \MUtil\Html::div();

                $htmlDiv->h3($this->getTitle());

                $basePath = $this->basepath->getBasePath();
                $view->headScript()->appendFile($basePath . '/gems/js/gems.copyToClipboard.js');
                // Always add the script, it will only be used if the answer button class
                // is set to inline-answers (in the menu or elsewhere)
                $view->headScript()->appendFile($basePath . '/gems/js/gems.respondentAnswersModal.js');


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
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if (! $this->tokenId) {
            if ($this->token) {
                $this->tokenId = $this->token->getTokenId();
            } elseif ($this->request) {
                $this->tokenId = $this->request->getParam(\MUtil\Model::REQUEST_ID);
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
     * @param string $value \Gems token value
     * @return \Gems\Form
     */
    public static function makeFakeForm($value)
    {
        $form = new \Gems\Form();
        $form->removeDecorator('HtmlTag');

        $element = new \Zend_Form_Element_Text('gto_id_token');
        $element->class = 'token_copy copy-to-clipboard-after';
        $element->setDecorators(array('ViewHelper', array('HtmlTag', 'Div')));

        $form->addElement($element);
        $form->isValid(array('gto_id_token' => \MUtil\Lazy::call('strtoupper', $value)));

        return $form;
    }
}
