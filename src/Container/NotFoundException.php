<?php declare(strict_types=1);

namespace Quanta\Container;

use Exception;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $tpl = "Identifier '%s' is not registered in the container.";

        $msg = sprintf($tpl, $id);

        parent::__construct($msg);
    }
}
