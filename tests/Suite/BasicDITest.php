<?php

namespace Tests\Suite;

use Exception;
use Sterzik\DI\DI;
use Sterzik\DI\Exception\InvalidConfigurationException;
use Tests\DIServices\Test1;
use Tests\DIServices\Test2;
use Tests\DIServices\Test3;

class BasicDITest extends AbstractTestCase
{
    public function testBasicDI(): void
    {
        $di = $this->createDI();
        $this->assertInstanceof(DI::class, $di);
        $test1 = $di->get(Test1::class);
        $this->assertInstanceof(Test1::class, $test1);
        $this->assertSame(Test1::class, $test1->value());

        $test2 = $di->get(Test2::class);
        $this->assertInstanceof(Test2::class, $test2);
        $this->assertSame(Test2::class, $test2->value());
        $this->assertSame(Test1::class, $test2->subValue());
    }

    public function testExplicitArguments(): void
    {
        $config = [
            Test3::class => fn($builder) => $builder->setArgument("value", "test3"),
        ];
        $di = $this->createDI($config);
        $this->assertInstanceof(DI::class, $di);

        $test3 = $di->get(Test3::class);
        $this->assertInstanceof(Test3::class, $test3);

    }

    public function testExplicitArgumentsPositional(): void
    {
        $config = [
            Test3::class => fn($builder) => $builder->setArgument(2, "test3"),
        ];
        $di = $this->createDI($config);
        $this->assertInstanceof(DI::class, $di);

        $test3 = $di->get(Test3::class);
        $this->assertInstanceof(Test3::class, $test3);

    }

    public function testExplicitArgumentsMissingDefinition(): void
    {
        $di = $this->createDI();
        $this->assertInstanceof(DI::class, $di);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/value.*Test3.*autowire/');
        $test3 = $di->get(Test3::class);

    }

    public function testConfigFileLoading(): void
    {
        $di = $this->createDI("test.php");
        $this->assertInstanceof(DI::class, $di);

        $test1 = $di->get("test");
        $this->assertInstanceof(Test1::class, $test1);
    }

    public function testExplicitService(): void
    {
        $config = [
            "explicit" => "explicitString",
        ];

        $di = $this->createDI($config);
        $value = $di->get("explicit");
        $this->assertSame("explicitString", $value);
    }
}
