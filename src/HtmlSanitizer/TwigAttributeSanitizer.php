<?php

declare(strict_types=1);

namespace Gems\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

class TwigAttributeSanitizer implements AttributeSanitizerInterface
{
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
        if (str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
            return $value;
        }

        return null;
    }
}