<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.9.2
 */
class ModelSnippetHandler implements RequestHandlerInterface
{
    public function __construct(protected SnippetResponderInterface $responder) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        
        return $this->responder->getSnippetsResponse(['InfoSnippet'], ['check' => 'Testing!'], $request);
    }
}