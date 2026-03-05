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

Examples:

```php
use Sterzik\DI\DI;

$config = [
    // callable
    "service1" => fn($builder) =>
        $builder->setClass(MyService1Class::class),

    // array - reserved for future development
    "service2" => [
        "some" => "configuration"
    ],

    // static service resolving
    "service3" => new StaticServiceClass(),
];

$di = new DI($config);

// lead to: new MyService1Class()
$service1 = $di->get("service1");

// not yet implemented
// leads to Sterzik\DI\Exception\NotImplementedException exception
$service2 = $di->get("service2");

// leads to the static instance of StaticServiceInstance class
// passed to the configuration
$service3 = $di->get("service3");
```

```php
use Sterzik\DI\DI;

$config = [
    // function returns builder:
    "service1" => function ($builder) {
        return $builder->setClass(MyService1Class::class);
    },

    // function returns null (none)
    "service2" => function ($builder) {
        $builder->setClass(MyService2Class::class);
    },

    // function returns anything else
    "service3" => function ($builder) {
        return new StaticServiceClass();
    },
];

$di = new DI($config);

// lead to: new MyService1Class()
$service1 = $di->get("service1");

// lead to: new MyService2Class() (same as in the case of service1)
$service2 = $di->get("service2");

// leads to the static instance of StaticServiceInstance class
// created in the callable service definition of service3
$service3 = $di->get("service3");
```

## The builder

The builder passed to the callable service definition contains many methods which may control the build process of the service. All setters return the builder itself
and therefore setters may be chained.

### $builder->setClass($class)

Sets the class of the built object (constructed by a regular constructor).

Example `services.php`:

```php
return [
    "service" => function ($builder) {
        return $builder->setClass(MyServiceClass::class);
    },
];
```

### $builder->setArgument($argument, $value)

Sets one constructor argument. Argument may be specified either as an integer (position index) or a string (argument name).

Example `services.php`:
```php
return [
    MyServiceClass::class => fn($builder) => $builder
        ->setArgument(0, "FirstArgument")
        ->setArgument("argument2", "SecondArgument"),
];
```

### $builder->setArguments(...$arguments)

Set multiple constructor arguments given in the variadic argument `$arguments`.

Example `services.php`:
```php
return [
    MyServiceClass::class => fn($builder) => $builder
        ->setArguments("FirstArgument",argument2: "SecondArgument"),
];
```

### $builder->putArguments($arguments, $resetArguments = false)

Set multiple constructor arguments given in the array `$arguments`. If `$resetArguments` is true, then all previously set arguments will be reset.

Example `services.php`:
```php
return [
    MyServiceClass::class => fn($builder) => $builder
        ->putArguments([0 => "FirstArgument", "argument2" => "SecondArgument"], true),
];
```

### $builder->resetArguments()

Reset all previously set arguments. Equivalent to call `$builder->putArguments([], true)`.

Example `services.php`:
```php
return [
    '\\' => fn($builder) => $builder
        ->setArgument("url" => $url),

    MyServiceClass::class => fn($builder) => $builder
        ->resetArguments(),
];
```

### $builder->setFactory($factory)

Use the callable `$factory` instead of calling the constructor. Arguments set by `setArgument()`, `setArguments()` or `putArguments()` are passed to the factory callable if set.

Example `services.php`:
```php
$factory = function (string $argument) {
    return new Service($argument);
}

return [
    MyServiceClass::class => fn($builder) => $builder
        ->setFactory($factory)
        ->setArgument("argument", "someValue"),
];
```

### $builder->setAutowire($autowire = true)

Enable or disable the autowiring functionality. If autowire is enabled (default state) arguments of the constructor or the factory not explicitely defined will be autowired
to services using the defined type of the argument.

Example `services.php`:
```php
// globally disable autowiring
return [
    '\\' => fn($builder) => $builder
        ->setAutowire(false),
    ...
];

```

### $builder->setRequireExplicitClass($requireExplicitClass = true)

Enable or disable the automatic class resolving. By default, if the class is not specified, the service name is used as the class. If this function is enabled,
classes must be explicitely specified for each service.

Example `services.php`:
```php
// globally require explicit class
return [
    '\\' => fn($builder) => $builder
        ->setRequireExplicitClass(true),

    ...
];

```

### $builder->setPublic($public = true)

Set the service as public (default) or private (`$public = false`). If the service is set to be private, then it cannot be instantiated outside of the DI container.
It may be instantiated only as a dependency of other public classes.

Example `services.php`:
```php
// set all services as public except service of id SomePublicServiceClass::class
return [
    '\\' => fn($builder) => $builder
        ->setPublic(false),

    SomePublicServiceClass::class => fn($builder) => $builder
        ->setPublic(true),
];

```

### $builder->call($method, ...$arguments)

Call a method `$method` of the service after creation. The service **must** be an object if you want to use this feature.

Example `services.php`:
```php
// after creation of MyServiceClass instance, the method setupUrl($url) will be called.
return [
    MyServiceClass::class => fn($builder) => $builder
        ->call("setupUrl", $url),
];
```

### $builder->callArguments($method, $arguments, $autowire = null)

Same as `call()` but arguments are passed as a single argument instead of using a variadic argument. The `$autowire` argument
specifies, if autowiring may be used for resolving method arguments. Possible values:

* `true` - do use autowiring in this method
* `false` - dont use autowiring in this method
* `null` - use the autowire setting valid for the constructor

Example `services.php`:
```php
// after creation of MyServiceClass instance, the method setupUrl($url) will be called.
return [
    MyServiceClass::class => fn($builder) => $builder
        ->callArgs("setupUrl", [$url], false),
];
```

### $builder->setService($service)

Explicitely set the service. It has the same effect as returning the service in the service definition callback.

Example `services.php`:
```php
$service = new Service();

// both services resolve to the same object instance
return [
    "service1" => fn($builder) => $service,
    "service2" => fn($builder) => $builder->setService($service),
];
```

### $builder->get($serviceName)

get the service of the given service name from the DI container.

Example `services.php`:
```php
// service "subservice" will be wired to the constructor argument $subService of class SomeClass
return [
    "service" => fn($builder) => $builder
        ->setClass(SomeClass::class)
        ->setArgument("subService", $builder->get("subservice")),

    "subservice" => fn($builder) => $builder
        ->setClass(SubserviceClass::class),
];
```

### $builder->has($serviceName)

Test if the service of the given service name does exist in the DI container.
