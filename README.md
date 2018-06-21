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

[Dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) containers are used to encapsulate the creation of objects. For something consuming a container it looks like a [map](https://en.wikipedia.org/wiki/Map_%28computer_science%29) of objects allowing to retrieve them using identifiers, regardless how they are instantiated.

The [Psr-11](https://www.php-fig.org/psr/psr-11/) standard normalize the way entries are retrieved from a container. It defines an interface named `Psr\Container\ContainerInterface` containing two methods: `has($id)` returning whether an identifier is associated with an entry and `get($id)` returning its value.

```php
<?php

use Psr\Container\ContainerInterface;

$consumer = function (ContainerInterface $container) {

    // check whether an entry is associated with the identifier 'some.service'.
    $container->has('some.service');

    // retrieve the value of the entry associated with the identifier 'some.service'.
    // we don't care how this value is built.
    $service = $container->get('some.service');

}
```

Defining container entries and the way their values are built depends on the implementation. It usually involves configuration files, service providers, auto wiring algorithms and other complex mechanism.

The class `Quanta\Container` is a [Psr-11](https://www.php-fig.org/psr/psr-11/) implementation built around the idea defining the entries and providing their values are two separate concerns. It aims to be the tiniest possible layer around a map of factories:

```php
<?php

// map of factories example.
$map = [
    'id1' => function () { /* ... */ },
    'id2' => function () { /* ... */ },
];
```

Factories can be any [callable](http://php.net/manual/en/language.types.callable.php) and can return any [type of value](http://php.net/manual/fr/language.types.intro.php). They receive the container as parameter allowing them to inject an object dependencies:

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

The same value is returned every time `get($id)` method is called with the same identifier. This is especially important when the value is an object because the same instance is returned.

Factories can be added and overwritten after initialization but the container is immutable so nothing can change its state when it is consumed.

The `get($id)` methods can throw three different exceptions:

- `Quanta\Container\NotFoundException` when no factory is associated with the identifier

- `Quanta\Container\FactoryTypeException` when a non callable value is associated with the identifier

- `Quanta\Container\ContainerException` wrapped around any exception thrown from the factory

The reasoning behind the last one is: as it should not be possible to recover from an exception thrown from a factory, they can be wrapped inside a `ContainerException` to get a beautiful stack trace.

## Usage

```php
<?php

use Quanta\Container;

// initialize the container with a factory map.
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

// check whether the id SomeService::class is associated with an entry.
$container->has('not.defined'); // returns false.
$container->has(SomeService::class); // returns true.

// retrieve the entry associated with the id SomeService::class.
$service1 = $container->get(SomeService::class); // returns the defined instance of SomeService.
$service2 = $container->get(SomeService::class); // returns the same instance of SomeService.

$service1 === $service2 // returns true.
```

```php
<?php

use Quanta\Container;

// initialize the original container.
$container1 = new Container([
    SomeService::class => function () {
        // ...
    };
]);

// adding a factory.
$container2 = $container1->with(SomeOtherService1::class, function () {
    // ...
});

// adding many factories.
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

// initialize a container with failing factories.
$container = new Container([

    'invalid' => 'not a callable',

    'throwing' => function () {
        throw new Exception('The original exception.');
    },

    SomeService::class => function ($container) {
        $dependency = $container->get('throwing');

        return new SomeService($dependency);
    },

]);

// throws a Quanta\Container\NotFoundException
$container->get('notfound');

// throws a Quanta\Container\FactoryTypeException
$container->get('invalid');

// throws a Quanta\Container\ContainerException
// it should display something like:
// - Exception: The original exception. ...
//   ...
// - Next Quanta\Container\ContainerException: Failed to get the entry 'throwing' from the container because its factory has thrown an uncaught exception. ...
//   ...
// - Next Quanta\Container\ContainerException: Failed to get the entry 'SomeService' from the container because its factory has thrown an uncaught exception. ...
//   ...
try {
    $container->get(SomeService::class);
}
catch (Quanta\Container\ContainerException $e) {
    print (string) $e;
}
```
