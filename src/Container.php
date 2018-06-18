<?php declare(strict_types=1);

namespace Quanta;

use Throwable;

use Psr\Container\ContainerInterface;

use Quanta\Container\ContainerException;
use Quanta\Container\NotFoundException;

final class Container implements ContainerInterface
{
    /**
     * The id to entry map.
     *
     * @var array
     */
    private $entries;

    /**
     * The id to factory map.
     *
     * @var callable[]
     */
    private $factories;

    /**
     * Constructor.
     *
     * @param array         $entries
     * @param callable[]    $factories
     */
    public function __construct(array $entries = [], array $factories = [])
    {
        $this->entries = $entries;
        $this->factories = $factories;
    }

    /**
     * Return a new container with an additional entry.
     *
     * When an entry is already associated with the given id, it is overwritten
     * by the given entry.
     *
     * @param string    $id
     * @param mixed     $entry
     * @return \Quanta\Container
     */
    public function withEntry(string $id, $entry): Container
    {
        return new Container([$id => $entry] + $this->entries, $this->factories);
    }

    /**
     * Return a new container with additional entries.
     *
     * @param array $entries
     * @return \Quanta\Container
     */
    public function withEntries(array $entries): Container
    {
        return array_reduce(array_keys($entries), function ($container, $id) use ($entries) {
            return $container->withEntry($id, $entries[$id]);
        }, $this);
    }

    /**
     * Return a new container with an additional factory.
     *
     * When a factory is already associated with the given id, it is overwritten
     * by the given factory.
     *
     * @param string    $id
     * @param callable  $factory
     * @return \Quanta\Container
     */
    public function withFactory(string $id, callable $factory): Container
    {
        return new Container($this->entries, [$id => $factory] + $this->factories);
    }

    /**
     * Return a new container with additional factories.
     *
     * @param callable[] $factories
     * @return \Quanta\Container
     */
    public function withFactories(array $factories): Container
    {
        return array_reduce(array_keys($factories), function ($container, $id) use ($factories) {
            return $container->withFactory($id, $factories[$id]);
        }, $this);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // use array_key_exists instead of isset because an entry can be null.
        if (array_key_exists($id, $this->entries)) {
            return $this->entries[$id];
        }

        if (isset($this->factories[$id])) {
            try {
                return $this->entries[$id] = $this->factories[$id]($this);
            }
            catch (Throwable $e) {
                throw new ContainerException($id, $e);
            }
        }

        throw new NotFoundException($id);
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        // use array_key_exists instead of isset because an entry can be null.
        return array_key_exists($id, $this->entries)
            ?: isset($this->factories[$id]);
    }
}
