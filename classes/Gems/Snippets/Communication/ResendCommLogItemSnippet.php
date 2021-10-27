<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Communication
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Communication;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Communication
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ResendCommLogItemSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * When hasHtmlOutput() is false a snippet code user should check
     * for a redirectRoute. Otherwise the redirect calling render() will
     * execute the redirect.
     *
     * This function should never return a value when the snippet does
     * not redirect.
     *
     * Also when hasHtmlOutput() is true this function should not be
     * called.
     *
     * @see \Zend_Controller_Action_Helper_Redirector
     *
     * @return mixed Nothing or either an array or a string that is acceptable for Redector->gotoRoute()
     */
    public function getRedirectRoute()
    {
        return [
            $this->request->getControllerKey() => $this->request->getControllerName(),
            $this->request->getActionKey() => 'show',
            \MUtil_Model::REQUEST_ID => $this->request->getParam(\MUtil_Model::REQUEST_ID),
        ];
        
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
        $logId   = $this->request->getParam(\MUtil_Model::REQUEST_ID);
        if (! $logId) {
            $this->addMessage($this->_('Cannot find mail log item!'));
            return false;
        }

        $logItem = $this->loader->getModels()->getCommLogModel(true)->loadFirst(['grco_id_action' => $logId]);
        if (! $logItem) {
            $this->addMessage($this->_('Cannot find mail log item!'));
            return false;
        }
        if (! isset($logItem['grco_id_job'])) {
            $this->addMessage($this->_('Mail sent by job id not set!'));
            return false;
        }
        if (! isset($logItem['grco_id_token'])) {
            $this->addMessage($this->_('Mail not sent for a token!'));
            return false;
        }

        $jobsUtil   = $this->util->getCommJobsUtil();
        $job        = $jobsUtil->getJob($logItem['grco_id_job']);
        $tokenModel = $this->loader->getTracker()->getTokenModel();
        $tokenData  = $tokenModel->loadFirst(['gto_id_token' => $logItem['grco_id_token']]);

        $messenger = $jobsUtil->getJobMessenger($job);
        $result    = $messenger->sendCommunication($job, $tokenData, false);

        if ($result) {
            $this->addMessage($result);
        } else {
            $this->addMessage($this->_('Mail resent successful!'));
        }
        return false;
    }
}