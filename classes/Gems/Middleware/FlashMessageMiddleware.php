<?php

namespace Gems\Middleware;

use Mezzio\Flash\Exception\InvalidFlashMessagesImplementationException;
use Mezzio\Flash\Exception\MissingSessionException;
use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Message\MezzioFlashMessenger;
use Zalt\Message\MezzioSessionMessenger;
use Zalt\Message\StatusMessengerInterface;

class FlashMessageMiddleware implements MiddlewareInterface
{

    public const FLASH_ATTRIBUTE = 'flash';

    public const STATUS_MESSENGER_ATTRIBUTE = StatusMessengerInterface::class;

    /** @psalm-var callable(SessionInterface, string): FlashMessagesInterface */
    private $flashMessageFactory;

    public function __construct(
        string $flashMessagesClass = FlashMessages::class,
        protected string $sessionKey = FlashMessagesInterface::FLASH_NEXT,
        protected string $attributeKey = self::FLASH_ATTRIBUTE,
        protected string $statusMessengerAttributeKey = self::STATUS_MESSENGER_ATTRIBUTE
    ) {
        $factory = [$flashMessagesClass, 'createFromSession'];
        if (! is_callable($factory)) {
            throw InvalidFlashMessagesImplementationException::forClass($flashMessagesClass);
        }

        $this->flashMessageFactory = $factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE, false);
        if (! $session instanceof SessionInterface) {
            throw MissingSessionException::forMiddleware($this);
        }

        $flashMessages = ($this->flashMessageFactory)($session, $this->sessionKey);

        $statusMessenger = new MezzioSessionMessenger($session);

        return $handler->handle(
            $request
                ->withAttribute($this->attributeKey, $flashMessages)
                ->withAttribute($this->statusMessengerAttributeKey, $statusMessenger)
        );
    }
}