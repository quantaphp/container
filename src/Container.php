<?php declare(strict_types=1);

namespace Quanta;

use stdClass;
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
     * Ids are mapped to anonymous objects with a {0} attribute containing a
     * factory and an optional {1} attribute containing the value produced by
     * this factory.
     *
     * It allows to bind the factories and their results together in order to
     * perform only one lookup in this map when using `get($id)`.
     *
     * @var \stdClass[]
     */
    private $map;

    /**
     * Constructor.
     *
     * Wrap anonymous objects around the given factories and merge them with the
     * previous ones. The new anonymous objects overwrite the previous ones
     * associated with the same ids.
     *
     * Not so good to have code in constructor but this is the only way both to
     * let the end user build a container with the actual factories and to have
     * a data structure allowing a single lookup get method.
     *
     * @param callable[]    $factories
     * @param \stdClass[]   $previous
     */
    public function __construct(array $factories, array $previous = [])
    {
        $this->map = $this->map($factories) + $previous;
    }

    /**
     * Wrap an anonymous object around the given factory.
     *
     * `$factory` is mixed because the container is instantiated with an array,
     * so the factory can have any type. `get($id)` will fail nicely when `$id`
     * is associated with a non callable factory.
     *
     * @param mixed $factory
     * @return \stdClass
     */
    private function o($factory): stdClass
    {
        return (object) [$factory];
    }

    /**
     * Wrap anonymous objects around the given factories.
     *
     * @param callable[] $factories
     * @return \stdClass[]
     */
    private function map(array $factories): array
    {
        return array_map([$this, 'o'], $factories);
    }

    /**
     * Return a new container with an additional factory.
     *
     * @param string    $id
     * @param callable  $factory
     * @return \Quanta\Container
     */
    public function withFactory(string $id, callable $factory): Container
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
        // no double lookup thanks to the null coalesce operator.
        $o = $this->map[$id] ?? false;

        if (! $o) throw new NotFoundException($id);

        // $o->{0} contains the factory.
        // $o->{1} contains the factory results when present.
        if (property_exists($o, '1')) return $o->{1};

        // execute the factory and store its result in $o->{1}.
        // check if the factory is actually a callable only on failure.
        try {
            return $o->{1} = ($o->{0})($this);
        }
        catch (Throwable $e) {
            throw is_callable($o->{0})
                ? new ContainerException($id, $e)
                : new FactoryTypeException($id, $o->{0});
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
