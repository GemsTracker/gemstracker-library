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
use Gems\MenuNew\RouteHelper;
use Gems\Model;
use Gems\SessionNamespace;
use Gems\Snippets\ZendFormSnippetAbstract;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Validator\Digits;
use Laminas\Validator\StringLength;
use Mezzio\Session\SessionInterface;
use MUtil\Validate\SimpleEmail;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
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
class OwnAccountEditAuthSnippet extends ZendFormSnippetAbstract
{
    private const MAX_ATTEMPTS = 10;

    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    protected User $currentUser;

    protected ServerRequestInterface $request;

    protected SessionInterface $session;

    private readonly SessionNamespace $sessionNamespace;

    private readonly bool $verify;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        private readonly Model $modelContainer,
        private readonly UserLoader $userLoader,
        private readonly AccesslogRepository $accesslogRepository,
        private readonly CurrentUserRepository $currentUserRepository,
        private readonly RouteHelper $routeHelper,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

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
        if (!$this->verify) {
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

            // TODO: check password + throttle

            return 0;
        }

        $newEmail = null;
        if ($this->sessionNamespace->has('new_email')) {
            $emailSession = $this->sessionNamespace->get('new_email');

            $newEmailSecret = $this->formData['new_email_secret'] ?: '';
            if ($newEmailSecret !== $emailSession['secret']) {
                $emailSession['attempts']++;
                if ($emailSession['attempts'] > self::MAX_ATTEMPTS) {
                    $this->sessionNamespace->unset('new_email');
                    $this->sessionNamespace->unset('new_phone');

                    $this->addMessage($this->_('Too many failed attempts, please try again.'));
                    return 0;
                }

                $this->sessionNamespace->set('new_email', $emailSession);

                $this->addMessage($this->_('Please enter the 6-digit code we e-mailed to you.'));
                return 0;
            }

            $newEmail = $emailSession['email'];
        }

        $newPhone = null;
        if ($this->sessionNamespace->has('new_phone')) {
            $phoneSession = $this->sessionNamespace->get('new_phone');

            $newPhoneSecret = $this->formData['new_phone_secret'] ?: '';
            if ($newPhoneSecret !== $phoneSession['secret']) {
                $phoneSession['attempts']++;
                if ($phoneSession['attempts'] > self::MAX_ATTEMPTS) {
                    $this->sessionNamespace->unset('new_email');
                    $this->sessionNamespace->unset('new_phone');

                    $this->addMessage($this->_('Too many failed attempts, please try again.'));
                    return 0;
                }

                $this->sessionNamespace->set('new_phone', $phoneSession);

                $this->addMessage($this->_('Please enter the 6-digit code we sent to you by SMS.'));
                return 0;
            }

            $newPhone = $phoneSession['phone'];
        }

        $staffModel = $this->modelContainer->getStaffModel();
        /*$user = $staffModel->loadFirst([
            'gsf_id_user' => $this->currentUser->getUserId(),
        ]);*/

        $newValues = [];
        if ($newEmail) {
            $newValues['gsf_email'] = $newEmail;
        }

        if ($newPhone) {
            $newValues['gsf_phone_1'] = $newPhone;
        }

        $staffModel->save($newValues, [
            'gsf_id_user' => $this->currentUser->getUserId(),
        ]);

        $this->sessionNamespace->unset('email');
        $this->sessionNamespace->unset('phone');

        return $staffModel->getChanged();
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

    protected function addFormElements(mixed $form): void
    {
        if (!$this->verify) {
            $element = new \MUtil\Form\Element\Text('gsf_email');
            $element
                ->setLabel($this->_('E-Mail'))
                ->setAttrib('size', 30)
                ->addValidator(new SimpleEmail())
                ->setValue($this->currentUser->getEmailAddress())
            ;
            $form->addElement($element);

            $element = new \MUtil\Form\Element\Text('gsf_phone_1');
            $element
                ->setLabel($this->_('Mobile phone'))// TODO required
                ->setValue($this->currentUser->getPhonenumber())
            ;
            $form->addElement($element);

            $element = new \MUtil\Form\Element\Password('password');
            $element
                ->setLabel($this->_('Current password'))
                ->setAttrib('renderPassword', true)
            ;
            $form->addElement($element);

            return;
        }

        if ($this->sessionNamespace->has('new_email')) {
            $element = new \MUtil\Form\Element\Html('email_explanation');
            $element->div(sprintf(
                $this->_('Enter the 6-digit verification code we e-mailed to you to verify your new e-mail address %s'),
                $this->sessionNamespace->get('new_email')['email'],
            ));
            $form->addElement($element);

            $element = new \MUtil\Form\Element\Text('new_email_secret');
            $element
                ->setLabel($this->_('E-mail code'))
                ->setAttrib('size', 6)
                ->addValidator(new Digits())
                ->addValidator(new StringLength(6, 6))
            ;
            $form->addElement($element);
        }

        if ($this->sessionNamespace->has('new_phone')) {
            $element = new \MUtil\Form\Element\Html('phone_explanation');
            $element->div(sprintf(
                $this->_('Enter the 6-digit verification code we sent to you by SMS to verify your new mobile number %s'),
                $this->sessionNamespace->get('new_phone')['phone'],
            ));
            $form->addElement($element);

            $element = new \MUtil\Form\Element\Text('new_phone_secret');
            $element
                ->setLabel($this->_('Phone code'))
                ->setAttrib('size', 6)
                ->addValidator(new Digits())
                ->addValidator(new StringLength(6, 6))
            ;
            $form->addElement($element);
        }
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
