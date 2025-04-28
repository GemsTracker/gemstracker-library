<?php

namespace Gems\Handlers\Api;

use Gems\Communication\CommFieldsRepository;
use Gems\Locale\Locale;
use Gems\Middleware\LocaleMiddleware;
use Gems\Repository\CommFieldRepository;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Model\MetaModelInterface;

class CommFieldsHandler implements RequestHandlerInterface
{
    public function __construct(
        protected readonly CommFieldsRepository $commFieldsRepository,
        protected readonly CommFieldRepository $commFieldRepository,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $target = $request->getAttribute('target');
        if ($target === null) {
            return new JsonResponse(['error' => 'Target required']);
        }

        $targets = $this->commFieldRepository->getCommFieldTypes();
        if (!isset($targets[$target])) {
            return new JsonResponse(['error' => 'No valid target']);
        }

        /**
         * @var Locale $locale
         */
        $locale = $request->getAttribute(LocaleMiddleware::LOCALE_ATTRIBUTE);
        $queryParams = $request->getQueryParams();
        $id = $request->getAttribute(MetaModelInterface::REQUEST_ID) ?? $queryParams['id'] ?? null;
        $organizationId = $request->getAttribute('organizationId') ?? $queryParams['organizationId'] ?? null;

        $commFields = $this->commFieldsRepository->getCommFields($target, $locale->getLanguage(), $id, $organizationId);

        if ($commFields) {
            return new JsonResponse($commFields);
        }
        return new EmptyResponse();
    }
}
