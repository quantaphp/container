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

            // autowiring tests
            TestDepDefinedInterface::class => new TestDepDefinedInterfaceImpl,
            TestDepDefinedAbstract::class => new TestDepDefinedAbstractImpl,
            TestDepDefinedClass::class => new TestDepDefinedClass,
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

    public function testHasReturnsTrueWhenIdIsDefined(): void
    {
        $test = $this->container->has('id');

        $this->assertTrue($test);
    }

    public function testHasReturnsFalseWhenIdIsNotDefined(): void
    {
        $test = $this->container->has('not.defined');

        $this->assertFalse($test);
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

        $dep_defined_interface = $this->container->get(TestDepDefinedInterface::class);
        $dep_defined_abstract = $this->container->get(TestDepDefinedAbstract::class);
        $dep_defined_class = $this->container->get(TestDepDefinedClass::class);
        $dep_undefined_class = $this->container->get(TestDepUndefinedClass::class);

        $this->assertSame($test->dep_defined_interface, $dep_defined_interface);
        $this->assertSame($test->dep_defined_abstract, $dep_defined_abstract);
        $this->assertSame($test->dep_defined_class, $dep_defined_class);
        $this->assertSame($test->dep_undefined_class, $dep_undefined_class);
    }

    public function testGetHandlesNullableDependencies(): void
    {
        $test = $this->container->get(TestAutowiredClass::class);

        $this->assertNull($test->dep_undefined_nullable_interface);
        $this->assertNull($test->dep_undefined_nullable_abstract);
        $this->assertNull($test->dep_nullable_value);
    }

    public function testGetHandlesDependenciesWithDefaultValues(): void
    {
        $test = $this->container->get(TestAutowiredClass::class);

        $this->assertEquals($test->dep_default_value, 1);
    }

    public function testGetRecursivelyAutowiresClassDependencies(): void
    {
        $test = $this->container->get(TestAutowiredClassWithDependencies::class);

        $this->assertInstanceOf(TestAutowiredClassWithDependencies::class, $test);
    }

    public function testGetCachesRecursivelyAutowiredClassDependencies(): void
    {
        $test1 = $this->container->get(TestAutowiredClassWithDependencies::class);
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

    public function testGetThrowsNotFoundExceptionForUndefinedAbstractClass(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::message(TestUndefinedAbstractClass::class));

        $this->container->get(TestUndefinedAbstractClass::class);
    }

    public function testGetThrowsNotFoundExceptionForUndefinedTrait(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::message(TestUndefinedTrait::class));

        $this->container->get(TestUndefinedTrait::class);
    }

    public function testGetThrowsNotFoundExceptionForClassWithProtectedConstructor(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::message(TestClassWithProtectedConstructor::class));

        $this->container->get(TestClassWithProtectedConstructor::class);
    }

    public function testGetThrowsNotFoundExceptionForClassWithPrivateConstructor(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage(NotFoundException::message(TestClassWithPrivateConstructor::class));

        $this->container->get(TestClassWithPrivateConstructor::class);
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

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithNonExistingParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeNotFound(
            TestClassWithNonExistingParameterType::class,
            'NonExistingClass',
            'dep',
        ));

        $this->container->get(TestClassWithNonExistingParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithUndefinedInterfaceParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeUndefined(
            TestClassWithUndefinedInterfaceParameterType::class,
            TestUndefinedInterface::class,
            'dep',
        ));

        $this->container->get(TestClassWithUndefinedInterfaceParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithUndefinedAbstractClassParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeUndefined(
            TestClassWithUndefinedAbstractClassParameterType::class,
            TestUndefinedAbstractClass::class,
            'dep',
        ));

        $this->container->get(TestClassWithUndefinedAbstractClassParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithProtectedConstructorClassParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeUndefined(
            TestClassWithProtectedConstructorClassParameterType::class,
            TestClassWithProtectedConstructor::class,
            'dep',
        ));

        $this->container->get(TestClassWithProtectedConstructorClassParameterType::class);
    }

    public function testGetThrowsContainerExceptionWhenAutowiringClassWithPrivateConstructorClassParameterType(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(ContainerException::typeUndefined(
            TestClassWithPrivateConstructorClassParameterType::class,
            TestClassWithPrivateConstructor::class,
            'dep',
        ));

        $this->container->get(TestClassWithPrivateConstructorClassParameterType::class);
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
