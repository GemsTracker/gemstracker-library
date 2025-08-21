<?php

declare(strict_types=1);

namespace Gems\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\UrlAttributeSanitizer;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Loader\ArrayLoader;
use Twig\Node\PrintNode;
use Twig\Source;

class TwigAttributeSanitizer implements AttributeSanitizerInterface
{
    public function __construct(
        private Environment|null $twig = null,
    )
    {
    }

    public function getSupportedElements(): ?array
    {
        return ['a'];
    }

    public function getSupportedAttributes(): ?array
    {
        return ['href'];
    }

    public function sanitizeAttribute(
        string $element,
        string $attribute,
        string $value,
        HtmlSanitizerConfig $config
    ): ?string {

        $this->initTwigEnvironment();

        if ($this->isTwigPrintTag($value)) {
            return $value;
        }

        // Handle as normal url attribute. UrlAttributeSanitizer is final. UrlSanitizer is internal,
        // so we can't extend or just do the UrlSanitizing directly
        $urlAttributeSanitizer = new UrlAttributeSanitizer();
        return $urlAttributeSanitizer->sanitizeAttribute($element, $attribute, $value, $config);
    }

    // Only init twig once when it is not set on construct, and only when we actually need it
    private function initTwigEnvironment(): void
    {
        if ($this->twig === null) {
            $loader = new ArrayLoader();
            $this->twig = new Environment($loader, []);
        }
    }

    private function isTwigPrintTag(string $value): bool
    {
        try {
            $source = new Source($value, 'inline_template');
            $stream = $this->twig->tokenize($source);
            $nodes = $this->twig->parse($stream);
        } catch (Error) {
            return false;
        }

        $body = $nodes->getNode('body');

        if ($body->count() !== 1) {
            return false;
        }

        $firstNode = $body->getNode('0');
        if ($firstNode instanceof PrintNode) {
            return true;
        }
        return false;
    }
}