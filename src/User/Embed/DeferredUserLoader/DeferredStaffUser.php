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

use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\User\Embed\DeferredUserLoaderAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\User;
use Gems\User\UserLoader;
use Zalt\Base\TranslatorInterface;

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
    public function __construct(
        TranslatorInterface $translator,
        UserLoader $userLoader,
        CurrentUserRepository $currentUserRepository,
        protected Model $modelLoader,

    ) {
        parent::__construct($translator, $userLoader, $currentUserRepository);
    }

    /**
     * Get the deferred user
     *
     * @param User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return User|null
     */
    public function getDeferredUser(User $embeddedUser, string $deferredLogin): ?User
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

        $model = $this->modelLoader->getStaffModel();
        $data  = $model->loadNew();

        $data['gsf_login']            = $deferredLogin;
        $data['gsf_id_organization']  = $embeddedUser->getBaseOrganizationId();
        $data['gsf_id_primary_group'] = $embeddedUser->getGroupId();
        $data['gsf_last_name']        = ucfirst($deferredLogin);
        $data['gsf_iso_lang']         = $embeddedUser->getLocale();
        $data['gul_user_class']       = 'StaffUser';
        $data['gul_can_login']        = 1;

        $model->save($data);

        $user = $this->userLoader->getUser($deferredLogin, $embeddedUser->getBaseOrganizationId());

        if ($user->isActive()) {
            $this->checkCurrentSettings($embeddedUser, $embeddedUserData, $user);

            return $user;
        }

        return null;
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    public function getLabel(): string
    {
        return $this->translator->_('Load a staff user');
    }
}