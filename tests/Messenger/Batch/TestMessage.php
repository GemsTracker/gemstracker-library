<?php

namespace GemsTest\Messenger\Batch;

class TestMessage
{
    public function __construct(
        public readonly string $name,
    )
    {
    }
}