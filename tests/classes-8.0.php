<?php

declare(strict_types=1);

final class TestClassWithUnionParameterType
{
    public function __construct(TestUnionDependency1|TestUnionDependency2 $dep)
    {
    }
}

final class TestUnionDependency1
{
}

final class TestUnionDependency2
{
}
