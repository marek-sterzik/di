<?php

namespace Tests\Suite;

use PHPUnit\Framework\TestCase;
use Sterzik\DI\DI;

class AbstractTestCase extends TestCase
{
    protected function createDI(array|string $definitions = [], bool $publicService = true): DI
    {
        if (is_string($definitions)) {
            $definitions = TEST_CONFIG_DIR . "/" . $definitions;
        }
        return new DI($definitions, $publicService);
    }
}
