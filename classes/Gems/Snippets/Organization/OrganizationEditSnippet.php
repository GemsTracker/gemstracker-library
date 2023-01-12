<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Organization;

use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Snippets\ModelFormSnippet;
use Gems\User\User;
use Gems\User\UserLoader;
use MUtil\Html;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Organization
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class OrganizationEditSnippet extends ModelFormSnippet
{
    protected User $currentUser;
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        CurrentUserRepository $currentUserRepository,
        protected UserLoader $userLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
         parent::afterSave($changed);

        // Make sure any changes in the allowed list are reflected.
        $this->currentUser->refreshAllowedOrganizations();
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        parent::loadFormData();

        if (isset($this->formData['gor_id_organization']) && $this->formData['gor_id_organization']) {
            $model = $this->getModel();

            // Strip self from list of organizations
            $multiOptions = $model->get('gor_accessible_by', 'multiOptions');
            unset($multiOptions[$this->formData['gor_id_organization']]);
            $model->set('gor_accessible_by', 'multiOptions', $multiOptions);

            // Show allowed organizations
            $org         = $this->userLoader->getOrganization($this->formData['gor_id_organization']);
            $allowedOrgs = $org->getAllowedOrganizations();
            //Strip self
            unset($allowedOrgs[$this->formData['gor_id_organization']]);
            $display = join(', ', $allowedOrgs);
            if (! $display) {
                $display = Html::create('em', $this->_('No access to other organizations.'));
            }
            $this->formData['allowed'] = $display;
            $model->set('allowed', 'value', $display);
        }
        // MultiOption null is ''.
        if (! isset($this->formData['gor_respondent_edit'])) {
            $this->formData['gor_respondent_edit'] = '';
        }
        if (! isset($this->formData['gor_respondent_show'])) {
            $this->formData['gor_respondent_show'] = '';
        }
        return $this->formData;
    }
}