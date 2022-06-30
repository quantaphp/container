<?php

declare(strict_types=1);

namespace Quanta\Container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    /**
     * Constructor.
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        parent::__construct(sprintf('No \'%s\' entry in the container', $id));
    }
}
