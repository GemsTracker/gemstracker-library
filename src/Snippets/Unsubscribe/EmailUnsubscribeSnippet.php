<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Unsubscribe;

use Gems\Audit\AuditLog;
use Gems\Communication\Unsubscribe\Messenger\Message\SubscriptionInfo;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\FormSnippetAbstract;
use Symfony\Component\Messenger\MessageBusInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Unsubscribe
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 19-Mar-2019 14:17:37
 */
class EmailUnsubscribeSnippet extends FormSnippetAbstract
{

    protected int $currentOrganizationId;

    /**
     * Since this forms acts as if it was successful when a valid e-mail address was
     * entered we need to store the real state for logging purposes here.
     *
     * @var boolean
     */
    protected bool $realChange = false;

    /**
     *
     * @var string|null Optional project specific after unsubscribe message.
     */
    protected string|null $unsubscribedMessage = null;

    /**
     * @var int The value to assign while unsubscribing
     */
    protected int $unsubscribedValue = 0;

    /**
     *
     * @var array of arrays, either null or respondent id and org id
     */
    protected array $userData = [0 => []];

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly MessageBusInterface $messageBus,
        array $config,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->currentOrganizationId = $this->currentUserRepository->getCurrentOrganizationId();

        $this->unsubscribedValue = $config['communication']['unsubscribe']['unsubscribeValue'] ?? 0;

    }

    /**
     * Add the elements to the form
     *
     * @param mixed $form
     */
    protected function addFormElements(mixed $form)
    {
        // Veld inlognaam
        $element = $form->createElement('text', 'email');
        $element->setLabel($this->_('Your E-Mail address'))
                ->setAttrib('size', 30)
                ->setRequired(true)
                ->addValidator('SimpleEmail');

        $form->addElement($element);

        return $element;
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
        // \MUtil\EchoOut\EchoOut::track($this->getMessenger()->getCurrentMessages(), $this->formData, $this->userData, $this->realChange);
        /*
        // This is also commented in our parent class (FormSnippetAbstract)
        foreach ($this->userData as $userData) {
            $this->auditLog->logRequest(
                    $this->requestInfo,
                    $this->getMessages(),
                    $this->formData + $userData,
                    isset($userData['gr2o_id_user']) ? $userData['gr2o_id_user'] : 0,
                    $this->realChange);
        }
        */
    }

    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData(): int
    {
        $this->addMessage($this->unsubscribedMessage ?:
                $this->_('If your E-email address is known, you have been unsubscribed.'));

        $unsubscribeInfo = new SubscriptionInfo(
            $this->formData['email'],
            $this->currentOrganizationId,
            $this->unsubscribedValue,
            $this->formData['comment'] ?? null,
        );
        $this->messageBus->dispatch($unsubscribeInfo);

        // Always act like something was saved when
        return 1;
    }
}
