<?php

namespace Gems\Twig;

use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Csrf extends AbstractExtension
{
    public function __construct()
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('csrf', [$this, 'csrfTag'], [
                'needs_environment' => true,
                'needs_context' => true,
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function csrfTag(\Twig\Environment $env, $context): string
    {
        /** @var CsrfGuardInterface $csrf */
        $csrf = $context[CsrfMiddleware::GUARD_ATTRIBUTE] ?? throw new \Exception('csrf() requires ' . CsrfMiddleware::class);

        $token = twig_escape_filter($env, $csrf->generateToken(), 'html_attr');

        return '<input type="hidden" name="__csrf" value="' . $token . '" />';
    }
}
