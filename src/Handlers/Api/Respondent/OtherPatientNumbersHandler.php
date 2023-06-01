<?php

namespace Gems\Handlers\Api\Respondent;

use Gems\Repository\RespondentRepository;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OtherPatientNumbersHandler implements RequestHandlerInterface
{
    public function __construct(protected RespondentRepository $respondentRepository)
    {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $patientNr = $request->getAttribute('patientNr');
        $organizationId = $request->getAttribute('organizationId');

        $otherPatientNumbers = $this->respondentRepository->getOtherPatientNumbers($patientNr, $organizationId, false, false, true);

        return new JsonResponse($otherPatientNumbers);
    }
}