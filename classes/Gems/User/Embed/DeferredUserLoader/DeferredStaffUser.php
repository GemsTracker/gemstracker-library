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
use Gems\User\Embed\EmbeddedUserData;

/**
 *
 * @package    Gems
 * @subpackage User\Embed\DeferredUserLoader
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 02-Apr-2020 19:33:03
 */
class DeferredStaffUser extends DeferredUserLoaderAbstract
{
    /**
     * Get the deferred user
     *
     * @param \Gems\User\User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return \Gems_User_user|null
     */
    public function getDeferredUser(\Gems\User\User $embeddedUser, $deferredLogin)
    {
        $embeddedUserData = $embeddedUser->getEmbedderData();
        if (! ($embeddedUserData instanceof EmbeddedUserData && $embeddedUser->isActive())) {
            return null;
        }

        $user = $this->getUser($deferredLogin, [
            $embeddedUser->getBaseOrganizationId(),
            $embeddedUser->getCurrentOrganizationId(),
            ]);

        if ($user->isActive()) {
            $this->checkCurrentSettings($embeddedUser, $embeddedUserData, $user);

            return $user;
        }

        if (! $embeddedUserData->canCreateUser()) {
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
        // \MUtil\EchoOut\EchoOut::track($data);

        $model->save($data);

        $user = $this->loader->getUser($deferredLogin, $embeddedUser->getBaseOrganizationId());

        if ($user->isActive()) {
            $this->checkCurrentSettings($embeddedUser, $embeddedUserData, $user);

            return $user;
        }
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel()
    {
        return $this->_('Load a staff user');
    }
}