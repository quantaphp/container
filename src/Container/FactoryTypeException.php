<?php declare(strict_types=1);

namespace Quanta\Container;

use Exception;

use Psr\Container\ContainerExceptionInterface;

use Quanta\Printable;

class FactoryTypeException extends Exception implements ContainerExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string    $id
     * @param mixed     $factory
     */
    public function __construct(string $id, $factory)
    {
        $tpl = "Failed to get the entry '%s' from the container because its factory is not a callable. The factory value is %s.";

        $msg = sprintf($tpl, $id, new Printable($factory));

        parent::__construct($msg);
    }
}
