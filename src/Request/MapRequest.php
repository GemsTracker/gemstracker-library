<?php

declare(strict_types=1);

namespace Gems\Request;

use Gems\Exception\SymfonyValidatorException;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MapRequest
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    )
    {
    }

    public function mapRequestBody(ServerRequestInterface $request, string $dtoClassName): object|null
    {
        $rawBody = $request->getBody()->getContents();
        if (empty($rawBody)) {
            return null;
        }

        try {
            $dto = $this->serializer->deserialize(
                $rawBody,
                $dtoClassName,
                'json',
                [
                    // 'groups' => ['post'], // Apply specific groups if needed
                    // Disallow extra fields not defined in the DTO
                    'allow_extra_attributes' => false,
                ]
            );
        } catch (SerializerExceptionInterface $e) {
            throw $e;
        }

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw new SymfonyValidatorException($violations);
        }
        return $dto;
    }

    public function mapRequestQuery(ServerRequestInterface $request, string $dtoClassName): object
    {
        $queryParams = $request->getQueryParams();

        return $this->mapDtoFromArray($queryParams, $dtoClassName);
    }

    public function mapAllParams(ServerRequestInterface $request, string $dtoClassName): object
    {
        $queryParams = $request->getQueryParams();

        $rawBody = $request->getBody()->getContents();
        $bodyParams = empty($rawBody) ? [] : json_decode($rawBody, true);
        $routeParams = $this->getRouteParams($request);

        $mergedData = array_merge($queryParams, $bodyParams, $routeParams);

        return $this->mapDtoFromArray($mergedData, $dtoClassName);
    }

    private function mapDtoFromArray(array $data, string $dtoClassName): object
    {

        // Optional context for denormalization
        $context = [
            // 'groups' => ['query'], // Apply specific groups if needed
            // Disallow extra fields not defined in the DTO
            'allow_extra_attributes' => false,
        ];

        try {
            // Use denormalize as we start with a PHP array, not a raw format string
            $dto = $this->serializer->denormalize(
                $data,
                $dtoClassName,
                null,
                $context
            );
        } catch (SerializerExceptionInterface $e) {
            throw $e;
        }

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            throw new SymfonyValidatorException($violations);
        }

        return $dto;
    }

    private function getRouteParams(ServerRequestInterface $request): array
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        if ($routeResult instanceof RouteResult) {
            return $routeResult->getMatchedParams();
        }
        return [];
    }
}