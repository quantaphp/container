# Quanta Psr-11 container

This package provides a minimalist dependency injection container implementing [Psr-11](https://www.php-fig.org/psr/psr-11/).

The goal is to implement a container working out of the box with minimal configuration, implementing interface aliasing and a
basic autowiring mechanism.

- [Getting started](#getting-started)
- [Basic usage](#basic-usage)
- [Interface aliasing](#interface-aliasing)
- [Autowiring](#autowiring)

## Getting started

**Require** php >= 7.4

**Installation** `composer require quanta/container`

**Run tests** `php ./vendor/bin/phpunit`

**Testing a specific php version using docker:**

- `docker build . --build-arg PHP_VERSION=7.4 --tag quanta-container-tests:7.4`
- `docker run --rm quanta-container-tests:7.4`

## Basic usage

- container entries are defined using any iterable as long as keys can be casted as strings
- any non callable value is returned as is like an associative array
- any callable value is treated as a factory building the associated value (the results are cached so
the callable is run only once and the same value is returned on every ->get() call)

```php
<?php

// container configuration
$container = new Quanta\Container([
    'id' => 'value',

    SomeService::class => fn ($container) => new SomeService(
        $container->get(SomeDependency::class),
    ),

    SomeDependency::class => fn () => new SomeDependency,

    'throwing' => function () {
        throw new Exception('some exception');
    },
]);

final class SomeService
{
    public function __construct(public SomeDependency $dependency)
    {
    }
}

// true
$container instanceof Psr\Container\ContainerInterface;
$container->has('id');
$container->has(SomeService::class);
$container->has(SomeDependency::class);
$container->has('throwing');
$container->get('id') === 'value';
$container->get(SomeService::class) == new SomeService(new SomeDependency);
$container->get(SomeDependency::class) == new SomeDependency;
$container->get(SomeService::class) === $container->get(SomeService::class);
$container->get(SomeDependency::class) === $container->get(SomeDependency::class);
$container->get(SomeService::class)->dependency === $container->get(SomeDependency::class);

// false
$container->has('not.defined');

// throws Quanta\Container\NotFoundException
try {
    $container->get('not.defined');
}

catch (Quanta\Container\NotFoundException $e) {
    // 'No 'not.defined' entry defined in the container'
    echo $e->getMessage() . "\n";
}

// throws Quanta\Container\ContainerException with the caught exception as previous
try {
    $container->get('throwing');
}

catch (Quanta\Container\ContainerException $e) {
    // 'Cannot get 'throwing' from the container: factory has thrown an uncaught exception'
    echo $e->getMessage() . "\n";

    // 'some exception'
    echo $e->getPrevious()->getMessage() . "\n";
}
```

## Interface aliasing

- interface names associated to strings are treated as aliases

```php

// container configuration
$container = new Quanta\Container([
    SomeInterface::class => SomeImplementation::class,

    SomeImplementation::class => fn () => new SomeImplementation,
]);

// true
$container->get(SomeInterface::class) == new SomeImplementation;
$container->get(SomeInterface::class) === $container->get(SomeInterface::class);
$container->get(SomeInterface::class) === $container->get(SomeImplementation::class);
$container->get(SomeImplementation::class) === $container->get(SomeImplementation::class);
```

## Autowiring

The container will try to build instances of non defined classes using simple rules:

- when the type of a constructor parameter is an interface/class name, its value will be retrieved
from the container (and also autowired if needed for class names)
- when the type of a constructor parameter is not a class name:
    - default value is used when defined
    - null is used when the parameter has no default value and is nullable
    - an Quanta\Container\ContainerException is thrown otherwise
- the $container->has() method returns true for any existing classes
- the objects built through autowiring are cached
- autowiring an abstract class throws a Quanta\Container\ContainerException
- autowiring a class with protected/private constructor throws a Quanta\Container\ContainerException
- php 8.0 => constructor parameter with union type throws a Quanta\Container\ContainerException
- php 8.1 => constructor parameter with intersection type throws a Quanta\Container\ContainerException

A factory must be defined when more control over the class instantiation is needed.

```php
<?php

// container configuration
$container = new Quanta\Container([
    SomeInterface::class => SomeImplementation::class,
]);

final class UndefinedClass
{
    public function __construct(
        public SomeInterface $dependency1,
        public AnotherUndefinedClass $dependency2,
        public ?int $dependency3,
        public string $dependency4 = 'test',
    ) {

    }
}

// true
$container->has(UndefinedClass::class);

// new UndefinedClass
$container->get(UndefinedClass::class);

// true
$container->get(UndefinedClass::class) === $container->get(UndefinedClass::class);

// true
$container->get(UndefinedClass::class) == new UndefinedClass(
    $container->get(SomeInterface::class),
    $container->get(AnotherUndefinedClass::class),
);

// true
$container->get(UndefinedClass::class)->dependency1 === $container->get(SomeInterface::class);
$container->get(UndefinedClass::class)->dependency2 === $container->get(AnotherUndefinedClass::class);
$container->get(UndefinedClass::class)->dependency3 === null;
$container->get(UndefinedClass::class)->dependency4 === 'test';
```
