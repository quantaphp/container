<?php declare(strict_types=1);

namespace Quanta\Container;

use Throwable;
use Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends Exception implements ContainerExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string        $id
     * @param \Throwable    $previous
     */
    public function __construct(string $id, Throwable $previous)
    {
        $tpl = "Failed to get '%s' from the container.";

        $msg = sprintf($tpl, $id);

        parent::__construct($msg, 0, $previous);
    }
}
