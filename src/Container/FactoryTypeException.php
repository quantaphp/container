<?php declare(strict_types=1);

namespace Quanta\Container;

use Exception;

use Psr\Container\ContainerExceptionInterface;

class FactoryTypeException extends Exception implements ContainerExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $tpl = "Failed to get the entry '%s' from the container because its factory is not a callable.";

        $msg = sprintf($tpl, $id);

        parent::__construct($msg);
    }
}
