<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed\DeferredUserLoader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed\DeferredUserLoader;

use Gems\User\Embed\DeferredUserLoaderAbstract;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\DeferredUserLoader
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 02-Apr-2020 19:33:03
 */
class StaffUser extends DeferredUserLoaderAbstract
{
    /**
     *
     * @param \Gems_User_User $embeddedUser
     * @param \Gems_User_User $user
     */
    protected function checkCurrentOrganization(\Gems_User_User $embeddedUser, \Gems_User_User $user)
    {
        if ($user->getCurrentOrganizationId() !== $embeddedUser->getCurrentOrganizationId()) {
            $user->setCurrentOrganization($embeddedUser->getCurrentOrganizationId());
        }
    }

    /**
     * Get the deferred user
     *
     * @param \Gems_User_User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return \Gems_User_user|null
     */
    public function getDeferredUser(\Gems_User_User $embeddedUser, $deferredLogin)
    {
        $user = $this->getUser($deferredLogin, [
            $embeddedUser->getBaseOrganizationId(),
            $embeddedUser->getCurrentOrganizationId(),
            ]);

        if ($user->isActive()) {
            $this->checkCurrentOrganization($embeddedUser, $user);

            return $user;
        }
        if (! $embeddedUser->canCreateUser()) {
            return null;
        }

        $model = $this->loader->getModels()->getStaffModel();
        $data  = $model->loadNew();

        $data['gsf_login']            = $deferredLogin;
        $data['gsf_id_organization']  = $embeddedUser->getBaseOrganizationId();
        $data['gsf_id_primary_group'] = $embeddedUser->getGroupId();
        $data['gsf_last_name']        = ucfirst($deferredLogin);
        $data['gsf_iso_lang']         = $embeddedUser->getLocale();
        $data['gul_user_class']       = 'StaffUser';
        $data['gul_can_login']        = 1;
        // \MUtil_Echo::track($data);

        $model->save($data);

        $user = $this->loader->getUser($deferredLogin, $embeddedUser->getBaseOrganizationId());

        if ($user->isActive()) {
            $this->checkCurrentOrganization($embeddedUser, $user);

            return $user;
        }
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    public function getLabel()
    {
        return $this->_('Load staff user in same group and organisation as system user');
    }
}