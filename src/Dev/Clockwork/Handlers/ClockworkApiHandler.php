<?php

namespace Gems\Dev\Clockwork\Handlers;

use Clockwork\Clockwork;
use Clockwork\Storage\StorageInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClockworkApiHandler implements RequestHandlerInterface
{

    public function __construct(
        protected readonly Clockwork $clockwork,
    )
    {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $direction = $request->getAttribute('direction');
        $count = $request->getAttribute('count');

        /**
         * @var StorageInterface $storage
         */
        $storage = $this->clockwork->getStorage();

        if ($direction === 'previous') {
            return new JsonResponse($storage->previous($id, $count));
        }
        if ($direction === 'next') {
            return new JsonResponse($storage->next($id, $count));
        }

        if ($id === 'latest') {
            return new JsonResponse($storage->latest());
        }
        return new JsonResponse($storage->find($id));
    }
}