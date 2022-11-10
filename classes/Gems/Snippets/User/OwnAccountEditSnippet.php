<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\Cache\HelperAdapter;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 15:15:07
 */
class OwnAccountEditSnippet extends \Gems\Snippets\ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $model;

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        if ($changed) {
            $this->accesslog->logChange($this->request, null, $this->formData);

            // Reload the current user data
            $user       = $this->currentUser;
            $currentOrg = $user->getCurrentOrganizationId();

            $this->loader->getUserLoader()->unsetCurrentUser();
            $user = $this->loader->getUser($user->getLoginName(), $user->getBaseOrganizationId())->setAsCurrentUser();
            $user->setCurrentOrganization($currentOrg);

            // In case locale has changed, set it in a cookie
            \Gems\Cookies::setLocale($this->formData['gsf_iso_lang'], $this->basepath);

            $this->addMessage($this->_('Saved your setup data', $this->formData['gsf_iso_lang']));
        } else {
            $this->addMessage($this->_('No changes to save!'));
        }

        if ($this->cacheTags && ($this->cache instanceof HelperAdapter)) {
            $this->cache->invalidateTags((array)[$this->cacheTags]);
        }
    }

    /**
     * After validation we clean the form data to remove all
     * entries that do not have elements in the form (and
     * this filters the data as well).
     */
    public function cleanFormData()
    {
        parent::cleanFormData();

        // You can only save data for the current user
        $this->formData['gsf_id_user'] = $this->currentUser->getUserId();
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->model instanceof \Gems\Model\StaffModel) {
            $this->model = $this->loader->getModels()->getStaffModel(false);
            $this->model->applyOwnAccountEdit();
        }

        return $this->model;
    }

    /**
     * The message to display when the change is not allowed
     *
     * @return string
     */
    protected function getNotAllowedMessage()
    {
        return $this->_('System account can not be changed.');
    }

    /**
     * If the current user is the system user, present a message and don't allow to edit
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if ($this->currentUser->getUserId() == \Gems\User\UserLoader::SYSTEM_USER_ID) {
            $this->addMessage($this->getNotAllowedMessage());
            return false;
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil\Snippets\ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        // Default is just go to the index
        if ($this->routeAction) {
            $this->afterSaveRouteUrl[$this->request->getActionKey()] = $this->routeAction;
        }
        $this->afterSaveRouteUrl['controller'] = $this->request->getControllerName();
    }
}
