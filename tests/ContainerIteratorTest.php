<?php

declare(strict_types=1);

use Quanta\Container;

require_once __DIR__ . '/ContainerTestAbstract.php';

class ContainerIteratorTest extends ContainerTestAbstract
{
    protected function getContainer(array $map): Container
    {
        return new Container(new ArrayIterator($map));
    }
}
