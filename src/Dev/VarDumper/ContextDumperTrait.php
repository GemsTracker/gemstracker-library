<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Dev\VarDumper
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Dev\VarDumper;

use Gems\Dev\VarDumper\ContextProvider\SourceContextProvider;

/**
 * @package    Gems
 * @subpackage Dev\VarDumper
 * @since      Class available since version 1.0
 */
trait ContextDumperTrait
{
    protected array $context = [];

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
                parent::echoLine($this->formatContext($contextString) , 0, '');
            }
        }
    }

    public function formatContext(string $contextString): string
    {
        return $contextString;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }
}