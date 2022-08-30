<?php

declare(strict_types=1);

namespace Quanta;

use Psr\Container\ContainerInterface;

use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

final class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $map;

    /**
     * @var array<string, mixed>
     */
    private array $cached;

    /**
     * @var object
     */
    private $null;

    /**
     * Constructor.
     *
     * @param iterable<string, mixed> $map
     */
    public function __construct(iterable $map = [])
    {
        $this->map = [];
        $this->cached = [];
        $this->null = new class
        {
        };

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

            $this->map[$id] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function get(string $id)
    {
        try {
            // when the id is associated to a cached value.
            if ($cached = $this->cached[$id] ?? $this->null !== $this->null) {
                return $cached;
            }

            // check if the id is defined in the map.
            $defined = ($value = $this->map[$id] ?? $this->null) !== $this->null;

            // when id is associated to a callable.
            if ($defined && is_callable($value)) {
                return $this->cached[$id] = $value($this);
            }

            // when id => value pair is an interface alias.
            if ($defined && is_string($value) && interface_exists($id)) {
                return $this->cached[$id] = $this->get($value);
            }

            // when the id is associated to a constant value.
            if ($defined) {
                return $this->cached[$id] = $value;
            }

            // when the id is not defined and is a class name.
            if (class_exists($id)) {
                return $this->cached[$id] = $this->autowired($id);
            }
        }

        // Any uncaught exception is wrapped in a ContainerException because it
        // allows to keep track of all the entries failing because of this
        // original exception.
        // This is not possible anymore to recover from a specific exception
        // thrown from a factory but it does not make sense anyway (usecase ?)
        catch (\Throwable $e) {
            throw new ContainerException($id, 0, $e);
        }

        // the id cant be associated to any value, throw a not found exception.
        throw new NotFoundException($id);
    }

    /**
     * @inheritdoc
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->map) || class_exists($id);
    }

    /**
     * @param class-string $class
     * @return object
     */
    private function autowired(string $class)
    {
        $args = [];

        $reflection = new \ReflectionClass($class);

        $constructor = $reflection->getConstructor();

        if (is_null($constructor)) {
            return new $class;
        }

        if (!$constructor->isPublic()) {
            throw new \LogicException(
                sprintf('Error while autowiring class %s: constructor is not public', $class)
            );
        }

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            $id = (string) $type;

            if (!$parameter->hasType()) {
                throw new \LogicException(
                    sprintf('Error while autowiring class %s: parameter $%s has no type', $class, $name)
                );
            }

            if (!$type instanceof \ReflectionNamedType) {
                throw new \LogicException(
                    sprintf('Error while autowiring class %s: parameter $%s type is not named', $class, $name)
                );
            }

            if ($type->isBuiltin()) {
                throw new \LogicException(
                    sprintf('Error while autowiring class %s: parameter $%s type is not a class name', $class, $name)
                );
            }

            if (!$this->has($id)) {
                throw new \LogicException(
                    sprintf('Error while autowiring class %s: parameter $%s type is not defined in the container (%s)', $class, $name, $id),
                );
            }

            $args[] = $this->get($id);
        }

        return new $class(...$args);
    }
}
