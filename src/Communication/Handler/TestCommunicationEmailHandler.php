<?php

declare(strict_types=1);

namespace Gems\Communication\Handler;

use Gems\Api\Util\ContentTypeChecker;
use Gems\Communication\CommFieldsRepository;
use Gems\Communication\CommunicationRepository;
use Gems\Event\Application\TokenEventMailFailed;
use Gems\Event\Application\TokenEventMailSent;
use Gems\Exception\MailException;
use Gems\Locale\Locale;
use Gems\Middleware\LocaleMiddleware;
use Gems\Repository\OrganizationRepository;
use Gems\Request\MapRequest;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;

class TestCommunicationEmailHandler implements RequestHandlerInterface
{
    /**
     * @var array List of allowed content types as input for write methods
     */
    protected array $allowedContentTypes = ['application/json'];

    protected ContentTypeChecker $contentTypeChecker;

    public function __construct(
        protected readonly MapRequest $mapRequest,
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly CommFieldsRepository $commFieldsRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly array $config,
    )
    {
        $this->contentTypeChecker = new ContentTypeChecker($this->allowedContentTypes);
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->contentTypeChecker->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        /**
         * @var TestCommunicationEmailParams $postObject
         */
        $postObject = $this->mapRequest->mapRequestBody($request, TestCommunicationEmailParams::class);

        /**
         * @var Locale $locale
         */
        $locale = $this->communicationRepository->getCommunicationLanguage($request->getAttribute(LocaleMiddleware::LOCALE_ATTRIBUTE));

        $mailFields = $this->commFieldsRepository->getCommFields($postObject->type, $locale->getLanguage(), $postObject->context, $postObject->organizationId);

        $email = $this->communicationRepository->getNewEmail();
        $email->subject($postObject->subject, $mailFields);

        $fromName = null;
        $from = $this->config['email']['site'];
        $template = 'default::mail';
        if ($postObject->organizationId) {
            $organization = $this->organizationRepository->getOrganization($postObject->organizationId);
            $from = $organization->getEmail() ?? $this->config['email']['site'];
            $fromName = $organization->getContactName() ?? null;
            $template = $this->communicationRepository->getTemplate($organization);
        }

        $mailer = $this->communicationRepository->getMailer();

        try {
            $email->addFrom(new Address($from, $fromName));
            $email->addTo(new Address($postObject->to));

            $email->htmlTemplate($template, $postObject->body, $mailFields);

            $mailer->send($email);
        } catch (TransportExceptionInterface  $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 500);
        }
        return new EmptyResponse(200);
    }

    protected function getMailFields(TestCommunicationEmailParams $params): array
    {
        return [];
    }
}