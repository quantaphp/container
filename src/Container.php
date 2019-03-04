<?php declare(strict_types=1);

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
     * This data structure allows the container to perform only one lookup when
     * retrieving an entry.
     *
     * @var array[]
     */
    private $map;

    /**
     * Constructor.
     *
     * The map is build by creating entries from the factories.
     *
     * @see $this->map
     *
     * @param callable[] $factories
     * @throws \InvalidArgumentException
     */
    public function __construct(array $factories)
    {
        $result = \Quanta\ArrayTypeCheck::result($factories, 'callable');

        if (! $result->isValid()) {
            throw new \InvalidArgumentException(
                $result->message()->constructor($this, 1)
            );
        }

        $this->map = array_map([$this, 'entry'], $factories);
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
     * Return an entry from the given factory.
     *
     * @see $this->map
     *
     * @param callable $factory
     * @return array
     */
    private function entry(callable $factory): array
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
