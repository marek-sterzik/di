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


## Installation

```
composer require sterzik/di
```

## Basic usage

```php
use Sterzik\DI\DI;

// get global DI container
$di = DI::instance();

// instantiate a service:
$service = $di->get(MyServiceClass::class);

// test if a service is available
$myServiceAvailable = $di->has(MyServiceClass::class);
```

In this simplest scenario (without any external configuration) this holds:

* Service names correspond to class names
* Dependencies are resolved by autowiring driven by the specified type of the constructor argument.
* Only objects may be autowired. Builtin types or non-typed arguments are never autowired.


## custom DI container

```php
use Sterzik\DI\DI;

// instantiate a separate custom container
$container = new DI();

// instantiate a service
$service = $di->get(MyServiceClass::class);
```

## Using configuration in a DI container

In general there are two options how to configure a DI container. You may either pass an configuration array of service definitions or you may pass a php configuration file which
returns the service definitions in the same format. The configuration is therefore either an array (direct passing of the service definitions) or a php filename (indirect passing
of the service definitions) which return value will be the same service definitions. The filename may be either specified as an absolute path or a relative path, which will be
resolved relatively to the project root (the directory where `composer.json` is present). 

### Configuring the global DI container

Without any setting, the global DI container (instantiated as `DI::instance()`) uses the config file `config/services.php` if present, or no configuration if the file is missing.
There are 4 options, how to pass some another custom configuration:

1. define the constant `DI_SERVICE_DEFINITIONS` which must be defined before the first call of `DI::instance()`.
2. Call `DI::setServiceDefinitions($filename)` with either relative or absolute file path before the first call of `DI::instance()` (calling `DI::setServiceDefinitions()` will override
   the `DI_SERVICE_DEFINITIONS` if both is present)
3. Call `DI::setServiceDefinitions($configurationArray)` with an explicit configuration array before the first call of `DI::instance()`.
4. Use your own custom container storage based on a custom container.

Examples:

```php
    // take configuration from $projectRoot/service-config.php
    define("DI_SERVICE_DEFINITIONS", "service-config.php");
    $di = DI::instance();
```

```php
    // take configuration from /app/root/service-config.php
    DI::setServiceDefinitions("/app/root/service-config.php");
    $di = DI::instance();
```
```php
    // set explicit service definitions (see later how service definitions look like)
    $config = [
        ...
    ];
    DI::setServiceDefinitions($config);
    $di = DI::instance();
```

### Configure custom DI container

To configure a custom DI container, just pass the configuration (or config file name) to the constructor of the `DI` object:

```php
    $di = new DI("config/services.php");
```

```php
    $config = [
        ...
    ];
    $di = new DI($config);
```

## Service names

A service name may be *any* string not starting or ending with a backslash (`\`). If the service name starts with a backslash, it will be stripped automatically.
The service container is designed in a way to support best cases when a service name corresponds to the class name, but this rule is not mandatory. The only mandatory
rules for service names are:

* Don't start any service name with a backslash. Leading backslashes will be stripped.
* Don't end any service name with a backslash. Trailing backslashes correspond to service prefixes instead of direct services.

## Loading service definitions

Multiple service definitions play a role when instantiating a service. Let say, we want to instantiate a service `Some\Cool\Service`. In this case, these service definitions
will be loaded (in the given order):

* The global "unchangable" service definition.
* The global service definition being always under the `\` key if present.
* The service definition for the prefix `Some\` if present.
* The service definition for the prefix `Some\Cool\` if present.
* The service definition for the service itself `Some\Cool\Service` if present.

All these service definitions are cascaded and the next service definition overrides the previous one.

## Service definitions

These data types are valid service definitions:

* functions (callables) taking one argument being the `Sterzik\DI\ServiceBuilder` object (preferred way how to build services). The return value of the function will decide how the
  service will be built (see later)
* arrays (not yet implemented, reserved for future usage)
* any other objects (in this case the service will be resolved "statically")

A service may be **any** php value. It does need to be necessarily an object, but having services as objects is the most common practice.

When the service definition is a callable, this holds for the return value:

* if the return value is the builder itself, the builder will be responsible for building the service.
* if the return value is `null`, it is the same as if the builder would be returned.
* if any other value is returned, it will become the service

