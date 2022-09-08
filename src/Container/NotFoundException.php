<?php

declare(strict_types=1);

namespace Quanta\Container;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    const MESSAGE = 'No \'%s\' entry defined in the container';

    public static function message(string $id): string
    {
        return sprintf(self::MESSAGE, $id);
    }

    public function __construct(string $id)
    {
        parent::__construct(self::message($id));
    }
}
