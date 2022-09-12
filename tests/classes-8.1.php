<?php

declare(strict_types=1);

final class TestClassWithIntersectionParameterType
{
    public function __construct(TestIntersectionDependency1&TestIntersectionDependency2 $dep)
    {
    }
}

final class TestIntersectionDependency1
{
}

final class TestIntersectionDependency2
{
}
