# Quanta Psr-11 container

This package provides a minimalist container implementing the  [Psr-11](https://www.php-fig.org/psr/psr-11/) standard.

- [Getting started](#getting-started)
- [Philosophy](#philosophy)
- [Design](#design)
- [Usage](#usage)

## Getting started

**Require** php >= 7.0

**Installation** `composer require quanta/container`

**Run tests** `./vendor/bin/kahlan`

## Philosophy

The [Psr-11](https://www.php-fig.org/psr/psr-11/) standard normalize the way values are retrieved from a [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) container. It defines an interface named `Psr\Container\ContainerInterface` declaring two methods: `has($id)` returning whether an `$id` entry is defined in the container and `get($id)` returning its value.

From the point of view of something consuming a [Psr-11](https://www.php-fig.org/psr/psr-11/) container, it just looks like a map of values:

```php
<?php

use Psr\Container\ContainerInterface;

$consumer = function (ContainerInterface $container) {

    // Check whether an 'some.service' entry is defined in the container.
    $container->has('some.service');

    // Retrieve the value of the 'some.service' entry.
    $service = $container->get('some.service');

}
```

Defining container entries and the way their values are built depends on the implementation. It usually involves configuration files, service providers, auto wiring algorithms and other complex mechanisms.

The class `Quanta\Container` is a [Psr-11](https://www.php-fig.org/psr/psr-11/) implementation built around the idea defining the entries and providing their values are two separate concerns. It aims to be the tiniest possible layer around a map of factories:

```php
<?php

// map of factories example.
$map = [
    'id1' => function () { /* ... */ },
    'id2' => function () { /* ... */ },
];
```

Factories can be any [callable](http://php.net/manual/en/language.types.callable.php) and can return any [type of value](http://php.net/manual/fr/language.types.intro.php). They receive the container as argument allowing them to inject an object dependencies:

```php
<?php

// factory example.
$factory = function ($container) {
    $dependency = $container->get('dependency');

    return new SomeService($dependency);
};
```

Developers are free to choose how to build the map of factories while `Quanta\Container` focuses on being as efficient as possible to retrieve the values they produce.

## Design

`Quanta\Container` implements [Psr-11](https://www.php-fig.org/psr/psr-11/) `Psr\Container\ContainerInterface`.

The same value is returned every time the `get($id)` method is called with the same identifier. It means the factory associated with the `$id` entry is executed only on the first call and the value it produced is cached to be returned on subsequent calls. This is especially important when the value is an object because the same instance is returned when retrieved multiple times.

Factories can be added and overwritten after initialization but the container is immutable so nothing can change its state when it is consumed.

The `get($id)` methods can throw two different exceptions:

- `Quanta\Container\NotFoundException` when no `$id` entry is definedss

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
    };
]);

// Create a new container with an additional entry.
$container2 = $container1->with(SomeOtherService1::class, function () {
    // ...
});

// Create a new container with many additional entries.
$container3 = $container2->withFactories([
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
        throw new Exception('the original exception');
    },

    SomeService::class => function ($container) {
        $dependency = $container->get('throwing');

        return new SomeService($dependency);
    },

]);

// Throws a Quanta\Container\NotFoundException.
$container->get('notfound');

// Throws a Quanta\Container\ContainerException. It should display something like:
// - Exception: the original exception ...
//   ...
// - Next Quanta\Container\ContainerException: uncaught exception thrown when retrieving the 'throwing' entry from the container ...
//   ...
// - Next Quanta\Container\ContainerException: uncaught exception thrown when retrieving the 'SomeService' entry from the container ...
//   ...
try {
    $container->get(SomeService::class);
}
catch (Quanta\Container\ContainerException $e) {
    echo (string) $e;
}
```
