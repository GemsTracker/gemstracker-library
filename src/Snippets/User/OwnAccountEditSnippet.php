<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\User;

use Gems\Audit\AuditLog;
use Gems\Cache\HelperAdapter;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\LocaleCookie;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\RedirectResponse;
use MUtil\Model\ModelAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\User
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 15:15:07
 */
class OwnAccountEditSnippet extends ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    protected User $currentUser;

    protected ModelAbstract $model;

    protected ServerRequestInterface $request;

    private ?ResponseInterface $response = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        private readonly array $config,
        private readonly Model $modelContainer,
        private readonly UserLoader $userLoader,
        private readonly CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
    }

    public function beforeDisplay()
    {
        parent::beforeDisplay();
    }


    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
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
        if ($changed) {
            $this->auditLog->logChange($this->request, null, $this->formData);

            // Reload the current user data
            $user       = $this->currentUser;
            $currentOrg = $user->getCurrentOrganizationId();

            $user = $this->userLoader->getUser($user->getLoginName(), $user->getBaseOrganizationId());
            $this->currentUserRepository->setCurrentUser($user);
            $user->setCurrentOrganization($currentOrg);

            // In case locale has changed, set it in a cookie
            $this->response = (new LocaleCookie())->addLocaleCookieToResponse(
                new RedirectResponse($this->request->getUri()),
                $this->formData['gsf_iso_lang'],
            );

            $this->addMessage($this->_('Saved your setup data', locale: $this->formData['gsf_iso_lang']));
        } else {
            $this->addMessage($this->_('No changes to save!'));
        }

        if ($this->cacheTags && ($this->cache instanceof HelperAdapter)) {
            $this->cache->invalidateTags((array)$this->cacheTags);
        }
    }

    /**
     * After validation we clean the form data to remove all
     * entries that do not have elements in the form (and
     * this filters the data as well).
     */
    public function cleanFormData()
    {
        parent::cleanFormData();

        // You can only save data for the current user
        $this->formData['gsf_id_user'] = $this->currentUser->getUserId();
    }

    /**
     * Creates the model
     *
     * @return FullDataInterface
     */
    protected function createModel(): FullDataInterface
    {
        $this->extraFilter['gsf_id_user'] = $this->currentUser->getUserId();

        if (! $this->model instanceof \Gems\Model\StaffModel) {
            $this->model = $this->modelContainer->getStaffModel(false);
            $this->model->applyOwnAccountEdit(!$this->config['account']['edit-auth']['enabled']);
        }

        return $this->model;
    }

    /**
     * The message to display when the change is not allowed
     *
     * @return string
     */
    protected function getNotAllowedMessage()
    {
        return $this->_('System account can not be changed.');
    }

    /**
     * If the current user is the system user, present a message and don't allow to edit
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        if ($this->currentUser->getUserId() == \Gems\User\UserLoader::SYSTEM_USER_ID) {
            $this->addMessage($this->getNotAllowedMessage());
            return false;
        }

        return parent::hasHtmlOutput();
    }
}
