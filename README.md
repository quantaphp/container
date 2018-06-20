# Quanta Psr-11 container

This package provides a minimalist container implementing the  [Psr-11](https://www.php-fig.org/psr/psr-11/) standard.

## Getting started

**Require** php >= 7.0

**Installation** `composer require quanta/container`

**Run tests** `./vendor/bin/kahlan`

## Philosophy

From the consumer point of view, a [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection) container can be seen as a [map](https://en.wikipedia.org/wiki/Map_%28computer_science%29) of entries allowing to use identifiers to retrieve the values associated with them, regardless how they are built. It can be simple scalar values as well as complex objects with multiple dependencies.

The [Psr-11](https://www.php-fig.org/psr/psr-11/) standard normalize the way entries are retrieved from a container. It defines an interface named `Psr\Container\ContainerInterface` containing two methods: `has($id)` returning whether an identifier is associated with an entry and `get($id)` returning the value associated with it.

```php
<?php

use Psr\Container\ContainerInterface;

$consumer = function (ContainerInterface $container) {
    // check whether an entry is mapped to the identifier 'some.service'.
    $container->has('some.service');

    // retrieve the value of the entry mapped to the identifier 'some.service'.
    // we don't care how this value is built.
    $service = $container->get('some.service');
}
```

How the values are built depends on the implementation. It usually involves configuration files, service providers, auto wiring algorithms and other complex logic.

The class `Quanta\Container` is a [Psr-11](https://www.php-fig.org/psr/psr-11/) implementation built around the idea defining factories and providing their results are two separate concerns. It aims to be the tiniest possible layer around a factory map:

```php
<?php

// identifier to factory map example.
$map = [
    'id1' => function () { /* ... */ },
    'id2' => function () { /* ... */ },
];
```

With factories being any [callable](http://php.net/manual/en/language.types.callable.php). They are called with the container as parameter:

```php
<?php

// factory example.
$factory = function ($container) {
    $dependency = $container->get('dependency');

    return new SomeService($dependency);
};
```

The developper is free to choose how to build the factory map while `Quanta\Container` focuses on being as efficient as possible when retrieving the values they produce.

## Using Quanta\Container

```php
<?php

use Quanta\Container;

// initialize the container with a factory map.
$container = new Container([
    'some.config' => function () {
        return 'config';
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
$container instanceof \Psr\Container\ContainerInterface // returns true.

// check whether the id SomeService::class is associated with an entry.
$container->has(SomeService::class); // returns true.

// retrieve the entry associated with the id SomeService::class.
$service1 = $container->get(SomeService::class); // returns the defined instance of SomeService.
$service2 = $container->get(SomeService::class); // returns the same instance of SomeService.

$service1 === $service2 // returns true.
```

```php
<?php

use Quanta\Container;

$container1 = new Container([]);

// adding a factory.
$container2 = $container1->with(SomeService::class, function () {
    // ...
});

// adding many factories.
$container3 = $container2->withFactories([
    SomeOtherService2::class => function () { /*...*/ },
    SomeOtherService3::class => function () { /*...*/ },
]);

// Quanta\Container is immutable.
$container1 === $container2 // returns false.
$container2 === $container3 // returns false.
```
