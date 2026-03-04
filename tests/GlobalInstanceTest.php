<?php

namespace Tests\Suite;

use Exception;
use Sterzik\DI\DI;
use Tests\Helper;
use Sterzik\DI\Exception\InvalidConfigurationException;

class GlobalInstanceTest extends AbstractTestCase
{
    private function useConstant(bool $use): void
    {
        if ($use) {
            if (!defined("DI_SERVICE_DEFINITIONS")) {
                define("DI_SERVICE_DEFINITIONS", DI_SERVICE_DEFINITIONS_TEMPLATE);
            }
        } else {
            if (defined("DI_SERVICE_DEFINITIONS")) {
                throw new Exception("DI_SERVICE_DEFINITIONS already defined");
            }
        }
        $this->resetGlobalDIInstance();
    }

    public function testBasicGlobalInstance(): void
    {
        $this->useConstant(false);
        $di = DI::instance();
        $definitions = Helper::getObjectProperty($di, "definitions");
        $this->assertSame([], $definitions);
    }

    public function testBasicGlobalInstanceUsingConstant(): void
    {
        $this->useConstant(true);
        $di = DI::instance();
        $definitions = Helper::getObjectProperty($di, "definitions");
        $this->assertNotSame([], $definitions);
        $this->assertSame($di->get("global"), "globalTest");
    }

    public function testBasicGlobalInstanceUsingMethod(): void
    {
        $this->useConstant(true);
        DI::setServiceDefinitions(["global" => "globalTest2"]);
        $di = DI::instance();
        $definitions = Helper::getObjectProperty($di, "definitions");
        $this->assertNotSame([], $definitions);
        $this->assertSame($di->get("global"), "globalTest2");

        $this->expectException(InvalidConfigurationException::class);
        DI::setServiceDefinitions([]);
    }
}
