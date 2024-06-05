<?php

namespace GemsTest\testUtils;

class TestCase extends \PHPUnit\Framework\TestCase
{
    use TestTraitsInitTrait;
    public function setUp(): void
    {
        parent::setUp();
        $this->setupTestTraits();
    }

    public function tearDown(): void
    {
        $this->tearDownTestTraits();

        parent::tearDown();
    }
}