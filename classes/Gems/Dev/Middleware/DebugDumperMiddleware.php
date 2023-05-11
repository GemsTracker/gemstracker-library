<?php

namespace Gems\Dev\Middleware;

use Gems\Dev\VarDumper\ContextProvider\SourceContextProvider;
use Gems\Dev\VarDumper\HtmlDumper;
use Gems\Dev\VarDumper\ContextualizedDumper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Stream;
use MUtil\EchoOut\EchoOut;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\VarDumper;

class DebugDumperMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cloner = new VarCloner();
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        if (!\in_array(\PHP_SAPI, ['cli', 'phpdbg'], true)) {

            $output = fopen('php://memory', 'r+b');

            $htmlDumper = new HtmlDumper($output);
            $htmlDumper->setDisplayOptions([
                'fileLinkFormat' => '%f - %l',
            ]);
            $dumper = new ContextualizedDumper($htmlDumper, [new SourceContextProvider()]);
            VarDumper::setHandler(function ($var) use ($cloner, &$dumper) {
                $dumper->dump($cloner->cloneVar($var));
            });
        }

        $response = $handler->handle($request);

        if (!$response instanceof HtmlResponse) {
            return $response;
        }

        $html = $response->getBody()->getContents();
        $html .= EchoOut::out();
        $html .= $debugOutput = stream_get_contents($output,-1, 0);

        $body = new Stream('php://temp', 'wb+');
        $body->write($html);
        $body->rewind();

        return $response->withBody($body);


    }
}