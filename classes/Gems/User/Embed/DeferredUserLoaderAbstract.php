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

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 16:07:27
 */
abstract class DeferredUserLoaderAbstract extends \MUtil\Translate\TranslateableAbstract
        implements DeferredUserLoaderInterface
{
    /**
     * @var \Gems\User\Organization
     */
    protected $currentOrganization;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @param \Gems\User\User $embeddedUser
     * @param \Gems\User\Embed\EmbeddedUserData $embeddedUserData
     * @param \Gems\User\User $user
     */
    protected function checkCurrentSettings(\Gems\User\User $embeddedUser, EmbeddedUserData $embeddedUserData, \Gems\User\User $user)
    {
        if ($user->getCurrentOrganizationId() !== $embeddedUser->getCurrentOrganizationId()) {
            $user->setCurrentOrganization($embeddedUser->getCurrentOrganizationId());
        }

        $groupId = $embeddedUserData->getUserGroupId();
        if ($groupId && ($user->getGroupId() != $groupId)) {
            $user->setGroupSession($groupId);
        }

        $user->setSessionCrumbs($embeddedUserData->getCrumbOption());
        $user->setSessionFramed(true);
        $user->setSessionMvcLayout($embeddedUserData->getMvcLayout());
        $user->setSessionStyle($embeddedUserData->getUserStyle());
    }

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // abstract public function getLabel();

    /**
     * Get the deferred user
     *
     * @param \Gems\User\User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return \Gems_User_user|null
     */
    // abstract public function getDeferredUser(\Gems\User\User $embeddedUser, $deferredLogin);

    /**
     * Try to find / load an active user with this data
     *
     * @param string $userLogin
     * @param mixed $organisations (Array of) organization id's or objects
     * @return \Gems\User\User
     */
    public function getUser($userLogin, $organisations = null)
    {
        // \MUtil\EchoOut\EchoOut::track($userLogin, $organisations );

        //$user       = $this->currentUser;
        $userLoader = $this->loader->getUserLoader();

        // Set to current organization if not passed and no organization is allowed
        if ((null === $organisations) && (! $userLoader->allowLoginOnWithoutOrganization)) {
            $organisations = [$this->currentOrganization];
        }
        foreach ((array) $organisations as $currentOrg) {
            if ($currentOrg instanceof \Gems\User\Organization) {
                $user = $userLoader->getUser($userLogin, $currentOrg->getId());
            } else {
                $user = $userLoader->getUser($userLogin, $currentOrg);
            }
            if ($user->isActive()) {
                return $user;
            }
        }

        throw new \Exception(); // TODO: Better name
        //return $user;
    }
}