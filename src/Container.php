<?php declare(strict_types=1);

namespace Quanta;

use Psr\Container\ContainerInterface;

use Quanta\Callbacks\Nest;
use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;
use Quanta\Exceptions\ArrayTypeCheckTrait;
use Quanta\Exceptions\ArgumentTypeErrorMessage;
use Quanta\Exceptions\ArrayArgumentTypeErrorMessage;

final class Container implements ContainerInterface
{
    use ArrayTypeCheckTrait;

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
        if (! $this->areAllTypedAs($factories, 'callable')) {
            throw new \InvalidArgumentException(
                (string) new ArrayArgumentTypeErrorMessage(1, 'callable', $factories)
            );
        }

        $this->map = array_map(new Nest, $factories) + $previous;
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
        if (! $this->areAllTypedAs($factories, 'callable')) {
            throw new \InvalidArgumentException(
                (string) new ArrayArgumentTypeErrorMessage(1, 'callable', $factories)
            );
        }

        return new Container($factories, $this->map);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // Ensure the id is a string.
        if (! is_string($id)) {
            throw new \InvalidArgumentException(
                (string) new ArgumentTypeErrorMessage(1, 'string', $id)
            );
        }

        // See $this->map
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
        // Ensure the id is a string.
        if (! is_string($id)) {
            throw new \InvalidArgumentException(
                (string) new ArgumentTypeErrorMessage(1, 'string', $id)
            );
        }

        // Return whether the given id is in the map.
        return isset($this->map[$id]);
    }
}
