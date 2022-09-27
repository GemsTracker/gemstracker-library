<?php

namespace Gems\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Trans extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {}

    public function getFilters()
    {
        return [
            new TwigFilter('trans', [$this, 'translate']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('t', [$this, 'translate']),
        ];
    }

    public function translate(string $message): string
    {
        return $this->translator->trans($message);
    }
}