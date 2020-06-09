# Quanta Psr-11 container

This package provides a minimalist dependency injection container implementing [Psr-11](https://www.php-fig.org/psr/psr-11/).

- [Getting started](#getting-started)
- [Usage](#usage)

## Getting started

**Require** php >= 7.0

**Installation** `composer require quanta/container`

**Run tests** `./vendor/bin/kahlan`

## Usage

```php
<?php

// instantiation through named constructor (only way).
$container = Quanta\Container::from([
    SomeService::class => fn ($container) => new SomeService(
        $container->get(SomeDependency::class),
    ),

    SomeDependency::class => fn () => new SomeDependency,

    'throwing' => function () {
        throw new Exception('some exception');
    },
]);

// true
$container instanceof Psr\Container\ContainerInterface;

// true
$container->has(SomeService::class);

// false
$container->has('not.defined');

// new SomeService(new SomeDependency)
$container->get(SomeService::class);

// true
$container->get(SomeService::class) === $container->get(SomeService::class);

// No 'not.defined' entry defined in the container
try {
    $container->get('not.defined');
}

catch (Quanta\Container\NotFoundException $e) {
    echo $e->getMessage() . "\n";
}

// The factory producing the 'throwing' container entry has thrown an uncaught exception
// some exception
try {
    $container->get('throwing');
}

catch (Quanta\Container\ContainerException $e) {
    echo $e->getMessage() . "\n";
    echo $e->getPrevious()->getMessage() . "\n";
}
```
