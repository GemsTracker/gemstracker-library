<?php

namespace Gems\Communication\Handler;

use Gems\Communication\Unsubscribe\Messenger\Message\SubscriptionInfo;
use Gems\Request\MapRequest;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class UnsubscribeHandler implements RequestHandlerInterface
{
    private readonly int $unsubscribeValue;

    public function __construct(
        private readonly MapRequest $mapRequest,
        private readonly MessageBusInterface $messageBus,
        array $config,
    )
    {
        $this->unsubscribeValue = $config['communication']['unsubscribe']['unsubscribeValue'] ?? 0;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Subscription value should be 0 on unsubscribe
        $parsedBody = json_decode($request->getBody()->getContents(), true);
        $parsedBody['subscriptionValue'] = $this->unsubscribeValue;

        /** @var SubscriptionInfo $info */
        $info = $this->mapRequest->mapDtoFromArray($parsedBody, SubscriptionInfo::class, false);

        $this->messageBus->dispatch($info);

        return new EmptyResponse(202);
    }
}