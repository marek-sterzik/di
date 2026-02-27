<?php

namespace Sterzik\DI;

use ReflectionFunction;
use ReflectionClass;

class ServiceBuilder
{
    private $constructorArguments = [];
    private ?string $class = null;
    private mixed $factory = null;
    private array $postOperations = [];

    private mixed $service = null;
    private bool $serviceAvailable = false;

    public function __construct(private DI $di, private string $serviceName, private array $serviceDefinitions)
    {
    }

    public function build(): mixed
    {
        foreach ($this->serviceDefinitions as $serviceDefinition) {
            $this->applyServiceDefinition($serviceDefinition);
        }

        if ($this->serviceAvailable) {
            if (!empty($this->constructorArguments)) {
                throw new Exception("Using constructor arguments is not compatible with direct service setting.");
            }
            if ($this->class !== null) {
                throw new Exception("Setting class is not compatible with direct service setting.");
            }
            if ($this->factory !== null) {
                throw new Exception("Setting factory is not compatible with direct service setting.");
            }
            if (!empty($this->postOperations)) {
                throw new Exception("Setting custom calls is not compatible with direct service setting.");
            }
            return $this->service;
        }

        if ($this->factory !== null) {
            $factory = $this->factory;
            $reflectionFactory = new ReflectionFunction($factory);
            $arguments = ArgumentBuilder::buildArguments(
                $reflectionFactory,
                $reflectionFactory->getName(),
                $di,
                $this->constructorArguments
            );
            $service = $factory(...$arguments);
            if ($this->class !== null && !is_a($service, $this->class)) {
                throw new Exception(sprintf("Service %s is not of class %s.", $this->serviceName, $this->class));
            }
        } else {
            $class = $this->class ?? $this->serviceName;
            if (!class_exists($class)) {
                throw new Exception(sprintf("Service %s does not correspond to any valid class", $this->serviceName));
            }
            $reflectionClass = new ReflectionClass($class);
            $constructor = $reflectionClass->getConstructor();
            if ($constructor !== null) {
                $arguments = ArgumentBuilder::buildArguments(
                    $constructor,
                    $constructor->getName(),
                    $di,
                    $this->constructorArguments
                );
            } else {
                $arguments = [];
            }

            return new $class(...$arguments);
        }
    }

    private function applyServiceDefinition(mixed $serviceDefinition): self
    {
        if (!is_callable($serviceDefinition)) {
            $ret = $serviceDefinition($this) ?? $this;
            if ($ret !== $this) {
                $this->setService($ret);
            }
        } else if(is_array($serviceDefinition)) {
            throw new Exception("Array service definitons are not yet supported.");
        } else {
            $this->setService($serviceDefinition);
        }
        return $this;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getPostOperation(): ?callable
    {
        if (empty($this->postOperations)) {
            return null;
        }
        throw new Exception("Post operations are not yet supported");
    }

    public function has(string $serviceName): bool
    {
        return $this->di->has($serviceName);
    }

    public function get(string $serviceName): mixed
    {
        return $this->di->get($serviceName);
    }

    public function setService(mixed $service): self
    {
        $this->service = $service;
        $this->serviceAvailable = true;
        return $this;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;
        return $this;
    }

    public function setFactory(callable $factory): self
    {
        $this->factory $factory;
        return $this;
    }

    public function arguments(array $arguments): self
    {
        $this->constructorArguments = array_merge($this->constructorArguments, $arguments);
        return $this;
    }

    public function argument(string|int $index, mixed $value): self
    {
        return $this->arguments([$index => $value]);
    }
}
