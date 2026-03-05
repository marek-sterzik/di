<?php

namespace Tests\Suite;

use Sterzik\DI\Path;
use Sterzik\DI\DI;
use Tests\DIServices\Test1;
use Tests\DIServices\TestValues;
use Sterzik\DI\Exception\ParametersImmutableException;
use Sterzik\DI\Exception\MissingParameterException;

class ParamsDITest extends AbstractTestCase
{
    public function testParams1(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder
                ->setArguments($builder->parameter("test"))
                ->call("addValue", $builder->parameter("test2")),
        ];

        $params = [
            "test" => "TEST",
            "test2" => "TST2",
        ];

        $di = $this->createDI($config, $params);

        $service = $di->get(TestValues::class);
        $this->assertInstanceof(TestValues::class, $service);
        $this->assertSame("TEST,TST2", $service->getValuesString());
    }

    public function testParams2(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder
                ->setArguments($builder->parameter("test"))
                ->call("addValue", $builder->parameter("test2")),
        ];

        $di = $this->createDI($config);

        $di->setParameters(["test" => "TST"]);
        $di->setParameter("test2", "TST2");

        $service = $di->get(TestValues::class);
        $this->assertInstanceof(TestValues::class, $service);
        $this->assertSame("TST,TST2", $service->getValuesString());
    }

    public function testImmutableParams1(): void
    {
        $di = $this->createDI();

        $di->setParameter("a", "a");

        $di->get(Test1::class);

        $this->expectException(ParametersImmutableException::class);

        $di->setParameter("b", "b");
    }

    public function testImmutableParams2(): void
    {
        $di = $this->createDI();

        $di->setParameter("a", "a");

        $di->freezeParameters();

        $this->expectException(ParametersImmutableException::class);

        $di->setParameter("b", "b");
    }

    public function testMissingParam(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder
                ->setArguments($builder->parameter("test")),
        ];

        $di = $this->createDI($config);

        $this->expectException(MissingParameterException::class);

        $di->get(TestValues::class);
    }

    public function testAppRoot(): void
    {
        $config = [
            TestValues::class => fn($builder) => $builder->setArguments($builder->getAppRoot()),
        ];
        $di = $this->createDI($config);

        $service = $di->get(TestValues::class);

        $this->assertSame(Path::getRoot(), $service->getValuesString());
    }

    public function testResolvePath(): void
    {
        $path = "config/data.xml";

        $config = [
            TestValues::class => fn($builder) => $builder->setArguments($builder->resolvePath($path)),
        ];
        $di = $this->createDI($config);

        $service = $di->get(TestValues::class);

        $this->assertSame(Path::resolve($path), $service->getValuesString());
    }
}
