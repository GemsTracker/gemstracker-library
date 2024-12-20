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

use Gems\Audit\AuditLog;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\ModelFormSnippet;
use Gems\User\User;
use Gems\User\UserLoader;
use MUtil\Html;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
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
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        CurrentUserRepository $currentUserRepository,
        protected UserLoader $userLoader,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
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

        $model = $this->getModel();
        $metaModel = $model->getMetaModel();

        if (isset($this->formData['gor_id_organization']) && $this->formData['gor_id_organization']) {

            // Strip self from list of organizations
            $multiOptions = $metaModel->get('gor_accessible_by', 'multiOptions');
            unset($multiOptions[$this->formData['gor_id_organization']]);
            $metaModel->set('gor_accessible_by', 'multiOptions', $multiOptions);

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
            $metaModel->set('allowed', 'value', $display);
        }
        // MultiOption null is ''.
        foreach ($metaModel->getColNames('multiOptions') as $colName) {
            if ((! isset($this->formData[$colName]) || null == $this->formData[$colName])) {
                $this->formData[$colName] = '';
            }
        }
        return $this->formData;
    }

    protected function saveData(): int
    {
        // Otherwise the value may be null and should not change here
        unset($this->formData['gor_has_respondents']);

        return parent::saveData();
    }
}