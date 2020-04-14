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
abstract class DeferredUserLoaderAbstract extends \MUtil_Translate_TranslateableAbstract implements DeferredUserLoaderInterface
{
    /**
     * @var \Gems_User_Organization
     */
    protected $currentOrganization;

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil_Html_HtmlElement
     */
    // abstract public function getLabel();

    /**
     * Get the deferred user
     *
     * @param \Gems_User_User $embeddedUser
     * @param string $deferredLogin name of the user to log in
     * @return \Gems_User_user|null
     */
    // abstract public function getDeferredUser(\Gems_User_User $embeddedUser, $deferredLogin);
    
    /**
     * Try to find / load an active user with this data
     *
     * @param string $userLogin
     * @param mixed $organisations (Array of) organization id's or objects
     * @return \Gems_User_User
     */
    public function getUser($userLogin, $organisations = null)
    {
        // \MUtil_Echo::track($userLogin, $organisations );

        $user       = $this->currentUser;
        $userLoader = $this->loader->getUserLoader();

        // Set to current organization if not passed and no organization is allowed
        if ((null === $organisations) && (! $userLoader->allowLoginOnWithoutOrganization)) {
            $organisations = [$this->currentOrganization];
        }
        foreach ((array) $organisations as $currentOrg) {
            if ($currentOrg instanceof \Gems_User_Organization) {
                $user = $userLoader->getUser($userLogin, $currentOrg->getId());
            } else {
                $user = $userLoader->getUser($userLogin, $currentOrg);
            }
            if ($user->isActive()) {
                return $user;
            }
        }

        return $user;
    }
}