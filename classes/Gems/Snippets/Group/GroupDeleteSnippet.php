<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Group;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 24-sep-2014 18:26:00
 */
class GroupDeleteSnippet extends \Gems\Snippets\ModelItemYesNoDeleteSnippetAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
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
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        $model = $this->getModel();
        $data  = $model->loadFirst();
        $roles = $this->currentUser->getAllowedRoles();

        //\MUtil\EchoOut\EchoOut::track($data);

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
