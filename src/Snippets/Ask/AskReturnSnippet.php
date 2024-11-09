<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Ask;

use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Tracker\Token;
use Gems\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Snippets\MessageableSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Ask
 * @since      Class available since version 1.0
 */
class AskReturnSnippet extends MessageableSnippetAbstract
{
    protected ?User $currentUser;

    protected ?Token $token = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        CurrentUserRepository $currentUserRepository,
        protected readonly MenuSnippetHelper $menuSnippetHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    public function getResponse(): ?ResponseInterface
    {
        $url = $this->token->getReturnUrl();
        if (! $url) {
            if (!$this->currentUser instanceof User || !$this->currentUser->isActive()) {
                $url = $this->menuSnippetHelper->getRelatedRouteUrl('forward');
            } else {
                $url = $this->menuSnippetHelper->getRouteUrl('respondent.show', [
                    MetaModelInterface::REQUEST_ID1 => $this->token->getPatientNumber(),
                    MetaModelInterface::REQUEST_ID2 => $this->token->getOrganizationId(),
                ]);
            }
        }

        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . ') url: ' .  $url . "\n", FILE_APPEND);

        return new RedirectResponse($url);

    }


    public function hasHtmlOutput(): bool
    {
        return false;
    }
}