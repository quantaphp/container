<?php

declare(strict_types=1);

use Quanta\Container;

require_once __DIR__ . '/ContainerTestAbstract.php';

class ContainerIteratorAggregateTest extends ContainerTestAbstract
{
    protected function getContainer(array $map): Container
    {
        return new Container(new class($map) implements IteratorAggregate
        {
            public function __construct(array $map)
            {
                $this->map = $map;
            }

            public function getIterator(): Iterator
            {
                return new ArrayIterator($this->map);
            }
        });
    }
}
