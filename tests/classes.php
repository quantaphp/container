<?php

declare(strict_types=1);

interface TestUndefinedInterface
{
}

abstract class TestUndefinedAbstractClass
{
}

final class TestUndefinedClass
{
}

final class TestUndefinedClassWithProtectedConstructor
{
    protected function __construct()
    {
    }
}

final class TestUndefinedClassWithPrivateConstructor
{
    private function __construct()
    {
    }
}

interface TestAliasInterface
{
}

class TestAliasClass implements TestAliasInterface
{
}

interface TestThrowingAliasInterface
{
}

class TestThrowingAliasClass implements TestThrowingAliasInterface
{
}

class TestAutowiredClass
{
    public $dep_defined_interface;
    public $dep_defined_abstract;
    public $dep_defined_class;
    public $dep_defined_class_protected;
    public $dep_defined_class_private;
    public $dep_undefined_interface;
    public $dep_undefined_class;
    public $dep_nullable_value;
    public $dep_default_value;

    public function __construct(
        TestDefinedInterface $dep_defined_interface,
        TestDefinedAbstract $dep_defined_abstract,
        TestDefinedClass $dep_defined_class,
        TestDefinedClassWithProtectedConstructor $dep_defined_class_protected,
        TestDefinedClassWithPrivateConstructor $dep_defined_class_private,
        ?TestUndefinedInterface $dep_undefined_interface,
        TestUndefinedClass $dep_undefined_class,
        ?int $dep_nullable_value,
        int $dep_default_value = 1,
    ) {
        $this->dep_defined_interface = $dep_defined_interface;
        $this->dep_defined_abstract = $dep_defined_abstract;
        $this->dep_defined_class = $dep_defined_class;
        $this->dep_defined_class_protected = $dep_defined_class_protected;
        $this->dep_defined_class_private = $dep_defined_class_private;
        $this->dep_undefined_interface = $dep_undefined_interface;
        $this->dep_undefined_class = $dep_undefined_class;
        $this->dep_nullable_value = $dep_nullable_value;
        $this->dep_default_value = $dep_default_value;
    }
}

interface TestDefinedInterface
{
}

class TestDefinedInterfaceImpl implements TestDefinedInterface
{
}

abstract class TestDefinedAbstract
{
}

final class TestDefinedAbstractImpl extends TestDefinedAbstract
{
}

final class TestDefinedClass
{
}

final class TestDefinedClassWithProtectedConstructor
{
    public static function instance(): self
    {
        return new self;
    }

    protected function __construct()
    {
    }
}

final class TestDefinedClassWithPrivateConstructor
{
    public static function instance(): self
    {
        return new self;
    }

    private function __construct()
    {
    }
}

final class TestAutowiredClassWithDeepDependencies
{
    public $dep;

    public function __construct(TestAutowiredClassDependency1 $dep)
    {
        $this->dep = $dep;
    }
}

final class TestAutowiredClassDependency1
{
    public $dep;

    public function __construct(TestAutowiredClassDependency2 $dep)
    {
        $this->dep = $dep;
    }
}

final class TestAutowiredClassDependency2
{
}

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

final class TestClassWithBuiltinParameterType
{
    public function __construct(string $dep)
    {
    }
}

// test for classes with recursive parameters.
final class TestClassWithRecursiveParameter
{
    public function __construct(TestClassWithRecursiveParameter $dep)
    {
    }
}

// test for classes having a throwing parameter.
final class TestClassWithThrowingParameterType
{
    public function __construct(TestThrowingParameterType $dep)
    {
    }
}

final class TestThrowingParameterType
{
}
