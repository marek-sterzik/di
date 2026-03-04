<?php

namespace Tests;

use ReflectionClass;

class Helper
{
    public static function getClassProperty(string $class, string $propertyName): mixed
    {
        $class = new ReflectionClass($class);
        $property = $class->getProperty($propertyName);
        if (is_callable([$property, "setAccessible"])) {
            $property->setAccessible(true);
        }
        return $property->getValue();
    }

    public static function getObjectProperty(object $object, string $propertyName): mixed
    {
        $class = new ReflectionClass($object);
        $property = $class->getProperty($propertyName);
        if (is_callable([$property, "setAccessible"])) {
            $property->setAccessible(true);
        }
        return $property->getValue($object);
    }

    public static function setClassProperty(string $class, string $propertyName, mixed $value): void
    {
        $class = new ReflectionClass($class);
        $property = $class->getProperty($propertyName);
        if (is_callable([$property, "setAccessible"])) {
            $property->setAccessible(true);
        }
        $property->setValue(null, $value);
    }

    public static function setObjectProperty(object $object, string $propertyName, mixed $value): void
    {
        $class = new ReflectionClass($object);
        $property = $class->getProperty($propertyName);
        if (is_callable([$property, "setAccessible"])) {
            $property->setAccessible(true);
        }
        $property->setValue($object, $value);
    }
}
