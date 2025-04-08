<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Dev\VarDumper
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Dev\VarDumper;

use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * @package    Gems
 * @subpackage Dev\VarDumper
 * @since      Class available since version 1.0
 */
class FileDumper extends CliDumper implements ContextDumperInterface
{
    use ContextDumperTrait;

    public function dump(Data $data, $output = null): ?string
    {
        $this->echoContext();
        return parent::dump($data, $output);
    }

    public function setOutput($output)
    {
        $prev = $this->outputStream ?? $this->lineDumper;

        if (\is_callable($output)) {
            $this->outputStream = null;
            $this->lineDumper = $output;
        } else {
            if (\is_string($output)) {
                $output = fopen($output, 'a');
            }
            $this->outputStream = $output;
            $this->lineDumper = $this->echoLine(...);
        }

        return $prev;
    }
}