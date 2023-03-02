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

use Gems\Audit\AccesslogRepository;
use Gems\Cache\HelperAdapter;
use Gems\Legacy\CurrentUserRepository;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Model;
use Gems\SessionNamespace;
use Gems\Snippets\ModelFormSnippetAbstract;
use Gems\User\User;
use Gems\User\UserLoader;
use Mezzio\Session\SessionInterface;
use MUtil\Model\ModelAbstract;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
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
class OwnAccountEditAuthSnippet extends ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    protected User $currentUser;

    protected ModelAbstract $model;

    protected ServerRequestInterface $request;

    protected SessionInterface $session;

    private readonly SessionNamespace $sessionNamespace;

    private readonly bool $verify;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        MenuSnippetHelper $menuHelper,
        private readonly Model $modelContainer,
        private readonly UserLoader $userLoader,
        private readonly AccesslogRepository $accesslogRepository,
        private readonly CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $menuHelper);

        $this->sessionNamespace = new SessionNamespace($this->session, __CLASS__);

        $this->verify = $this->sessionNamespace->has('new_email') || $this->sessionNamespace->has('new_phone');
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
            $this->accesslogRepository->logChange($this->request, null, $this->formData);

            // Reload the current user data
            $user = $this->userLoader->getUser($this->currentUser->getLoginName(), $this->currentUser->getBaseOrganizationId());
            $this->currentUserRepository->setCurrentUser($user);


            $this->addMessage($this->_('Saved your setup data', locale: $this->formData['gsf_iso_lang']));
        } else {
            $this->addMessage($this->_('No changes to save!'));
        }

        if ($this->cacheTags && ($this->cache instanceof HelperAdapter)) {
            $this->cache->invalidateTags([$this->cacheTags]);
        }
    }

    protected function saveData(): int
    {
        $newEmail = trim($this->formData['gsf_email'] ?: '');
        $newPhone = trim($this->formData['gsf_phone_1'] ?: '');

        if ($newEmail !== $this->currentUser->getEmailAddress()) {
            $this->sessionNamespace->set('new_email', [
                'email' => $newEmail,
                'secret' => random_int(100000, 999999),
                'attempts' => 0,
            ]);
        }

        if ($newPhone === '') {
            // TODO
        } elseif ($newPhone !== $this->currentUser->getPhonenumber()) {
            $this->sessionNamespace->set('new_phone', [
                'phone' => $newPhone,
                'secret' => random_int(100000, 999999),
                'attempts' => 0,
            ]);
        }

        return 0;
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
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): FullDataInterface
    {
        $this->extraFilter['gsf_id_user'] = $this->currentUser->getUserId();

        if (! $this->model instanceof \Gems\Model\StaffModel) {
            $this->model = $this->modelContainer->getStaffModel(false);
        }

        if (!$this->verify) {
            $this->model->applyOwnAccountEditAuth();
        }

        return $this->model;
    }

    protected function addFormElements(mixed $form)
    {
    }

    protected function createForm($options = null)
    {
        $form = parent::createForm($options);

        if (!$this->verify) {
            return $form;
        }

        if ($this->sessionNamespace->has('new_email')) {
            $element = new \MUtil\Form\Element\Html('email_explanation');
            $element->div(sprintf(
                $this->_('Enter the 6-digit verification code we e-mailed to you to verify your new e-mail address %s'),
                $this->sessionNamespace->get('new_email')['email'],
            ));
            $form->addElement($element);

            $this->model->set('new_email_secret', [
                'label' => $this->_('E-mail code'),
                'size' => 6,
            ]);
        }

        if ($this->sessionNamespace->has('new_phone')) {
            $element = new \MUtil\Form\Element\Html('phone_explanation');
            $element->div(sprintf(
                $this->_('Enter the 6-digit verification code we sent to you by SMS to verify your new mobile number %s'),
                $this->sessionNamespace->get('new_phone')['phone'],
            ));
            $form->addElement($element);

            $this->model->set('new_phone_secret', [
                'label' => $this->_('Phone code'),
                'size' => 6,
            ]);
        }

        return $form;
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
