<?php

namespace Tests\Suite;

use Sterzik\DI\DI;
use Sterzik\DI\Exception\PrivateServiceException;
use Sterzik\DI\Exception\ServiceDoesNotExistException;

use Tests\DIServices\Test1;
use Tests\DIServices\Test2;
use Tests\DIServices\TestValues;
use Tests\DIServices\C1;
use Tests\DIServices\C2;

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

    public function testDirectServiceSetting(): void
    {
        $test = new Test1();
        $config = [
            "test1" => fn($builder) => $builder->setService($test),
            "test2" => fn($builder) => $test,
        ];
        $di = $this->createDI($config);
        $test1 = $di->get("test1");
        $test2 = $di->get("test2");
        $this->assertSame($test, $test1);
        $this->assertSame($test, $test2);
    }

    public function testFactoryWithNoArgs(): void
    {
        $factory = function() {
            return new Test1();
        };

        $config = [
            "test1" => fn($builder) => $builder->setFactory($factory),
        ];

        $di = $this->createDI($config);

        $test1 = $di->get("test1");

        $this->assertInstanceof(Test1::class, $test1);
    }

    public function testFactoryWithExplicitArg(): void
    {
        $factory = function(string $value) {
            return new TestValues($value);
        };

        $config1 = [
            "\\" => fn($builder) => $builder->setFactory($factory),
            "test1" => fn($builder) => $builder->putArguments(["test1"]),
            "test2" => fn($builder) => $builder->setArguments("test2"),
        ];
        $config2 = [
            "\\" => fn($builder) => $builder->setFactory($factory)->setArguments($builder->getServiceName()),
        ];

        foreach ([$config1, $config2] as $config) {
            $di = $this->createDI($config);

            $test1 = $di->get("test1");
            $test2 = $di->get("test2");

            $this->assertInstanceof(TestValues::class, $test1);
            $this->assertInstanceof(TestValues::class, $test2);

            $this->assertSame("test1", $test1->getValuesString());
            $this->assertSame("test2", $test2->getValuesString());
        }
    }

    public function testFactoryWithAutowireArg(): void
    {
        $lastFactoriedObject = null;
        $lastFactoryArgument = null;
        $factory = function(Test1 $value) use (&$lastFactoriedObject, &$lastFactoryArgument){
            $lastFactoryArgument = $value;
            $lastFactoriedObject = new Test2($value);
            return $lastFactoriedObject;
        };

        $config = [
            Test2::class => fn($builder) => $builder->setFactory($factory),
        ];

        $di = $this->createDI($config);

        $test1 = $di->get(Test1::class);
        $test2 = $di->get(Test2::class);

        $this->assertSame($test1, $lastFactoryArgument);
        $this->assertSame($test2, $lastFactoriedObject);
        $this->assertSame($test1, $test2->getTest1());
    }

    public function testBuilderGetAndHas(): void
    {
        $config = [
            'test1' => fn($builder) => $builder->get(Test1::class),
            'test1exists' => fn($builder) => $builder->has("test1"),
        ];

        $di = $this->createDI($config);

        $test1 = $di->get("test1");
        $this->assertSame($di->get(Test1::class), $test1);
        $this->assertSame($di->get("test1exists"), true);
    }

    public function testRequireExplicitClass(): void
    {
        $config = [
            '\\' => fn($builder) => $builder->setRequireExplicitClass(true),
            Test1::class => fn($builder) => $builder->setClass(Test1::class),
        ];

        $di = $this->createDI($config);

        $test1 = $di->get(Test1::class);
        $this->assertInstanceof(Test1::class, $test1);
        $this->expectException(ServiceDoesNotExistException::class);
        $di->get(Test2::class);
    }

    public function testCall(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder->setArguments("test")->call("addValue", "v1")->call("addValue", "v2"),
        ];

        $di = $this->createDI($config);

        $testValues = $di->get(TestValues::class);

        $this->assertInstanceof(TestValues::class, $testValues);
        $this->assertSame("test,v1,v2", $testValues->getValuesString());
    }

    public function testCircularWithAutowire(): void
    {
        $config = [
            C1::class => fn($builder) => $builder->call("setC2"),
            C2::class => fn($builder) => $builder->call("setC1"),
        ];

        $di = $this->createDI($config);

        $c1 = $di->get(C1::class);
        $c2 = $di->get(C2::class);
        $this->assertInstanceof(C1::class, $c1);
        $this->assertInstanceof(C2::class, $c2);
        $this->assertInstanceof(C2::class, $c1->getC2());
        $this->assertInstanceof(C1::class, $c2->getC1());
        $this->assertSame($c1, $c2->getC1());
        $this->assertSame($c2, $c1->getC2());
    }

    public function testArgumentAutowiring(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder
                ->setArguments("c")
                ->call("addValue", "v1")
                ->call("addValueImplicit", "v2")
                ->call("addValueImplicit")
                ->call("addValueNull", "v3")
                ->call("addValueNull")
                ->call("addValueVariadic", "x")
                ->call("addValueVariadic", "y", "y1", "y2")
                ->call("addValueVariadic", "z", values: "z1")
                ->call("addAllValues", "A", "B", "C")
        ];

        $di = $this->createDI($config);

        $testValues = $di->get(TestValues::class);
        $this->assertInstanceof(TestValues::class, $testValues);
        $this->assertSame("c,v1,v2,value,string,v3,null,x,y,y1,y2,z,z1,A,B,C", $testValues->getValuesString());
    }

    public function testExists(): void
    {
        $config = [
            Test2::class => fn($builder) => $builder->setExists(false),
        ];

        $di = $this->createDI($config);

        $test1 = $di->get(Test1::class);
        $this->assertInstanceof(Test1::class, $test1);
        $this->assertFalse($di->has(Test2::class));
    }

    public function testResetArguments(): void
    {
        $config = [
            "test1" => fn($builder) => $builder->setClass(TestValues::class)->setArguments("a", "b", "c")->resetArguments()->setArguments("d"),
        ];

        $di = $this->createDI($config);

        $test1 = $di->get("test1");

        $this->assertSame("d", $test1->getValuesString());
    }
}

