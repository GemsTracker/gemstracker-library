<?php

declare(strict_types=1);

namespace Gems\Snippets\Tracker;

use DateTimeImmutable;
use Gems\Audit\AuditLog;
use Gems\Form;
use Gems\Form\Element\DateTimeInput;
use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\FormSnippetAbstract;
use Gems\Tracker\Token;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class TokenAnsweredOnFormSnippet extends FormSnippetAbstract
{
    protected string|null $tokenComment = null;

    protected string|null $receptionCode = null;

    protected Token $token;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
        protected CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);
    }


    protected function addFormElements(mixed $form): void
    {
        if (!$form instanceof Form) {
            return;
        }
        $element = new DateTimeInput('completionTime', [
            'label' => $this->_('Answered on'),
            'description' => $this->_('When was this token answered'),
            'required' => true,
            'value' => (new DateTimeImmutable())->format('d-m-Y H:i:s')
        ]);

        $form->addElement($element);
        $this->addCsrf($this->csrfName, $this->csrfToken, $form);
    }

    protected function saveData(): int
    {
        $this->token->setCompletionTime(new DateTimeImmutable($this->formData['completionTime']), $this->currentUserRepository->getCurrentUserId());

        $message = $this->tokenComment ?? null;

        if ($this->receptionCode) {
            $this->token->setReceptionCode($this->receptionCode, $message, $this->currentUserRepository->getCurrentUserId());
        } elseif ($message) {
            $this->token->setComment($message, $this->currentUserRepository->getCurrentUserId());
        }

        $route = $this->menuHelper->getRelatedRoute('show');
        $this->redirectRoute = $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl($route, $this->requestInfo->getRequestMatchedParams());

        return 1;
    }
}