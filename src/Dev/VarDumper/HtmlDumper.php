<?php

namespace Gems\Dev\VarDumper;

use Gems\Dev\VarDumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Cloner\Data;

class HtmlDumper extends \Symfony\Component\VarDumper\Dumper\HtmlDumper implements ContextDumperInterface
{
    protected array $context = [];

    public function dump(Data $data, $output = null, array $extraDisplayOptions = []): ?string
    {
        $this->echoContext();
        return parent::dump($data, $output, $extraDisplayOptions);
    }

    public function formatContextHeader(array $context): ?string
    {
        if (isset($context['class'], $context['function'], $context['line'])) {
            return sprintf('%s->%s: %d', $context['class'], $context['function'], $context['line']);
        }
        if (isset($context['class'], $context['line'])) {
            return sprintf('%s: %d', $context['class'], $context['line']);
        }
        if (isset($context['file'], $context['line'])) {
            return sprintf('%s: %d', $context['file'], $context['line']);
        }
        return null;
    }

    protected function echoContext(): void
    {
        if (isset($this->context[SourceContextProvider::class])) {
            $contextString = $this->formatContextHeader($this->context[SourceContextProvider::class]);
            if ($contextString !== null) {
                parent::echoLine($this->dumpPrefix . $contextString . $this->dumpSuffix, 0, '');
            }
        }
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}