<?php

namespace Gems\Dev\VarDumper;

interface ContextDumperInterface
{
    public function setContext(array $context): void;
}