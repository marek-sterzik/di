<?php

namespace Sterzik\DI;

use ReflectionFunction;
use ReflectionClass;

use Sterzik\DI\Exception\InvalidConfigurationException;
use Sterzik\DI\Exception\NotImplementedException;
use Sterzik\DI\Exception\ServiceDoesNotExistException;

class ServiceBuilder
{
    private $constructorArguments = [];
    private ?string $class = null;
    private mixed $factory = null;
    private array $postOperations = [];
    private bool $public = true;
    private bool $autowire = true;
    private bool $requireExplicitClass = false;

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
                throw new InvalidConfigurationException("Using constructor arguments is not compatible with direct service setting.");
            }
            if ($this->class !== null) {
                throw new InvalidConfigurationException("Setting class is not compatible with direct service setting.");
            }
            if ($this->factory !== null) {
                throw new InvalidConfigurationException("Setting factory is not compatible with direct service setting.");
            }
            if (!empty($this->postOperations)) {
                throw new InvalidConfigurationException("Setting custom calls is not compatible with direct service setting.");
            }
            return $this->service;
        }

        if ($this->factory !== null) {
            $factory = $this->factory;
            $reflectionFactory = new ReflectionFunction($factory);
            $arguments = ArgumentBuilder::buildArguments(
                $reflectionFactory,
                $di,
                $this->constructorArguments,
                $this->autowire
            );
            $service = $factory(...$arguments);
            if ($this->class !== null && !is_a($service, $this->class)) {
                throw new InvalidConfigurationException(sprintf("Service %s is not of class %s.", $this->serviceName, $this->class));
            }
            return $service;
        } else {
            if ($this->requireExplicitClass && $this->class === null) {
                throw new ServiceDoesNotExistException(sprintf("Service %s does not have an explicit class set", $this->serviceName));
            }
            $class = $this->class ?? $this->serviceName;

            if (!class_exists($class)) {
                throw new ServiceDoesNotExistException(sprintf("Service %s does not correspond to any valid class", $this->serviceName));
            }
            $reflectionClass = new ReflectionClass($class);
            $constructor = $reflectionClass->getConstructor();
            if ($constructor !== null) {
                $arguments = ArgumentBuilder::buildArguments(
                    $constructor,
                    $this->di,
                    $this->constructorArguments,
                    $this->autowire
                );
            } else {
                $arguments = [];
            }
            return new $class(...$arguments);
        }
    }

    private function applyServiceDefinition(mixed $serviceDefinition): self
    {
        if (is_callable($serviceDefinition)) {
            $ret = $serviceDefinition($this) ?? $this;
            if ($ret !== $this) {
                $this->setService($ret);
            }
        } else if(is_array($serviceDefinition)) {
            throw new NotImplementedException("Array service definitons are not yet supported.");
        } else {
            $this->setService($serviceDefinition);
        }
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getPostOperation(): ?callable
    {
        if (empty($this->postOperations)) {
            return null;
        }
        throw new NotImplementedException("Post operations are not yet supported");
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
        $this->factory = $factory;
        return $this;
    }

    public function setArguments(array $arguments): self
    {
        foreach ($arguments as $index => $value) {
            $this->constructorArguments[$index] = $value;
        }
        return $this;
    }

    public function setArgument(string|int $index, mixed $value): self
    {
        return $this->setArguments([$index => $value]);
    }

    public function setPublic(bool $public = true): self
    {
        $this->public = $public;
        return $this;
    }

    public function setAutowire(bool $autowire = true): self
    {
        $this->autowire = $autowire;
    }

    public function setRequireExplicitClass(bool $requireExplicitClass = true): self
    {
        $this->requireExplicitClass = $requireExplicitClass;
    }
}
