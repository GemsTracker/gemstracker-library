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

        $userOrganizationId = $request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_ORGANIZATION);

        // TEMP FOR PENTEST
        //if ($request->getAttribute(\Gems\Api\Middleware\ApiAuthenticationMiddleware::AUTH_TYPE) === 'session') {
        //    $pairs = false;
        //}

        $otherPatientNumbers = $this->respondentRepository->getOtherPatientNumbers(
            $patientNr,
            $organizationId,
            $pairs,
            false,
            true,
            $userOrganizationId
        );

        return new JsonResponse($otherPatientNumbers);
    }
}