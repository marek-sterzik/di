<?php

namespace Sterzik\DI;

use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use ReflectionNamedType;

use Sterzik\DI\Exception\CircularReferenceException;
use Sterzik\DI\Exception\PrivateServiceException;
use Sterzik\DI\Exception\MissingConfigurationException;
use Sterzik\DI\Exception\MissingParameterException;
use Sterzik\DI\Exception\ParametersImmutableException;
use Sterzik\DI\Exception\InvalidConfigurationException;
use Sterzik\DI\Exception\ServiceDoesNotExistException;

final class DI
{
    const DEFAULT_DI_SERVICE_DEFINITION_FILE = "config/services.php";

    private static mixed $serviceDefinitions = null;
    private static ?self $instance = null;
    private static bool $instantiateMain = false;

    private array $definitions = [];
    private array $services = [];
    private array $recursionProtection = [];
    private array $postOperations = [];
    private array $publicServices = [];
    private array $parameters = [];
    private bool $parametersFinal = false;

    public static function setServiceDefinitions(string|array $serviceDefinitions): void
    {
        if (self::$instance !== null) {
            throw new InvalidConfigurationException(
                "Service definitons must be passed before DI::instance() is called"
            );
        }
        self::$serviceDefinitions = $serviceDefinitions;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            if (self::$serviceDefinitions !== null) {
                $serviceDefinitions = self::$serviceDefinitions;
            } elseif (defined("DI_SERVICE_DEFINITIONS")) {
                $serviceDefinitions = DI_SERVICE_DEFINITIONS;
            } else {
                $serviceDefinitions = self::DEFAULT_DI_SERVICE_DEFINITION_FILE;
            }
            self::$instantiateMain = true;
            try {
                self::$instance = new self($serviceDefinitions);
            } finally {
                self::$instantiateMain = false;
            }
        }
        return self::$instance;
    }

    public function __construct(string|array $serviceDefinitions, array $parameters = [])
    {
        if (is_string($serviceDefinitions)) {
            $serviceDefinitions = Path::resolve($serviceDefinitions);
            if (file_exists($serviceDefinitions)) {
                $serviceDefinitions = include($serviceDefinitions);
            } else if(self::$instantiateMain) {
                $serviceDefinitions = [];
            } else {
                throw new MissingConfigurationException(sprintf("Cannot find service file: %s", $serviceDefinitions));
            }
        }
        if (!is_array($serviceDefinitions)) {
            throw new InvalidConfigurationException("Service definitons must be an array");
        }
        $this->definitions = [];
        foreach ($serviceDefinitions as $service => $definition) {
            $serviceCanonized = $this->canonizeServiceName($service);
            if (!isset($this->definitions[$serviceCanonized])) {
                $this->definitions[$serviceCanonized] = new ServiceDefinition();
            }
            $this->definitions[$serviceCanonized]->addDefinition($definition);
        }

        $this->parameters = $parameters;
    }

    public function setParameter(string $parameter, mixed $value): self
    {
        return $this->setParameters([$parameter => $value]);
    }

    public function setParameters(array $parameters): self
    {
        if ($this->parametersFinal) {
            throw new ParametersImmutableException(
                "Cannot modify DI parameters, they become immutable after getting the fist service from DI"
            );
        }
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    public function freezeParameters(): self
    {
        $this->parametersFinal = true;
        return $this;
    }

    public function parameter(string $parameter): mixed
    {
        if (!array_key_exists($parameter, $this->parameters)) {
            throw new MissingParameterException(sprintf("Missing parameter %s in DI container", $parameter));
        }
        return $this->parameters[$parameter];
    }

    public function has(string $serviceName): bool
    {
        try {
            $this->get($serviceName);
            return true;
        } catch (ServiceDoesNotExistException $e) {
            return false;
        } catch (PrivateServiceException $e) {
            return false;
        }
    }

    public function get(string $serviceName): mixed
    {
        $this->parametersFinal = true;
        $serviceName = $this->canonizeServiceName($serviceName);
        if (!array_key_exists($serviceName, $this->services)) {
            $definitions = $this->buildServiceDefinitions($serviceName);
            if (isset($this->recursionProtection[$serviceName])) {
                throw new CircularReferenceException(sprintf("Circular definition of service %s detected.", $serviceName));
            }
            $this->recursionProtection[$serviceName] = true;
            try {
                $builder = new ServiceBuilder($this, $serviceName, $definitions);
                $service = $builder->build();
                $postOperation = $builder->getPostOperation();
                if ($builder->isPublic()) {
                    $this->publicServices[$serviceName] = true;
                }
                if ($postOperation !== null) {
                    $this->postOperations[] = [$postOperation, $service];
                }
                $this->services[$serviceName] = $service;
                if (count($this->recursionProtection) <= 1) {
                    while (!empty($this->postOperations)) {
                        $postOperations = $this->postOperations;
                        $this->postOperations = [];
                        foreach ($postOperations as list($postOperation, $service)) {
                            $postOperation($service);
                        }
                    }
                }
            } finally {
                unset($this->recursionProtection[$serviceName]);
            }
        }
        if (empty($this->recursionProtection)) {
            if (!($this->publicServices[$serviceName] ?? false)) {
                throw new PrivateServiceException(sprintf("Cannot access private service %s", $serviceName));
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
        if (array_key_exists('\\', $this->definitions)) {
            $definitions[] = $this->definitions['\\'];
        }
        foreach ($serviceNameParsed as $i => $serviceNamePart) {
            $part .= $serviceNamePart . (($i !== $last) ? '\\' : '');
            if (array_key_exists($part, $this->definitions)) {
                $definitions[] = $this->definitions[$part];
            }
        }
        return $definitions;
    }

    private function canonizeServiceName(string $serviceName): string
    {
        if (substr($serviceName, 0, 1) == '\\') {
            $serviceName = substr($serviceName, 1);
        }
        if ($serviceName === '') {
            $serviceName = '\\';
        }
        return $serviceName;
    }
}
