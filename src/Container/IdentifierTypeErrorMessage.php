<?php declare(strict_types=1);

namespace Quanta\Container;

use Quanta\Printable;

final class IdentifierTypeErrorMessage
{
    /**
     * The sprintf template used for the error message.
     *
     * @var string
     */
    const TPL = 'Container entry identifier must be of the type string, %s given';

    /**
     * The invalid id.
     *
     * @var mixed
     */
    private $id;

    /**
     * Constructor.
     *
     * @param mixed $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Return the error message.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(self::TPL, new Printable($this->id));
    }
}
