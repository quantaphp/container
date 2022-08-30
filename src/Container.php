<?php

declare(strict_types=1);

namespace Quanta;

use Psr\Container\ContainerInterface;

use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

final class Container implements ContainerInterface
{
    /**
     * The id to entry map.
     *
     * An entry is an array with the factory as first element and the cached
     * value produced by the factory as second element.
     *
     * The second value is populated when the factory is invoked on the first
     * `get($id)` method call.
     *
     * @var array<string, null|array{0: callable|null, 1?: mixed}>
     */
    private array $map;

    /**
     * Build a container from an iterable containing factories or values.
     *
     * @param iterable<string, mixed> $values
     * @return \Quanta\Container
     * @throws \InvalidArgumentException
     */
    public static function factories(iterable $values): self
    {
        $map = [];

        foreach ($values as $id => $value) {
            try {
                $id = strval($id);
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException(sprintf(
                    'Argument 1 passed to %s::factories() must be an iterable with stringable keys, %s given',
                    self::class,
                    gettype($id),
                ), 0, $e);
            }

            $map[$id] = is_callable($value) ? [$value] : [null, $value];
        }

        return new self($map);
    }

    /**
     * Constructor.
     *
     * @param array<string, array{0: callable|null, 1?: mixed}> $map
     */
    private function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @inheritdoc
     */
    public function get(string $id)
    {
        // Get a reference to the array associated to this id in the map.
        $ref = &$this->map[$id];

        // Fail when the given id is not present in the map (= null ref).
        if (is_null($ref)) {
            throw new NotFoundException($id);
        }

        // Return the entry when existing (= offset 1 is set).
        if (array_key_exists(1, $ref)) {
            return $ref[1];
        }

        // Make phpstan happy... Exception never happen.
        if (is_null($factory = $ref[0])) throw new \Exception;

        // Execute the factory and cache its result.
        try {
            return $ref[1] = $factory($this);
        }

        // Any uncaught exception is wrapped in a ContainerException because it
        // allows to keep track of all the entries failing because of this
        // original exception.
        // This is not possible anymore to recover from a specific exception
        // thrown from a factory but it does not make sense anyway (usecase ?)
        catch (\Throwable $e) {
            throw new ContainerException($id, 0, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function has(string $id): bool
    {
        return isset($this->map[$id]);
    }
}
