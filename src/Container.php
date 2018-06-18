<?php declare(strict_types=1);

namespace Quanta;

use Throwable;

use Psr\Container\ContainerInterface;

use Quanta\Container\ContainerException;
use Quanta\Container\NotFoundException;

class Container implements ContainerInterface
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
    public function __construct(array $entries, array $factories = [])
    {
        $this->entries = $entries;
        $this->factories = $factories;
    }

    /**
     * Return a new container with additional entries. New entries with the same
     * keys erase the current ones.
     *
     * @param array $entries
     * @return \Quanta\Container
     */
    public function withEntries(array $entries): Container
    {
        return new Container($entries + $this->entries, $this->factories);
    }

    /**
     * Return a new container with additional factories. New factories with the
     * same keys erase the current ones.
     *
     * @param callable[] $factories
     * @return \Quanta\Container
     */
    public function withFactories(array $factories): Container
    {
        return new Container($this->entries, $factories + $this->factories);
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->entries)) {
            return $this->entries[$id];
        }

        if (array_key_exists($id, $this->factories)) {
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
        return array_key_exists($id, $this->entries)
            ?: array_key_exists($id, $this->factories);
    }
}
