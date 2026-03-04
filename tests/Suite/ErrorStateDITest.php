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
use Tests\DIServices\Test1;
use Tests\DIServices\Test2;

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

    private static function getInvalidServiceConfigurations(): array
    {
        $service = new Test1();
        return [
            "test1" => fn($builder) => $builder->setService($service)->setClass(Test1::class),
            "test2" => fn($builder) => $builder->setService($service)->setArguments(["a"]),
            "test3" => fn($builder) => $builder->setService($service)->setFactory(fn() => $service),
        ];
    }

    public static function provideInvalidServiceConfigurationIds(): array
    {
        return array_map(fn ($x) => [$x], array_keys(self::getInvalidServiceConfigurations()));
    }

    #[DataProvider("provideInvalidServiceConfigurationIds")]
    public function testInvalidServiceConfigurations(string $service): void
    {
        $di = $this->createDI(self::getInvalidServiceConfigurations());
        $this->expectException(InvalidConfigurationException::class);
        $di->get($service);
    }

    public function testFactoryWithClassBuilder(): void
    {
        $factory = function() {
            return new Test1();
        };

        $config = [
            "test1" => fn($builder) => $builder->setFactory($factory)->setClass(Test1::class),
            "test2" => fn($builder) => $builder->setFactory($factory)->setClass(Test2::class),
        ];

        $di = $this->createDI($config);

        $this->assertInstanceof(Test1::class, $di->get("test1"));
        $this->expectException(InvalidConfigurationException::class);
        $di->get("test2");
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
}
