<?php

namespace Sterzik\DI;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use ReflectionNamedType;

class DI
{
    private static ?self $instance = null;

    private array $definitions = [];
    private array $services = [];
    private array $recursionProtection = [];
    private array $postOperations = [];
    private array $publicServices = [];

    public function __construct(string|array $serviceDefinitions, private bool $publicContainer = true)
    {
        if (is_string($serviceDefinitions)) {
            if (file_exists($serviceDefinitions)) {
                $serviceDefinitions = include($servicesDefinitions);
            } else {
                throw new Exception(sprintf("Cannot find service file: %s", $serviceDefinitions));
            }
        }
        if (!is_array($serviceDefinitions)) {
            throw new Exception("Service definitons must be an array");
        }
        $this->definitions = $serviceDefinitions;
    }

    public function has(string $serviceName): bool
    {
        return class_exists($serviceName) || !empty($this->buildServiceDefinitions($serviceName));
    }

    public function get(string $serviceName): mixed
    {
        if (!array_key_exists($serviceName, $this->services)) {
            $definitions = $this->buildServiceDefinitions($serviceName);
            if (isset($tihs->recursionProtection[$serviceName])) {
                throw new Exception(sprintf("Circular definition of service %s detected.", $serviceName));
            }
            $this->recursionProtection[$serviceName] = true;
            try {
                $builder = new ServiceBuilder($this, $serviceName, $definitions);
                $service = $builder->build();
                $postOperation = $builder->getPostOperation();
                if ($this->publicContainer || $builder->isPublic()) {
                    $this->publicServices[$serviceName] = true;
                }
                if ($postOperation !== null) {
                    $this->postOperations[] = $postOperation;
                }
                $this->services[$serviceName] = $definition;
                if (count($this->recursionProtection) <= 1) {
                    while (!empty($this->postOperations)) {
                        $postOperations = $this->postOperations;
                        $this->postOperations = [];
                        foreach ($postOperations as $postOperation) {
                            $postOperation();
                        }
                    }
                }
            } finally {
                unset($this->recursionProtection[$serviceName]);
            }
        }
        if (empty($this->recursionProtection)) {
            $postOperations = $this->postOperations;
            $this->postOperations = [];
            foreach ($postOperations as $postOperation) {
                $postOperation();
            }
            if (!($this->publicServices[$serviceName] ?? false)) {
                throw new Exception(sprintf("Cannot access private service %s", $serviceName));
            }
        }
        return $this->services[$serviceName];
    }

    private function buildServiceDefinitions(string $serviceName): array
    {
        $serviceNameParsed = explode('\\', $serviceName);
        $last = count($serviceNameParsed) - 1;
        $definitions = [];
        $part = "";
        foreach ($serviceNameParsed as $i => $serviceNamePart) {
            $part .= $serviceNamePart . (($i !== $last) ? '\\' : '');
            if (array_key_exists($part, $this->definitions)) {
                $definitions[] = $this->definitions[$part];
            }
        }
        return $definitions;
    }
}
