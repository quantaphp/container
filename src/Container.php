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
     * @var array<string, null|array<callable>|array<callable, mixed>>
     */
    private $map;

    /**
     * Named constructor building the map from an array of factories.
     *
     * @param callable[] $factories
     * @return \Quanta\Container
     * @throws \InvalidArgumentException
     */
    public static function factories(array $factories): self
    {
        $map = [];

        foreach ($factories as $id => $factory) {
            if (!is_callable($factory)) {
                throw new \InvalidArgumentException(sprintf(
                    'Argument 1 passed to %s::factories() must be an array of callable values, %s given for key \'%s\'',
                    self::class,
                    gettype($factory),
                    $id,
                ));
            }

            $map[(string) $id] = [$factory];
        }

        return new self($map);
    }

    /**
     * Named constructor building the map from a list of glob patterns.
     *
     * Matched files must return an array of callables.
     *
     * @param string $pattern
     * @param string ...$patterns
     * @return \Quanta\Container
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     */
    public static function files(string $pattern, string ...$patterns): self
    {
        $map = [];

        $patterns = array_merge([$pattern], $patterns);

        foreach ($patterns as $pattern) {
            $files = glob($pattern);

            if ($files === false) {
                throw new \RuntimeException(sprintf(
                    'Failed to use \'%s\' as glob pattern',
                    $pattern,
                ));
            }

            $files = array_filter($files, 'is_file');

            foreach ($files as $file) {
                $factories = require $file;

                if (!is_array($factories)) {
                    throw new \UnexpectedValueException(sprintf(
                        'Value returned by file \'%s\' must be an array of callable values, %s returned',
                        $file,
                        gettype($factories),
                    ));
                }

                foreach ($factories as $id => $factory) {
                    if (!is_callable($factory)) {
                        throw new \UnexpectedValueException(sprintf(
                            'Value returned by file \'%s\' must be an array of callable values, %s given for key \'%s\'',
                            $file,
                            gettype($factory),
                            $id,
                        ));
                    }

                    $map[(string) $id] = [$factory];
                }
            }
        }

        return new self($map);
    }

    /**
     * Return the message of the exception thrown when an identifier is not a string.
     *
     * @param string    $method
     * @param mixed     $id
     * @return string
     */
    private static function invalidIdentifierTypeErrorMessage(string $method, $id): string
    {
        $tpl = 'Argument 1 passed to %s::%s() must be of the type string, %s given';

        return sprintf($tpl, self::class, $method, gettype($id));
    }

    /**
     * Constructor.
     *
     * @param array<string, array<callable>> $map
     */
    private function __construct(array $map)
    {
        $this->map = $map;
    }

    /**
     * @inheritdoc
     */
    public function get($id)
    {
        // Ensure the id is a string.
        if (!is_string($id)) {
            throw new \InvalidArgumentException(
                self::invalidIdentifierTypeErrorMessage('get', $id)
            );
        }

        // Get a reference to the array associated to this id in the map.
        $ref = &$this->map[$id];

        // Fail when the given id is not present in the map (= null ref).
        if (is_null($ref)) {
            throw new NotFoundException($id);
        }

        // Return the entry when cached (= the array has two values).
        if (count($ref) == 2) {
            return $ref[1];
        }

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
        if (!is_string($id)) {
            throw new \InvalidArgumentException(
                self::invalidIdentifierTypeErrorMessage('has', $id)
            );
        }

        // Return whether the given id is in the map.
        return isset($this->map[$id]);
    }
}
