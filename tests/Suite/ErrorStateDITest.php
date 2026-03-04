<?php

namespace Tests\Suite;

use Sterzik\DI\DI;
use Sterzik\DI\Exception\MissingConfigurationException;
use Sterzik\DI\Exception\InvalidConfigurationException;
use Sterzik\DI\Exception\CircularReferenceException;
use Sterzik\DI\Exception\ServiceDoesNotExistException;
use Tests\DIServices\Circular1;

class ErrorStateDITest extends AbstractTestCase
{
    public function testMissingConfigFile(): void
    {
        $this->expectException(MissingConfigurationException::class);
        $di = $this->createDI("file-not-found.php");
    }

    public function testInvalidConfigFile(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $di = $this->createDI("invalid.php");
    }

    public function testCircularReference(): void
    {
        $di = $this->createDI();
        $this->expectException(CircularReferenceException::class);
        $di->get(Circular1::class);
    }

    public function testServiceDoesNotExist(): void
    {
        $di = $this->createDI();
        $class = 'Tests\\DIServices\\ServiceDoesNotExistsClass';
        $this->assertFalse($di->has($class));
        $this->expectException(ServiceDoesNotExistException::class);
        $di->get($class);
    }
}
