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

use Gems\Auth\Acl\AclRepository;
use Gems\Auth\Acl\GroupRepository;
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessageStatus;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

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

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        private readonly AclRepository $aclRepository,
        private readonly CurrentUserRepository $currentUserRepository,
        private readonly GroupRepository $groupRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate, $messenger);
    }

    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
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
        $roles = $this->aclRepository->getAllowedRoles($this->currentUserRepository->getCurrentUser());

        //\MUtil\EchoOut\EchoOut::track($data);

        // Perform access check here, before anything has happened!!!
        if (isset($data['ggp_role']) && (! isset($roles[$data['ggp_role']]))) {
            $this->messenger->addMessage($this->_('You do not have sufficient privilege to edit this group.'));
            $this->afterActionRouteUrl = $this->menuHelper->getRelatedRouteUrl('show');

            return false;
        }

        $dependents = $this->groupRepository->getDependingGroups($data['ggp_code']);

        // If we try to delete a group on which others are dependent, add an error message and reroute
        if (count($dependents) > 0) {
            $this->messenger->addMessage(sprintf(
                $this->_('This group is being used by groups %s and hence cannot be deleted'),
                implode(', ', $dependents)
            ), MessageStatus::Danger);
            $this->afterActionRouteUrl = $this->menuHelper->getRelatedRouteUrl('show');

            return false;
        }

        // TODO: Reenable?
        //$this->menu->getParameterSource()->offsetSet('ggp_role', $data['ggp_role']);

        return parent::hasHtmlOutput();
    }
}
