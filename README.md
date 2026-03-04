# sterzik/di

This is a simple dependency-injection library. The goals of this library are:

* easy to use
* project independent
* compatible with symfony-like services
* designed to cover basic functions of the symfony DI component
* intended for microservices, where the full-featured DI component is too expensive
* easily being integrated in existing projects
* support symfony-like autowiring feature
* handle even more complicated scenarios including circular references
* configuration is not compatible with the symfony DI component


## Basic usage

```php
use Sterzik\DI\DI;

// create a new dependency injection container
$di = new DI();

// instantiate a service:
$service = $di->get(MyServiceClass::class);

// test if a service is available
$myServiceAvailable = $di->has(MyServiceClass::class);
```

In this simplest scenario (without any external configuration) this holds:

* Service names correspond to class names
* Dependencies are resolved by autowiring driven by the specified type of the constructor argument.
* Only objects may be autowired. Builtin types or non-typed arguments are never autowired.
