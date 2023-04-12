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
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage ItemSnippets
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 24-sep-2014 17:41:20
 */
class GroupFormSnippet extends \Gems\Snippets\ModelFormSnippetAbstract
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
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        private readonly AclRepository $aclRepository,
        private readonly CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
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
        $this->loadFormData();

        if ($this->getRedirectRoute()) {
            return false;
        }

        return parent::hasHtmlOutput();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        if (! $this->formData) {
            parent::loadFormData();

            $model     = $this->getModel();
            $roles     = $model->get('ggp_role', 'multiOptions');
            $userRoles = $this->aclRepository->getAllowedRoles($this->currentUserRepository->getCurrentUser());

            // \MUtil\EchoOut\EchoOut::track($userRoles, $roles);
            // Make sure we get the roles as they are labeled
            foreach ($roles as $role => $label) {
                if (! isset($userRoles[$role])) {
                    unset($roles[$role]);
                }
            }

            if ($this->formData && $this->formData['ggp_role'] && (! isset($roles[$this->formData['ggp_role']]))) {
                if ($this->createData) {
                    $this->formData['ggp_role'] = reset($roles);
                } else {
                    $this->messenger->addMessage($this->_('You do not have sufficient privilege to edit this group.'));
                    $this->redirectRoute = $this->menuHelper->getRelatedRouteUrl('show');

                    return $this->formData;
                }
            }
            $model->set('ggp_role', 'multiOptions', $roles);

            // TODO: Reenable?
            // $this->menu->getParameterSource()->offsetSet('ggp_role', $this->formData['ggp_role']);
        }
        
        return $this->formData;
    }
}
