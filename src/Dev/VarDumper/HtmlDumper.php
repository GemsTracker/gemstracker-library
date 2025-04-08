<?php

namespace Gems\Dev\VarDumper;

use Gems\Dev\VarDumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Cloner\Data;

class HtmlDumper extends \Symfony\Component\VarDumper\Dumper\HtmlDumper implements ContextDumperInterface
{
    use ContextDumperTrait;

    public function dump(Data $data, $output = null, array $extraDisplayOptions = []): ?string
    {
        $this->echoContext();
        return parent::dump($data, $output, $extraDisplayOptions);
    }

    public function formatContext(string $contextString): string
    {
        return$this->dumpPrefix . $contextString . $this->dumpSuffix;
    }
}