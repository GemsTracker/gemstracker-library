<?php

namespace Gems\Communication\Handler;

use Gems\Communication\Unsubscribe\Messenger\Message\SubscriptionInfo;
use Gems\Exception\SymfonyValidatorException;
use Gems\Request\MapRequest;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerExceptionInterface;

class UnsubscribeHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly MapRequest $mapRequest,
        private readonly MessageBusInterface $messageBus,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Subscription value should be 0 on unsubscribe
        $parsedBody = json_decode($request->getBody()->getContents(), true);
        if (isset($parsedBody['subscriptionValue']) && $parsedBody['subscriptionValue'] !== 0) {
            return new EmptyResponse(400);
        }

        /** @var SubscriptionInfo $info */
        $info = $this->mapRequest->mapRequestBody($request, SubscriptionInfo::class, false);
        $this->messageBus->dispatch($info);

        return new EmptyResponse(202);
    }
}