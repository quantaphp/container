<?php

declare(strict_types=1);

// test for interface aliases.
interface TestAliasInterface
{
}

class TestAliasClass implements TestAliasInterface
{
}

// test for all handled cases of autowiring.
class TestAutowiredClass
{
    public $dep_defined_interface;
    public $dep_defined_abstract;
    public $dep_defined_class;
    public $dep_undefined_nullable_interface;
    public $dep_undefined_nullable_abstract;
    public $dep_undefined_class;
    public $dep_nullable_value;
    public $dep_default_value;

    public function __construct(
        TestDepDefinedInterface $dep_defined_interface,
        TestDepDefinedAbstract $dep_defined_abstract,
        TestDepDefinedClass $dep_defined_class,
        ?TestDepUndefinedNullableInterface $dep_undefined_nullable_interface,
        ?TestDepUndefinedNullableAbstract $dep_undefined_nullable_abstract,
        TestDepUndefinedClass $dep_undefined_class,
        ?int $dep_nullable_value,
        int $dep_default_value = 1,
    ) {
        $this->dep_defined_interface = $dep_defined_interface;
        $this->dep_defined_abstract = $dep_defined_abstract;
        $this->dep_defined_class = $dep_defined_class;
        $this->dep_undefined_nullable_interface = $dep_undefined_nullable_interface;
        $this->dep_undefined_nullable_abstract = $dep_undefined_nullable_abstract;
        $this->dep_undefined_class = $dep_undefined_class;
        $this->dep_nullable_value = $dep_nullable_value;
        $this->dep_default_value = $dep_default_value;
    }
}

interface TestDepDefinedInterface
{
}

class TestDepDefinedInterfaceImpl implements TestDepDefinedInterface
{
}

abstract class TestDepDefinedAbstract
{
}

final class TestDepDefinedAbstractImpl extends TestDepDefinedAbstract
{
}

final class TestDepDefinedClass
{
}

final class TestDepUndefinedClass
{
}

interface TestDepUndefinedNullableInterface
{
}

abstract class TestDepUndefinedNullableAbstract
{
}

// test for recursive autowiring.
final class TestAutowiredClassWithDependencies
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

// test for autowiring errors.
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

final class TestClassWithNonExistingParameterType
{
    public function __construct(NonExistingClass $dep)
    {
    }
}

final class TestClassWithUndefinedInterfaceParameterType
{
    public function __construct(TestUndefinedInterface $dep)
    {
    }
}

final class TestClassWithUndefinedAbstractClassParameterType
{
    public function __construct(TestUndefinedAbstractClass $dep)
    {
    }
}

final class TestClassWithProtectedConstructorClassParameterType
{
    public function __construct(TestClassWithProtectedConstructor $dep)
    {
    }
}

final class TestClassWithPrivateConstructorClassParameterType
{
    public function __construct(TestClassWithPrivateConstructor $dep)
    {
    }
}

// test for not found interface, abstract class and traits.
interface TestUndefinedInterface
{
}

abstract class TestUndefinedAbstractClass
{
}

trait TestUndefinedTrait
{
}

final class TestClassWithProtectedConstructor
{
    protected function __construct()
    {
    }
}

final class TestClassWithPrivateConstructor
{
    private function __construct()
    {
    }
}
