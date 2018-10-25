<?php declare(strict_types=1);

namespace Quanta;

use Psr\Container\ContainerInterface;

use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

final class Container implements ContainerInterface
{
    /**
     * The map used by the container to retrieve entry values from their ids.
     *
     * Ids are actually mapped to arrays containing one or two elements:
     * - the first one is the factory producing the entry value
     * - the second one is the value produced by the factory
     *
     * The second value is set when `get($id)` is called for the first time and
     * is then used as a cache on subsequent calls. This way the container only
     * needs to perform one lookup in this map when retrieving the value of an
     * entry.
     *
     * @var array[]
     */
    private $map;

    /**
     * Constructor.
     *
     * The map is build by putting the given factories inside arrays and merging
     * it with the given previous map.
     *
     * The new factories overwrite the previous ones having the same ids.
     *
     * An InvalidArgumentException with an useful error message is thrown when
     * a factory is not a callable.
     *
     * @param callable[]    $factories
     * @param array[]       $previous
     * @throws \InvalidArgumentException
     */
    public function __construct(array $factories, array $previous = [])
    {
        try {
            $this->map = array_map([$this, 'array'], $factories) + $previous;
        }
        catch (\TypeError $e) {
            $msg = $this->factoryTypeErrorMessage($factories);

            throw new \InvalidArgumentException($msg);
        }
    }

    /**
     * Return a new container with an additional entry.
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
     * Return a new container with many additional entries.
     *
     * The eventual InvalidArgumentException thrown from the constructor is
     * rethrown from here. The reasoning behind this is: if the associative
     * array values could be type hinted as callable, the exception would be
     * thrown from this method, not from the constructor.
     *
     * @param callable[] $factories
     * @return \Quanta\Container
     * @throws \InvalidArgumentException
     */
    public function withEntries(array $factories): Container
    {
        try {
            return new Container($factories, $this->map);
        }
        catch (\InvalidArgumentException $e) {
            $msg = $this->factoryTypeErrorMessage($factories);

            throw new \InvalidArgumentException($msg);
        }
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // see $this->map
        $ref = &$this->map[$id];

        // Fail when the given id is not present in the map.
        if ($ref === null) throw new NotFoundException($id);

        // Return the entry value when already built.
        if (count($ref) == 2) return $ref[1];

        // Execute the factory and store the value it produced in $ref[1].
        // Any uncaught exception is wrapped in a ContainerException because it
        // allows to keep track of all the entries failing because of this
        // original exception. This should not be a problem because recovering
        // from a failling factory should not be a reasonable thing to do.
        try {
            return $ref[1] = ($ref[0])($this);
        }
        catch (\Throwable $e) {
            throw new ContainerException($id, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        return isset($this->map[$id]);
    }

    /**
     * Return an array containing the given factory.
     *
     * @param callable $factory
     * @return array
     */
    private function array(callable $factory): array
    {
        return [$factory];
    }

    /**
     * Return whether the given value is not a callable.
     *
     * @param mixed $value
     * @return bool
     */
    private function notCallable($value): bool
    {
        return ! is_callable($value);
    }

    /**
     * Return the error message of the exception thrown when a factory is not
     * a callable.
     *
     * @param array $factories
     * @return string
     */
    private function factoryTypeErrorMessage(array $factories): string
    {
        $invalid = array_filter($factories, [$this, 'notCallable']);

        $id = key($invalid);
        $value = current($invalid);

        $tpl = 'The \'%s\' container entry is associated to %s, callable expected';

        return sprintf($tpl, $id, new Printable($value));
    }
}
