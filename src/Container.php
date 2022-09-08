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
                throw new ContainerException(ContainerException::factory($id), $e);
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
                throw new ContainerException(ContainerException::alias($id, $definition), $e);
            }
        }

        // cache any other associated value type and return it.
        if ($defined) {
            return $ref[1] = $definition;
        }

        // The given id is not defined in the container so try to instantiate a class named id. Throw a not
        // found exception when the id is not an existing class name.
        if (!class_exists($id)) {
            throw new NotFoundException($id);
        }

        // reflect the class and throw a not found exception when it is an abstract class.
        $reflection = new \ReflectionClass($id);

        if ($reflection->isAbstract()) {
            throw new ContainerException(ContainerException::abstract($id));
        }

        // reflect the constructor of the class and throw a container exception when it is not public.
        $constructor = $reflection->getConstructor();

        if ($constructor && !$constructor->isPublic()) {
            throw new ContainerException(ContainerException::private($id));
        }

        // try to associate values to constructor parameters and throw a container exception when
        // something goes wrong.
        $args = [];

        $parameters = is_null($constructor) ? [] : $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            $hasDefault = $parameter->isDefaultValueavailable() || $parameter->allowsNull();

            $isDefined = $type instanceof \ReflectionNamedType
                && !$type->isBuiltin()
                && $this->has($type->getName());

            if (!$isDefined && $hasDefault) {
                $args[] = $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : null;
            } elseif ($type instanceof \ReflectionUnionType) {
                throw new ContainerException(ContainerException::typeUnion($id, $name));
            } elseif ($type instanceof \ReflectionIntersectionType) {
                throw new ContainerException(ContainerException::typeIntersection($id, $name));
            } elseif ($type instanceof \ReflectionNamedType && $type->isBuiltIn()) {
                throw new ContainerException(ContainerException::typeBuiltin($id, $name));
            } elseif ($type instanceof \ReflectionNamedType && $id == $type->getName()) {
                throw new ContainerException(ContainerException::typeRecursive($id, $name));
            } elseif ($type instanceof \ReflectionNamedType) {
                try {
                    $args[] = $this->get($type->getName());
                } catch (\Throwable $e) {
                    throw new ContainerException(ContainerException::typeError($id, $type->getName(), $name), $e);
                }
            } else {
                throw new \LogicException;
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
        return array_key_exists($id, $this->map) || class_exists($id);
    }
}
