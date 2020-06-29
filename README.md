# Quanta Psr-11 container

This package provides a minimalist dependency injection container implementing [Psr-11](https://www.php-fig.org/psr/psr-11/).

- [Getting started](#getting-started)
- [Basic usage](#basic-usage)
- [Usage with factory files](#usage-with-factory-files)

## Getting started

**Require** php >= 7.0

**Installation** `composer require quanta/container`

**Run tests** `./vendor/bin/kahlan`

## Basic usage

```php
<?php

// instantiation using an associative array of factories
$container = Quanta\Container::factories([
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

## Usage with factory files

```php
# /app/factories/services1.php
<?php

return [
    SomeService::class => fn ($container) => new SomeService(
        $container->get(SomeDependency::class),
    ),
];
```

```php
# /app/factories/services2.php
<?php

return [
    SomeDependency::class => fn () => new SomeDependency,
];
```

```php
<?php

// instantiation using a list of files returning associative arrays of factories
$container = Quanta\Container::files(
    '/app/factories/services1.php',
    '/app/factories/services2.php',
);

// true
$container->has(SomeService::class);

// new SomeService(new SomeDependency)
$container->get(SomeService::class);
```

```php
<?php

// instantiation using a glob pattern matching files returning associative
// arrays of factories
$container = Quanta\Container::files('/app/factories/*.php');

// true
$container->has(SomeService::class);

// new SomeService(new SomeDependency)
$container->get(SomeService::class);
```

```php
<?php

// instantiation using a list of  glob patterns matching files returning
// associative arrays of factories
$container = Quanta\Container::files('/app/factories/*1.php', '/app/factories/*2.php');

// true
$container->has(SomeService::class);

// new SomeService(new SomeDependency)
$container->get(SomeService::class);
```
