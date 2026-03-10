<?php

namespace Tests\Suite;

use PHPUnit\Framework\Attributes\DataProvider;

use Sterzik\DI\DI;
use Sterzik\DI\Exception\MissingConfigurationException;
use Sterzik\DI\Exception\InvalidConfigurationException;
use Sterzik\DI\Exception\CircularReferenceException;
use Sterzik\DI\Exception\ServiceDoesNotExistException;
use Sterzik\DI\Exception\NotImplementedException;
use Tests\DIServices\Circular1;
use Tests\DIServices\Factory;
use Tests\DIServices\Test1;
use Tests\DIServices\Test2;
use Tests\DIServices\TestValues;

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

    public function testCreationWithoutAutowire(): void
    {
        $config = [
            '\\' => fn($builder) => $builder->setAutowire(false),
        ];

        $di = $this->createDI($config);

        $this->expectException(InvalidConfigurationException::class);

        $di->get(Test2::class);
    }

    public function testCallOnNonObject(): void
    {
        $config = [
            'object' => fn($builder) => $builder->setService("justString")->call("someMethod"),
        ];

        $di = $this->createDI($config);
        $this->expectException(InvalidConfigurationException::class);
        $di->get("object");
    }

    public function testServiceWithArray(): void
    {
        $config = [
            "explicit" => [
                "def" => 1,
            ],
        ];

        $di = $this->createDI($config);
        $this->expectException(NotImplementedException::class);
        $di->get("explicit");
    }

    public function testArgumentBadAutowiring1(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder
                ->setArguments("c")
                ->call("addAllValues", arg: "A")
        ];

        $di = $this->createDI($config);

        $this->expectException(InvalidConfigurationException::class);
        $di->get(TestValues::class);
    }

    public function testArgumentBadAutowiring2(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder
                ->setArguments("c")
                ->call("addValueVariadic", "z", "z1", values: "z2")
        ];

        $di = $this->createDI($config);

        $this->expectException(InvalidConfigurationException::class);
        $di->get(TestValues::class);
    }

    public function testInvalidFactory(): void
    {
        $config = [
            Test1::class => fn($builder) => $builder->setFactory([$builder->get(Factory::class), "createTest1", "invalid"]),
        ];

        $di = $this->createDI($config);

        $this->expectException(InvalidConfigurationException::class);
        $di->get(Test1::class);
    }
}
