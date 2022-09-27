<?php

namespace Gems\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Trans extends AbstractExtension
{
    public function __construct(private ContainerInterface $container)
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

    protected function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    public function translate(string $message): string
    {
        $translator = $this->getTranslator();
        return $translator->trans($message);
    }
}