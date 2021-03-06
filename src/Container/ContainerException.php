<?php declare(strict_types=1);

namespace Quanta\Container;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string        $id
     * @param int           $code
     * @param \Throwable    $previous
     */
    public function __construct(string $id, int $code, \Throwable $previous)
    {
        $tpl = 'The factory producing the \'%s\' container entry has thrown an uncaught exception';

        parent::__construct(sprintf($tpl, $id), $code, $previous);
    }
}
