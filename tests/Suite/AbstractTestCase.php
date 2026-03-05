<?php

namespace Tests\Suite;

use PHPUnit\Framework\TestCase;
use Sterzik\DI\DI;

use Tests\Helper;

class AbstractTestCase extends TestCase
{
    protected function createDI(array|string $definitions = [], array $params = []): DI
    {
        if (is_string($definitions)) {
            $definitions = TEST_CONFIG_DIR . "/" . $definitions;
        }
        return new DI($definitions, $params);
    }

    protected function resetGlobalDIInstance(): void
    {
        Helper::setClassProperty(DI::class, "serviceDefinitions", null);
        Helper::setClassProperty(DI::class, "instance", null);
    }
}
