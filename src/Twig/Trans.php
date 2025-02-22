<?php

namespace Gems\Twig;

use Psr\Container\ContainerInterface;
// use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Zalt\Base\SymfonyTranslator;
use Zalt\Base\TranslatorInterface;

class Trans extends AbstractExtension
{
    protected readonly TranslatorInterface $translator;

    public function __construct(
        private ContainerInterface $container,
    )
    {
        $this->translator = $this->container->get(TranslatorInterface::class);
    }

    public function getFilters()
    {
        return [
            new TwigFilter('trans', [$this, 'translate']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('trans', [$this, 'translate']),
        ];
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function translate(string $message): string
    {
        return $this->translator->trans($message);
    }
}