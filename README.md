# Quanta Psr-11 container

This package provides a minimalist dependency injection container implementing [Psr-11](https://www.php-fig.org/psr/psr-11/).

- [Getting started](#getting-started)
- [Philosophy](#philosophy)
- [Design](#design)
- [Usage](#usage)

## Getting started

**Require** php >= 7.0

**Installation** `composer require quanta/container`

**Run tests** `./vendor/bin/kahlan`

## Philosophy

The [Psr-11](https://www.php-fig.org/psr/psr-11/) standard normalize the way values are retrieved from a [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) container. It defines an interface named `Psr\Container\ContainerInterface` declaring two methods: `has($id)` returning whether an `$id` entry is defined in the container and `get($id)` returning it.

From the point of view of something consuming a [Psr-11](https://www.php-fig.org/psr/psr-11/) container, it just looks like a map of values:

```php
<?php

use Psr\Container\ContainerInterface;

$consumer = function (ContainerInterface $container) {

    // Check whether an 'some.service' entry is defined in the container.
    $container->has('some.service');

    // Retrieve the 'some.service' entry.
    $service = $container->get('some.service');

}
```

Defining container entries and the way they are built depends on the implementation. It usually involves configuration files, service providers, auto wiring algorithms and other complex mechanisms.

The class `Quanta\Container` is a [Psr-11](https://www.php-fig.org/psr/psr-11/) implementation built around the idea defining the entries and providing them are two separate concerns. It aims to be the tiniest possible layer around a map of factories:

```php
<?php

// A map of container factories.
$map = [
    'id1' => function ($container) { /* ... */ },
    'id2' => function ($container) { /* ... */ },
];
```

Container factories can be any [callable](http://php.net/manual/en/language.types.callable.php) and can return any value. They are called with the container as first argument so it can be used to retrieve the entry's dependencies:

```php
<?php

// A factory producing a SomeService object.
$factory = function ($container) {
    $dependency = $container->get('dependency');

    return new SomeService($dependency);
};
```

Developers are free to choose how to build the map of factories while `Quanta\Container` focuses on being as efficient as possible to retrieve the values they produce.

## Design

`Quanta\Container` implements [Psr-11](https://www.php-fig.org/psr/psr-11/) `Psr\Container\ContainerInterface`.

The same value is returned every time the `get($id)` method is called with the same identifier. It means the factory associated with the `$id` entry is executed only on the first call and the produced value is cached to be returned on subsequent calls. This is especially important when the value is an object because the same instance is returned when retrieved multiple times.

Factories can be added and overwritten after initialization but the container is immutable so nothing can change its state while it is consumed.

The `get($id)` methods can throw two different exceptions:

- `Quanta\Container\NotFoundException` when no `$id` entry is defined

- `Quanta\Container\ContainerException` wrapped around any exception thrown from the factory

The second one allow to keep track of all the container entries failling because of the original exception.

## Usage

```php
<?php

use Quanta\Container;

// Initialize the container with many entries.
$container = new Container([
    'some.config' => function () {
        return 'config value';
    },

    SomeDependency::class => function ($container) {
        $config = $container->get('some.config');

        return new SomeDependency($config);
    },

    SomeService::class => function ($container) {
        $dependency = $container->get(SomeDependency::class);

        return new SomeService($dependency);
    },
]);

// Quanta\Container is a Psr-11 implementation.
$container instanceof Psr\Container\ContainerInterface // returns true.

// Check whether an entry is defined in the container.
$container->has('not.defined'); // returns false.
$container->has(SomeService::class); // returns true.

// Retrieve the value of the SomeService::class entry.
$service1 = $container->get(SomeService::class); // returns the defined instance of SomeService.
$service2 = $container->get(SomeService::class); // returns the same instance of SomeService.

$service1 === $service2 // returns true.
```

```php
<?php

use Quanta\Container;

// Initialize the original container.
$container1 = new Container([
    SomeService::class => function () {
        // ...
    },
]);

// Create a new container with an additional entry.
$container2 = $container1->with(SomeOtherService1::class, function () {
    // ...
});

// Create a new container with many additional entries.
$container3 = $container2->withEntries([
    SomeOtherService2::class => function () { /*...*/ },
    SomeOtherService3::class => function () { /*...*/ },
    SomeService::class => function () {
        // $container3 uses this factory instead of the one defined in $container1.
    },
]);

// Quanta\Container is immutable.
$container1 === $container2 // returns false.
$container2 === $container3 // returns false.
```

```php
<?php

use Quanta\Container;

// Initialize a container with failling entries.
$container = new Container([
    'throwing' => function () {
        throw new Exception('The original exception');
    },

    SomeService::class => function ($container) {
        $dependency = $container->get('throwing');

        return new SomeService($dependency);
    },
]);

// Throws a Quanta\Container\NotFoundException.
$container->get('notfound');

// Throws a Quanta\Container\ContainerException. It should display something like:
// - Exception: The original exception ...
//   ...
// - Next Quanta\Container\ContainerException: The factory producing the 'throwing' container entry has thrown an uncaught exception ...
//   ...
// - Next Quanta\Container\ContainerException: The factory producing the 'SomeService' container entry has thrown an uncaught exception ...
//   ...
try {
    $container->get(SomeService::class);
}
catch (Quanta\Container\ContainerException $e) {
    echo (string) $e;
}
```
