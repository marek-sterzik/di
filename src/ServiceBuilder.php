<?php

namespace Sterzik\DI;

use ReflectionFunction;
use ReflectionMethod;
use ReflectionClass;

use Sterzik\DI\Exception\InvalidConfigurationException;
use Sterzik\DI\Exception\ServiceDoesNotExistException;

class ServiceBuilder
{
    private array $constructorArguments = [];
    private ?string $class = null;
    private mixed $factory = null;
    private array $postOperations = [];
    private bool $public = true;
    private bool $autowire = true;
    private bool $requireExplicitClass = false;
    private bool $exists = true;

    private mixed $service = null;
    private bool $serviceAvailable = false;

    public function __construct(private DI $di, private string $serviceName, private array $serviceDefinitions)
    {
    }

    public function build(): mixed
    {
        foreach ($this->serviceDefinitions as $serviceDefinition) {
            $serviceDefinition->applyToBuilder($this);
        }

        if (!$this->exists) {
            throw new ServiceDoesNotExistException(sprintf("Service %s does not exist", $this->serviceName));
        }

        if ($this->serviceAvailable) {
            return $this->service;
        }

        if ($this->factory !== null) {
            $factory = $this->factory;
            $reflectionFactory = new ReflectionFunction($factory);
            $arguments = ArgumentBuilder::buildArguments(
                $reflectionFactory,
                $this->di,
                $this->constructorArguments,
                $this->autowire
            );
            $service = $factory(...$arguments);
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

    public function getServiceName(): string
    {
        return $this->serviceName;
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
        $postOperations = $this->postOperations;
        foreach ($postOperations as &$operation) {
            if ($operation['autowire'] === null) {
                $operation['autowire'] = $this->autowire;
            }
        }

        return function ($service) use ($postOperations) {
            if (!is_object($service)) {
                throw new InvalidConfigurationException(
                    sprintf("Trying to call methods on a non-object (service %s)", $this->getServiceName())
                );
            }

            foreach ($postOperations as $postOperation) {
                $method = new ReflectionMethod($service, $postOperation['method']);
                $arguments = ArgumentBuilder::buildArguments(
                    $method,
                    $this->di,
                    $postOperation['arguments'],
                    $postOperation['autowire']
                );
                $method->invoke($service, ...$arguments);
            }
        };
    }

    public function has(string $serviceName): bool
    {
        return $this->di->has($serviceName);
    }

    public function get(string $serviceName): mixed
    {
        return $this->di->get($serviceName);
    }

    public function parameter(string $parameter): mixed
    {
        return $this->di->parameter($parameter);
    }

    public function getAppRoot(): string
    {
        return Path::getRoot();
    }

    public function resolvePath(string $path): string
    {
        return Path::resolve($path);
    }

    public function setExists(bool $exists = true): self
    {
        $this->exists = $exists;
        if (!$this->exists) {
            $this->class = null;
            $this->factory = null;
            $this->constructorArguments = [];
            $this->postOperations = [];
            $this->service = null;
            $this->serviceAvailable = false;
        }
        return $this;
    }

    public function setService(mixed $service): self
    {
        $this->service = $service;
        $this->serviceAvailable = true;
        $this->class = null;
        $this->factory = null;
        $this->constructorArguments = [];
        $this->exists = true;
        return $this;
    }

    public function setClass(string $class): self
    {
        $this->class = $class;
        $this->factory = null;
        $this->service = null;
        $this->serviceAvailable = false;
        $this->exists = true;
        return $this;
    }

    public function setFactory(callable $factory): self
    {
        $this->factory = $factory;
        $this->class = null;
        $this->service = null;
        $this->serviceAvailable = false;
        $this->exists = true;
        return $this;
    }

    public function resetArguments(): self
    {
        return $this->putArguments([], true);
    }

    public function setArguments(...$arguments): self
    {
        return $this->putArguments($arguments, false);
    }

    public function putArguments(array $arguments, bool $resetArguments = false): self
    {
        $this->service = null;
        $this->serviceAvailable = false;
        $this->exists = true;

        if ($resetArguments) {
            $this->constructorArguments = [];
        }
        foreach ($arguments as $index => $value) {
            $this->constructorArguments[$index] = $value;
        }
        return $this;
    }

    public function setArgument(string|int $index, mixed $value): self
    {
        return $this->putArguments([$index => $value], false);
    }

    public function call(string $method, ...$arguments): self
    {
        return $this->callArguments($method, $arguments);
    }

    public function callArguments(string $method, array $arguments, ?bool $autowire = null): self
    {
        $this->exists = true;

        $this->postOperations[] = [
            "method" => $method,
            "arguments" => $arguments,
            "autowire" => $autowire,
        ];
        return $this;
    }

    public function setPublic(bool $public = true): self
    {
        $this->exists = true;
        $this->public = $public;
        return $this;
    }

    public function setAutowire(bool $autowire = true): self
    {
        $this->exists = true;
        $this->autowire = $autowire;
        return $this;
    }

    public function setRequireExplicitClass(bool $requireExplicitClass = true): self
    {
        $this->exists = true;
        $this->requireExplicitClass = $requireExplicitClass;
        return $this;
    }
}
