<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Adds basic token editing snippet parameter processing and checking.
 *
 * This class supplies the model and adds some display knowledge.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Tracker_Snippets_EditTokenSnippetAbstract extends \Gems_Snippets_ModelFormSnippetAbstract
{
    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;


    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Optional: $request or $tokenData must be set
     *
     * The token shown
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
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->loader && $this->request && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = $this->token->getModel();

        if ($model instanceof \Gems_Tracker_Model_StandardTokenModel) {
            $model->addEditTracking();

            if ($this->createData) {
                $model->applyInsertionFormatting();
            }
        }

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
                return parent::getHtmlOutput($view);
            } else {
                $this->addMessage(sprintf($this->_('Token %s not found.'), $this->tokenId));
            }

        } else {
            $this->addMessage($this->_('No token specified.'));
        }
    }


    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
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
        return parent::hasHtmlOutput();
    }
}
