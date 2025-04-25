<?php

declare(strict_types=1);

namespace Gems\Communication\Handler;

use Gems\Api\Util\ContentTypeChecker;
use Gems\Request\MapRequest;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestCommunicationEmailHandler implements RequestHandlerInterface
{
    /**
     * @var array List of allowed content types as input for write methods
     */
    protected array $allowedContentTypes = ['application/json'];

    protected ContentTypeChecker $contentTypeChecker;

    public function __construct(
        protected readonly MapRequest $mapRequest
    )
    {
        $this->contentTypeChecker = new ContentTypeChecker($this->allowedContentTypes);
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->contentTypeChecker->checkContentType($request) === false) {
            return new EmptyResponse(415);
        }

        $this->mapRequest->mapRequestBody($request, TestCommunicationEmailParams::class);


    }
}