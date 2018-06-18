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
        $tpl = "No entry '%s' was found in the container.";

        $msg = sprintf($tpl, $id);

        parent::__construct($msg);
    }
}
