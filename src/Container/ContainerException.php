<?php declare(strict_types=1);

namespace Quanta\Container;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string        $id
     * @param \Throwable    $previous
     */
    public function __construct(string $id, \Throwable $previous)
    {
        $tpl = 'The factory producing the \'%s\' container entry has thrown an uncaught exception';

        $msg = sprintf($tpl, $id);

        parent::__construct($msg, 0, $previous);
    }
}
