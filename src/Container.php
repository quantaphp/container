<?php declare(strict_types=1);

namespace Quanta;

use Psr\Container\ContainerInterface;

use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

use function Quanta\Exceptions\areAllTypedAs;
use Quanta\Exceptions\ArrayArgumentTypeErrorMessage;

final class Container implements ContainerInterface
{
    /**
     * The map used by the container to retrieve entries from their ids.
     *
     * Ids are actually mapped to arrays containing one or two elements:
     * - the first one is the factory producing the entry
     * - the second one is the cached result of the factory
     *
     * The second value is set when `get($id)` is called for the first time and
     * is used as a cache on subsequent calls.
     *
     * This data structure allows the container to perform only one lookup when
     * retrieving an entry.
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
        if (! areAllTypedAs('callable', $factories)) {
            throw new \InvalidArgumentException(
                (string) new ArrayArgumentTypeErrorMessage(1, 'callable', $factories)
            );
        }

        $this->map = array_map([$this, 'nested'], $factories) + $previous;
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
            throw new \InvalidArgumentException(
                (string) new ArrayArgumentTypeErrorMessage(1, 'callable', $factories)
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // Ensure the id is a string.
        if (! is_string($id)) {
            throw new \InvalidArgumentException(
                $this->invalidIdentifierTypeErrorMessage('get', $id)
            );
        }

        /**
         * Get a reference to the array associated to this id in the map.
         *
         * Docblock for phpstan.
         *
         * @var array|null
         */
        $ref = &$this->map[$id];

        // Fail when the given id is not present in the map (= null ref).
        if (is_null($ref)) throw new NotFoundException($id);

        // Return the entry when cached (= the array has two values).
        if (count($ref) == 2) return $ref[1];

        // Execute the factory and cache its result.
        try {
            return $ref[1] = ($ref[0])($this);
        }

        // Any uncaught exception is wrapped in a ContainerException because it
        // allows to keep track of all the entries failing because of this
        // original exception.
        // This is not possible anymore to recover from a specific exception
        // thrown from a factory but it does not make sense anyway (usecase ?)
        catch (\Throwable $e) {
            throw new ContainerException($id, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function has($id)
    {
        // Ensure the id is a string.
        if (! is_string($id)) {
            throw new \InvalidArgumentException(
                $this->invalidIdentifierTypeErrorMessage('has', $id)
            );
        }

        // Return whether the given id is in the map.
        return isset($this->map[$id]);
    }

    /**
     * Return a reference to the array associated to the given id.
     *
     * @param string $id
     * @return array|null
     */
    private function &ref(string $id)
    {
        return $this->map[$id];
    }

    /**
     * Return an array containing the given factory.
     *
     * @param callable $factory
     * @return array
     */
    private function nested(callable $factory): array
    {
        return [$factory];
    }

    /**
     * Return the message of the exception thrown when an identifier is not a
     * string.
     *
     * @param string    $method
     * @param mixed     $id
     * @return string
     */
    private function invalidIdentifierTypeErrorMessage(string $method, $id): string
    {
        $tpl = 'Argument 1 passed to %s::%s method must be of the type string, %s given';

        return sprintf($tpl, Container::class, $method, gettype($id));
    }
}
