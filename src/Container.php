<?php

declare(strict_types=1);

namespace Quanta;

use Psr\Container\ContainerInterface;

use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

final class Container implements ContainerInterface
{
    /**
     * @var array<string, null|array{0: mixed, 1?: mixed}>
     */
    private array $map;

    /**
     * Constructor.
     *
     * @param iterable<string, mixed> $map
     */
    public function __construct(iterable $map = [])
    {
        $this->map = [];

        foreach ($map as $id => $value) {
            try {
                $id = strval($id);
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException(sprintf(
                    'Argument 1 passed to %s::__construct() must be an iterable with stringable keys, %s given',
                    self::class,
                    gettype($id),
                ), 0, $e);
            }

            $this->map[$id] = [$value];
        }
    }

    /**
     * @inheritdoc
     */
    public function get(string $id)
    {
        // get a ref to the entry.
        $ref = &$this->map[$id];

        // check if the entry is defined.
        $defined = !is_null($ref);

        // return the cached value when present.
        if ($defined && array_key_exists(1, $ref)) {
            return $ref[1];
        }

        // get the entry definition if any.
        $definition = $ref[0] ?? null;

        // when the given id is associated to a callable.
        // - execute the callable, cache the result and return it.
        // - any exception thrown by the callable is wrapped in a ContainerException
        //   to trace all failling entries.
        if ($defined && is_callable($definition)) {
            try {
                return $ref[1] = $definition($this);
            } catch (\Throwable $e) {
                throw new ContainerException($this->factoryErrorMessage($id), $e);
            }
        }

        // when the given id is an interface name associated to a string.
        // - get the value from the container, cache it and return it.
        // - allow to alias an interface without using a factory.
        // - any exception thrown by the container is wrapped in a ContainerException
        //   to trace all failling entries.
        if ($defined && is_string($definition) && interface_exists($id)) {
            try {
                return $ref[1] = $this->get($definition);
            } catch (\Throwable $e) {
                throw new ContainerException($this->aliasErrorMessage($id, $definition), $e);
            }
        }

        // cache any other associated value type and return it.
        if ($defined) {
            return $ref[1] = $definition;
        }

        // The given id is not defined in the container so try to instantiate a class named id. Throw a not
        // found exception when the id is not an existing class name.
        //
        // By the way this is yet nother absolute bullshit from phpstan. It forces to give ReflectionClass an
        // existing class name whereas it accepts any string and throws when it is not an interface/class/trait
        // name.
        //
        // Why should I typehint a string with a bullshit type to pass it to a constructor accepting strings?
        // Why should I duplicate a regular PHP behavior?
        // In which way does it prevent from making mistakes?
        if (!class_exists($id)) {
            throw new NotFoundException($id);
        }

        // reflect the class and throw a not found exception when it is an abstract class.
        $reflection = new \ReflectionClass($id);

        if ($reflection->isAbstract()) {
            throw new NotFoundException($id);
        }

        // reflect the constructor of the class and throw a container exception when it is not public.
        $constructor = $reflection->getConstructor();

        if ($constructor && !$constructor->isPublic()) {
            throw new NotFoundException($id);
        }

        // try to associate values to constructor parameters and throw a container exception when
        // something goes wrong.
        $args = [];

        $parameters = is_null($constructor) ? [] : $constructor->getParameters();

        foreach ($parameters as $parameter) {
            [$hasClass, $class, $error] = $this->typeClass($parameter);

            if ($hasClass && $id != $class) {
                try {
                    $args[] = $this->get($class);
                } catch (\Throwable $e) {
                    throw new ContainerException($this->parameterErrorMessage($id, $class, $parameter), $e);
                }
            } elseif ($hasClass && $id == $class) {
                throw new ContainerException($this->recursiveErrorMessage($id, $parameter));
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $args[] = null;
            } else {
                throw new ContainerException(sprintf($error, $id, $parameter->getName()));
            }
        }

        // cache and return the instance.
        return $ref[1] = $reflection->newInstance(...$args);
    }

    /**
     * @inheritdoc
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->map);
    }

    /**
     * @return array{0: boolean, 1: string, 2: string}
     */
    private function typeClass(\ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();

        if (is_null($type)) {
            return [false, '', '']; // no type means it is nullable
        }

        if ($type instanceof \ReflectionUnionType) {
            return [false, '', 'Container cannot instantiate %s: parameter $%s has union type'];
        }

        if ($type instanceof \ReflectionIntersectionType) {
            return [false, '', 'Container cannot instantiate %s: parameter $%s has intersection type'];
        }

        if (!$type instanceof \ReflectionNamedType) {
            // This block never happend. Just pleasing phpstan...
            throw new \LogicException;
        }

        if ($type->isBuiltin()) {
            return [false, '', 'Container cannot instantiate %s: parameter $%s type is not a class name'];
        }

        $class = $type->getName();

        if (!interface_exists($class) && !class_exists($class) && !trait_exists($class)) {
            return [false, '', sprintf(
                'Container cannot instantiate %%s: parameter $%%s type %s does not exist',
                $class
            )];
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable() && !array_key_exists($class, $this->map)) {
            return [false, '', sprintf(
                'Container cannot instantiate %%s: parameter $%%s type %s cannot be instantiated and should be defined in the container',
                $class,
            )];
        }

        return [true, $class, ''];
    }

    private function factoryErrorMessage(string $id): string
    {
        return sprintf(
            'Cannot get \'%s\' from the container: factory has thrown an uncaught exception',
            $id,
        );
    }

    private function aliasErrorMessage(string $id, string $value): string
    {
        return sprintf(
            'Cannot get \'%s\' from the container: getting \'%s\' value has thrown an uncaught exception',
            $id,
            $value,
        );
    }

    private function recursiveErrorMessage(string $id, \ReflectionParameter $parameter): string
    {
        return sprintf(
            'Container cannot instantiate %s: parameter $%s value has the same type, this would trigger infinite recursion',
            $id,
            $parameter->getName(),
        );
    }

    private function parameterErrorMessage(string $id, string $className, \ReflectionParameter $parameter): string
    {
        return sprintf(
            'Container cannot instantiate %s: getting parameter $%s value has thrown an uncaught exception (type: %s)',
            $id,
            $parameter->getName(),
            $className,
        );
    }
}
