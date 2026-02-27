<?php

namespace Sterzik\DI;

use Exception;
use ReflectionFunctionAbstract;
use ReflectionType;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionMethod;


class ArgumentBuilder
{
    public static function buildArguments(
        ?ReflectionFunctionAbstract $function,
        DI $di,
        array $customArgs
    ): array {
        if ($function === null) {
            return [];
        }
        $functionName = self::getReflectionName($function);
        $arguments = [];
        foreach ($function->getParameters() as $index => $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($index, $customArgs)) {
                $arguments[] = $customArgs[$index];
                unset($customArgs[$index]);
            } else if (array_key_exists($name, $customArgs)) {
                $arguments[] = $customArgs[$name];
                unset($customArgs[$name]);
            } else {
                $type = $parameter->getType();
                $arguments[] = self::findArgumentByType($type, $di, $parameter, $name, $functionName);
            }
        }
        if (!empty($customArgs)) {
            throw new Exception(sprintf("Too much arguments for %s", $functionName));
        }
        return $arguments;
    }

    private static function getReflectionName(ReflectionFunctionAbstract $function): string
    {
        if ($function instanceof ReflectionMethod) {
            return $function->getDeclaringClass()->getName() . "::" . $function->getName() . "()";
        } else {
            return $function->getName() . "()";
        }
    }

    private static function findArgumentByType(
        ?ReflectionType $type,
        DI $di,
        ReflectionParameter $parameter,
        string $argumentName,
        string $functionName
    ): mixed {
        if ($type instanceof ReflectionNamedType && !$type->isBuiltin() && $di->has($type->getName())) {
            return $di->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($type instanceof ReflectionType && $type->allowsNull()) {
            return null;
        }

        throw new Exception(sprintf("Cannot instantiate argument '%s' of '%s' by autowire", $argumentName, $functionName));
    }

}
