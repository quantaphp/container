<?php declare(strict_types=1);

namespace Quanta;

use Throwable;

use Psr\Container\ContainerInterface;

use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;
use Quanta\Container\FactoryTypeException;

final class Container implements ContainerInterface
{
    /**
     * The map used by the container to retrieve entries.
     *
     * Ids are actually mapped to arrays containing a factory as first element
     * and eventually the value produced by this factory as second element (set
     * when using the `get($id)` method).
     *
     * It allows to bind the factories and their results together in order to
     * perform only one lookup in this map when using `get($id)`.
     *
     * @var array[]
     */
    private $map;

    /**
     * Constructor.
     *
     * Build a map by putting the given factories inside arrays and merge it
     * with the previous map. The new factories overwrite the previous ones
     * associated with the same ids.
     *
     * Not so good to have code in constructor but this is the only way both to
     * let the end user build a container with the actual factories and to have
     * a data structure allowing a single lookup get method.
     *
     * @param callable[]    $factories
     * @param array[]       $previous
     */
    public function __construct(array $factories, array $previous = [])
    {
        $this->map = $this->map($factories) + $previous;
    }

    /**
     * Put an array around the given factory.
     *
     * `$factory` is mixed because the container is instantiated with an array,
     * so the factory can have any type. `get($id)` will fail nicely when the
     * factory is not a callable.
     *
     * @param mixed $factory
     * @return array
     */
    private function array($factory): array
    {
        return [$factory];
    }

    /**
     * Build a map by putting the given factories inside arrays.
     *
     * @param callable[] $factories
     * @return array[]
     */
    private function map(array $factories): array
    {
        return array_map([$this, 'array'], $factories);
    }

    /**
     * Return a new container with an additional factory.
     *
     * @param string    $id
     * @param callable  $factory
     * @return \Quanta\Container
     */
    public function with(string $id, callable $factory): Container
    {
        return new Container([$id => $factory], $this->map);
    }

    /**
     * Return a new container with additional factories.
     *
     * @param callable[] $factories
     * @return \Quanta\Container
     */
    public function withFactories(array $factories): Container
    {
        return new Container($factories, $this->map);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // $ref[0] contains the factory.
        // $ref[1] contains the factory results when present.
        $ref = &$this->map[$id];

        // fail when the entry is not in the map.
        if ($ref === null) throw new NotFoundException($id);

        // return the entry when already built.
        if (count($ref) == 2) return $ref[1];

        // execute the factory and store its result in $ref[1].
        // check if the factory is actually a callable only on failure.
        try {
            return $ref[1] = ($ref[0])($this);
        }
        catch (Throwable $e) {
            throw is_callable($ref[0])
                ? new ContainerException($id, $e)
                : new FactoryTypeException($id, $ref[0]);
        }
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return isset($this->map[$id]);
    }
}
