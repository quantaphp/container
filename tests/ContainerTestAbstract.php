<?php

declare(strict_types=1);

require_once __DIR__ . '/classes.php';

use PHPUnit\Framework\TestCase;

use Psr\Container\ContainerInterface;

use Quanta\Container;
use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

abstract class ContainerTestAbstract extends TestCase
{
    private $value;

    private $container;

    abstract protected function getContainer(array $factories): Container;

    protected function setUp(): void
    {
        $this->value = new class
        {
        };

        $this->exception = new Exception;

        $this->container = $this->getContainer([
            'id' => 'value',

            // simple tests
            'simple.null' => null,
            'simple.true' => true,
            'simple.false' => false,
            'simple.object' => $this->value,

            // callable tests
            'callable.null' => fn () => null,
            'callable.true' => fn () => true,
            'callable.false' => fn () => false,
            'callable.object' => fn () => $this->value,
            'callable.object.cache' => fn () => new class
            {
            },
            'callable.throwing' => function () {
                throw $this->exception;
            },

            // aliases tests
            TestAliasInterface::class => TestAliasClass::class,
            TestAliasClass::class => new TestAliasClass,
            TestThrowingAliasInterface::class => TestThrowingAliasClass::class,
            TestThrowingAliasClass::class => function () {
                throw $this->exception;
            },

            // classes definition
            TestDefinedInterface::class => new TestDefinedInterfaceImpl,
            TestDefinedAbstract::class => new TestDefinedAbstractImpl,
            TestDefinedClass::class => new TestDefinedClass,
            TestDefinedClassWithProtectedConstructor::class => TestDefinedClassWithProtectedConstructor::instance(),
            TestDefinedClassWithPrivateConstructor::class => TestDefinedClassWithPrivateConstructor::instance(),
            TestThrowingParameterType::class => function () {
                throw $this->exception;
            },
        ]);
    }

    public function testImplementsContainerInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function testConstructorThrowsWithNonStringableKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Container(new class implements IteratorAggregate
        {
            public function getIterator(): Generator
            {
                yield [] => 'value';
            }
        });
    }

    public function testHasReturnsTrueForDefinedId(): void
    {
        $test = $this->container->has('id');

        $this->assertTrue($test);
    }

    public function testHasReturnsFalseForUndefinedId(): void
    {
        $test = $this->container->has('not.defined');

        $this->assertFalse($test);
    }

    public function testHasReturnsFalseForUndefinedInterfaces(): void
    {
        $test = $this->container->has(TestUndefinedInterface::class);

        $this->assertFalse($test);
    }

    public function testHasReturnsTrueForUndefinedClasses(): void
    {
        $test = $this->container->has(TestUndefinedClass::class);

        $this->assertTrue($test);
    }

    public function testHasReturnsTrueForUndefinedAbstractClasses(): void
    {
        $test = $this->container->has(TestUndefinedAbstractClass::class);

        $this->assertTrue($test);
    }

    public function testHasReturnsTrueForUndefinedClassesWithProtectedConstructor(): void
    {
        $test = $this->container->has(TestUndefinedClassWithProtectedConstructor::class);

        $this->assertTrue($test);
    }

    public function testHasReturnsTrueForUndefinedClassesWithPrivateConstructor(): void
    {
        $test = $this->container->has(TestUndefinedClassWithPrivateConstructor::class);

        $this->assertTrue($test);
    }

    public function testGetReturnsSimpleNullValue(): void
    {
        $test = $this->container->get('simple.null');

        $this->assertNull($test);
    }

    public function testGetReturnsSimpleTrueValue(): void
    {
        $test = $this->container->get('simple.true');

        $this->assertTrue($test);
    }

    public function testGetReturnsSimpleFalseValue(): void
    {
        $test = $this->container->get('simple.false');

        $this->assertFalse($test);
    }

    public function testGetReturnsSimpleObjectValue(): void
    {
        $test = $this->container->get('simple.object');

        $this->assertSame($test, $this->value);
    }

    public function testGetReturnsCallableNullValue(): void
    {
        $test = $this->container->get('callable.null');

        $this->assertNull($test);
    }

    public function testGetReturnsCallableTrueValue(): void
    {
        $test = $this->container->get('callable.true');

        $this->assertTrue($test);
    }

    public function testGetReturnsCallableFalseValue(): void
    {
        $test = $this->container->get('callable.false');

        $this->assertFalse($test);
    }

    public function testGetReturnsCallableObjectValue(): void
    {
        $test = $this->container->get('callable.object');

        $this->assertSame($test, $this->value);
    }

    public function testGetCachesCallableValue(): void
    {
        $test1 = $this->container->get('callable.object.cache');
        $test2 = $this->container->get('callable.object.cache');

        $this->assertSame($test1, $test2);
    }

    public function testGetWrapsExceptionsThrownFromCallablesIntoContainerException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::factory('callable.throwing'));

        $this->container->get('callable.throwing');
    }

    public function testGetInterfaceAliases(): void
    {
        $test = $this->container->get(TestAliasInterface::class);

        $this->assertEquals($test, new TestAliasClass);
    }

    public function testGetCachesInterfaceAliases(): void
    {
        $test1 = $this->container->get(TestAliasInterface::class);
        $test2 = $this->container->get(TestAliasInterface::class);

        $this->assertSame($test1, $test2);
    }

    public function testGetWrapsExceptionsThrownFromAliasesIntoContainerException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::alias(
            TestThrowingAliasInterface::class,
            TestThrowingAliasClass::class,
        ));

        $this->container->get(TestThrowingAliasInterface::class);
    }

    public function testGetAutowiresClasses(): void
    {
        $test = $this->container->get(TestAutowiredClass::class);

        $this->assertInstanceOf(TestAutowiredClass::class, $test);
    }

    public function testGetCachesAutowiredClasses(): void
    {
        $test1 = $this->container->get(TestAutowiredClass::class);
        $test2 = $this->container->get(TestAutowiredClass::class);

        $this->assertSame($test1, $test2);
    }

    public function testGetCachesAutowiredClassDependencies(): void
    {
        $test = $this->container->get(TestAutowiredClass::class);

        $dep_defined_interface = $this->container->get(TestDefinedInterface::class);
        $dep_defined_abstract = $this->container->get(TestDefinedAbstract::class);
        $dep_defined_class = $this->container->get(TestDefinedClass::class);
        $dep_defined_class_protected = $this->container->get(TestDefinedClassWithProtectedConstructor::class);
        $dep_defined_class_private = $this->container->get(TestDefinedClassWithPrivateConstructor::class);
        $dep_undefined_class = $this->container->get(TestUndefinedClass::class);

        $this->assertSame($test->dep_defined_interface, $dep_defined_interface);
        $this->assertSame($test->dep_defined_abstract, $dep_defined_abstract);
        $this->assertSame($test->dep_defined_class, $dep_defined_class);
        $this->assertSame($test->dep_defined_class_protected, $dep_defined_class_protected);
        $this->assertSame($test->dep_defined_class_private, $dep_defined_class_private);
        $this->assertSame($test->dep_undefined_class, $dep_undefined_class);
    }

    public function testGetHandlesNullableDependencies(): void
    {
        $test = $this->container->get(TestAutowiredClass::class);

        $this->assertNull($test->dep_undefined_interface);
        $this->assertNull($test->dep_nullable_value);
    }

    public function testGetHandlesDependenciesWithDefaultValues(): void
    {
        $test = $this->container->get(TestAutowiredClass::class);

        $this->assertEquals($test->dep_default_value, 1);
    }

    public function testGetRecursivelyAutowiresClassDependencies(): void
    {
        $test = $this->container->get(TestAutowiredClassWithDeepDependencies::class);

        $this->assertInstanceOf(TestAutowiredClassWithDeepDependencies::class, $test);
    }

    public function testGetCachesRecursivelyAutowiredClassDependencies(): void
    {
        $test1 = $this->container->get(TestAutowiredClassWithDeepDependencies::class);
        $test2 = $this->container->get(TestAutowiredClassDependency1::class);
        $test3 = $this->container->get(TestAutowiredClassDependency2::class);

        $this->assertSame($test2, $test1->dep);
        $this->assertSame($test3, $test1->dep->dep);
    }

    public function testGetThrowsNotFoundExceptionForUndefinedId(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::message('not.defined'));

        $this->container->get('not.defined');
    }

    public function testGetThrowsNotFoundExceptionForUndefinedInterface(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::message(TestUndefinedInterface::class));

        $this->container->get(TestUndefinedInterface::class);
    }

    public function testGetThrowsContainerExceptionForUndefinedAbstractClass(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::abstract(TestUndefinedAbstractClass::class));

        $this->container->get(TestUndefinedAbstractClass::class);
    }

    public function testGetThrowsContainerExceptionForUndefinedClassWithProtectedConstructor(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::private(TestUndefinedClassWithProtectedConstructor::class));

        $this->container->get(TestUndefinedClassWithProtectedConstructor::class);
    }

    public function testGetThrowsContainerExceptionForUndefinedClassWithPrivateConstructor(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::private(TestUndefinedClassWithPrivateConstructor::class));

        $this->container->get(TestUndefinedClassWithPrivateConstructor::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithUnionParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeUnion(
            TestClassWithUnionParameterType::class,
            'dep',
        ));

        $this->container->get(TestClassWithUnionParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithIntersectionParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeIntersection(
            TestClassWithIntersectionParameterType::class,
            'dep',
        ));

        $this->container->get(TestClassWithIntersectionParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithBuiltinParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeBuiltin(
            TestClassWithBuiltinParameterType::class,
            'dep',
        ));

        $this->container->get(TestClassWithBuiltinParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiredClassHasRecursiveParameter(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeRecursive(
            TestClassWithRecursiveParameter::class,
            'dep',
        ));

        $this->container->get(TestClassWithRecursiveParameter::class);
    }

    public function testGetWrapsExceptionsThrownFromParameterTypesIntoContainerException(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeError(
            TestClassWithThrowingParameterType::class,
            TestThrowingParameterType::class,
            'dep',
        ));

        $this->container->get(TestClassWithThrowingParameterType::class);
    }
}
