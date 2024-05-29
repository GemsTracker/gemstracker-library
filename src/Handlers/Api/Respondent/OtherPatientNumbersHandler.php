<?php

namespace Gems\Handlers\Api\Respondent;

use Gems\Api\Middleware\ApiAuthenticationMiddleware;
use Gems\Repository\RespondentRepository;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OtherPatientNumbersHandler implements RequestHandlerInterface
{
    public function __construct(
        protected RespondentRepository $respondentRepository
    )
    {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $patientNr = $request->getAttribute('patientNr');
        $organizationId = $request->getAttribute('organizationId');

        $pairs = true;
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['detailed']) && $queryParams['detailed'] == 1) {
            $pairs = false;
        }

        $userRole = null;
        $userOrganizationId = $organizationId;
        if (isset($queryParams['allowed-organizations']) && $queryParams['allowed-organizations'] == 1) {
            $userRole = $request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_ROLE);
            $userOrganizationId = $request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_ORGANIZATION);
        }

        $otherPatientNumbers = $this->respondentRepository->getOtherPatientNumbers(
            $patientNr,
            $organizationId,
            $pairs,
            false,
            true,
            $userOrganizationId,
            $userRole,
        );

        return new JsonResponse($otherPatientNumbers);
    }
}