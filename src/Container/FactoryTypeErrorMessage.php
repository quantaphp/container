<?php declare(strict_types=1);

namespace Quanta\Container;

use Quanta\Printable;

final class FactoryTypeErrorMessage
{
    /**
     * The sprintf template used for the error message.
     *
     * @var string
     */
    const TPL = 'The \'%s\' container entry is associated to %s, callable expected';

    /**
     * The invalid array of factories.
     *
     * @var array
     */
    private $factories;

    /**
     * Constructor.
     *
     * @param array $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * Return the error message.
     *
     * @return string
     */
    public function __toString()
    {
        $valid = array_filter($this->factories, 'is_callable');

        $invalid = array_diff_key($this->factories, $valid);

        $id = key($invalid);
        $value = current($invalid);

        return sprintf(self::TPL, $id, new Printable($value));
    }
}
