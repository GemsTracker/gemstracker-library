<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: GroupDeleteSnippet.php 0002 2015-04-30 16:33:05Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 24-sep-2014 18:26:00
 */
class Gems_Snippets_Group_GroupDeleteSnippet extends \Gems_Snippets_ModelItemYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
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
        $model = $this->getModel();
        $data  = $model->loadFirst();
        $roles = $this->currentUser->getAllowedRoles();

        //\MUtil_Echo::track($data);

        // Perform access check here, before anything has happened!!!
        if (isset($data['ggp_role']) && (! isset($roles[$data['ggp_role']]))) {
            $this->addMessage($this->_('You do not have sufficient privilege to edit this group.'));
            $this->afterSaveRouteUrl = array($this->request->getActionKey() => 'show');
            $this->resetRoute        = false;

            return false;
        }
        $this->menu->getParameterSource()->offsetSet('ggp_role', $data['ggp_role']);

        return parent::hasHtmlOutput();
    }
}
