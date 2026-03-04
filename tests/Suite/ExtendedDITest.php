<?php

namespace Tests\Suite;

use Sterzik\DI\DI;
use Sterzik\DI\Exception\PrivateServiceException;

use Tests\DIServices\Test1;
use Tests\DIServices\Test2;

class ExtendedDITest extends AbstractTestCase
{
    public function testRootDefinition(): void
    {
        $config = [
            "\\" => fn ($builder) => $builder->setClass(Test1::class),
        ];
        $di = $this->createDI($config);
        $this->assertInstanceof(DI::class, $di);

        $test1 = $di->get("test1");
        $this->assertInstanceof(Test1::class, $test1);
        $test2 = $di->get("test2");
        $this->assertInstanceof(Test1::class, $test2);
        $this->assertNotSame($test1, $test2);
    }

    private function getPrivateServiceConfig(): array
    {
        return [
            '\\' => fn($builder) => $builder->setPublic(false),
            Test2::class => fn($builder) => $builder->setPublic(true),
        ];
    }

    public function testPrivateServiceAccess(): void
    {
        $di = $this->createDI($this->getPrivateServiceConfig());

        $test2 = $di->get(Test2::class);
        $this->assertInstanceof(Test2::class, $test2);

        $this->expectException(PrivateServiceException::class);
        $di->get(Test1::class);
    }

    public function testHasService(): void
    {
        $di = $this->createDI($this->getPrivateServiceConfig());
        $this->assertTrue($di->has(Test2::class));
        $this->assertFalse($di->has(Test1::class));
    }
}

