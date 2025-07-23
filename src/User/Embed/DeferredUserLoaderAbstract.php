<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed;

use Gems\Exception;
use Gems\Legacy\CurrentUserRepository;
use Gems\User\Organization;
use Gems\User\User;
use Gems\User\UserLoader;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 16:07:27
 */
abstract class DeferredUserLoaderAbstract
        implements DeferredUserLoaderInterface
{

    protected int $currentOrganizationId;
    public function __construct(
        protected TranslatorInterface $translator,
        protected UserLoader $userLoader,
        CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentOrganizationId = $currentUserRepository->getCurrentOrganizationId();
    }

    /**
     *
     * @param User $embeddedUser
     * @param \Gems\User\Embed\EmbeddedUserData $embeddedUserData
     * @param User $user
     */
    protected function checkCurrentSettings(User $embeddedUser, EmbeddedUserData $embeddedUserData, User $user)
    {
        if ($user->getCurrentOrganizationId() !== $embeddedUser->getCurrentOrganizationId() && $user->isAllowedOrganization($embeddedUser->getCurrentOrganizationId())) {
            $user->setCurrentOrganizationId($embeddedUser->getCurrentOrganizationId());
        }

        $groupId = $embeddedUserData->getUserGroupId();
        if ($groupId && ($user->getGroupId() != $groupId)) {
            $user->setGroupSession($groupId);
        }

        /*
        $user->setSessionCrumbs($embeddedUserData->getCrumbOption());
        $user->setSessionFramed(true);
        $user->setSessionMvcLayout($embeddedUserData->getMvcLayout());
        $user->setSessionStyle($embeddedUserData->getUserStyle());
        // */
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // abstract public function getLabel();

    /**
     * Get the deferred user
     *
     * @param User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return User|null
     */
    // abstract public function getDeferredUser(User $embeddedUser, $deferredLogin);

    /**
     * Try to find / load an active user with this data
     *
     * @param string $userLogin
     * @param mixed $organisations (Array of) organization id's or objects
     * @return User|null
     */
    public function getUserOrNull($userLogin, $organisations = null): User|null
    {
        // Set to current organization if not passed and no organization is allowed
        if ((null === $organisations) && (! $this->userLoader->allowLoginOnWithoutOrganization)) {
            $organisations = [$this->currentOrganizationId];
        }
        foreach ((array) $organisations as $currentOrg) {
            if ($currentOrg instanceof Organization) {
                $user = $this->userLoader->getUserOrNull($userLogin, $currentOrg->getId());
            } else {
                $user = $this->userLoader->getUserOrNull($userLogin, $currentOrg);
            }
            if ($user instanceof User && $user->isActive()) {
                return $user;
            }
        }

        return null;
    }
}