<?php

use function Eloquent\Phony\Kahlan\stub;

use Psr\Container\ContainerInterface;

use Quanta\Container;
use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

describe('Container::factories()', function () {

    context('when the given array contains only callable values', function () {

        it('should return an instance of Container', function () {
            $test = Container::factories([
                'id1' => fn () => 'value1',
                'id2' => fn () => 'value2',
                'id3' => fn () => 'value3',
            ]);

            expect($test)->toBeAnInstanceOf(Container::class);
            expect($test->has('id1'))->toBeTruthy();
            expect($test->has('id2'))->toBeTruthy();
            expect($test->has('id3'))->toBeTruthy();
        });

    });

    context('when the given array does not contain only callable values', function () {

        it('should throw an InvalidArgumentException', function () {
            $test = fn () => Container::factories([
                'id1' => fn () => 'value1',
                'id2' => 'value2',
                'id3' => fn () => 'value3',
            ]);

            expect($test)->toThrow(new InvalidArgumentException);
        });

    });

});

describe('Container::files()', function () {

    context('when only one glob pattern is given', function () {

        context('when a matched file does not return an array', function () {

            it('should throw an UnexpectedValueException', function () {
                $test = fn () => Quanta\Container::files(__DIR__ . '/files/case1/*.php');

                expect($test)->toThrow(new UnexpectedValueException);
            });

        });

        context('when a matched file does not return an array of callable', function () {

            it('should throw an UnexpectedValueException', function () {
                $test = fn () => Quanta\Container::files(__DIR__ . '/files/case2/*.php');

                expect($test)->toThrow(new UnexpectedValueException);
            });

        });

        context('when all matched files return an array of callables', function () {

            it('should return an instance of Container', function () {
                $test = Quanta\Container::files(__DIR__ . '/files/case3/*.php');

                expect($test)->toBeAnInstanceOf(Container::class);
                expect($test->has('id11'))->toBeTruthy();
                expect($test->has('id12'))->toBeTruthy();
                expect($test->has('id21'))->toBeTruthy();
                expect($test->has('id22'))->toBeTruthy();
            });

        });

    });

    context('when many glob patterns are given', function () {

        context('when a matched file does not return an array', function () {

            it('should throw an UnexpectedValueException', function () {
                $test = fn () => Quanta\Container::files(
                    __DIR__ . '/files/case1/*.php',
                    __DIR__ . '/files/case3/*.php',
                );

                expect($test)->toThrow(new UnexpectedValueException);
            });

        });

        context('when a matched file does not return an array of callable', function () {

            it('should throw an UnexpectedValueException', function () {
                $test = fn () => Quanta\Container::files(
                    __DIR__ . '/files/case2/*.php',
                    __DIR__ . '/files/case3/*.php',
                );

                expect($test)->toThrow(new UnexpectedValueException);
            });

        });

        context('when all matched files return an array of callables', function () {

            it('should return an instance of Container', function () {
                $test = Quanta\Container::files(
                    __DIR__ . '/files/case3/v*1.php',
                    __DIR__ . '/files/case3/v*2.php',
                );

                expect($test)->toBeAnInstanceOf(Container::class);
                expect($test->has('id11'))->toBeTruthy();
                expect($test->has('id12'))->toBeTruthy();
                expect($test->has('id21'))->toBeTruthy();
                expect($test->has('id22'))->toBeTruthy();
            });

        });

    });

});

describe('Container', function () {

    beforeEach(function () {
        $this->container = Container::factories([
            'id' => $this->factory = stub(),
        ]);
    });

    it('should not be instantiable without named constructor', function () {
        $test = fn () => new Container([]);

        expect($test)->toThrow();
    });

    it('should implement ContainerInterface', function () {
        expect($this->container)->toBeAnInstanceOf(ContainerInterface::class);
    });

    describe('->get()', function () {

        context('when the given id is a string', function () {

            context('when the given id is associated to a factory', function () {

                context('when the associated factory does not throw an exception', function () {

                    it('should return the value produced by the factory (called with itself as argument)', function () {
                        $this->factory->with($this->container)->returns('value');

                        $test = $this->container->get('id');

                        expect($test)->toEqual('value');
                    });

                    it('should return the same value on multiple calls', function () {
                        $this->factory->with($this->container)->does(fn () => new class {});

                        $test1 = $this->container->get('id');
                        $test2 = $this->container->get('id');

                        expect($test1)->toBe($test2);
                    });

                    it('should cache null values', function () {
                        $this->factory->with($this->container)->returns(null);

                        $this->container->get('id');
                        $this->container->get('id');

                        $this->factory->once()->called();
                    });

                });

                context('when the associated factory throws an exception', function () {

                    it('should throw a ContainerException containing the thrown exception', function () {
                        $exception = new Exception('test');

                        $this->factory->with($this->container)->throws($exception);

                        $test = fn () => $this->container->get('id');

                        expect($test)->toThrow(new ContainerException('id', $exception));
                    });

                });

            });

            context('when the given id is not associated to a factory', function () {

                it('should throw a NotFoundException', function () {
                    $test = fn () => $this->container->get('notdefined');

                    expect($test)->toThrow(new NotFoundException('notdefined'));
                });

            });

        });

        context('when the given id is not a string', function () {

            it('should throw an InvalidArgumentException', function () {
                $test = fn () => $this->container->get(1);

                expect($test)->toThrow(new InvalidArgumentException);
            });

        });

    });

    describe('->has()', function () {

        context('when the given id is a string', function () {

            context('when the given id is associated with a factory', function () {

                it('should return true', function () {
                    $test = $this->container->has('id');

                    expect($test)->toBeTruthy();
                });

            });

            context('when the given id is not associated with a factory', function () {

                it('should return false', function () {
                    $test = $this->container->has('notdefined');

                    expect($test)->toBeFalsy();
                });

            });

        });

        context('when the given id is not a string', function () {

            it('should throw an InvalidArgumentException', function () {
                $test = fn () => $this->container->has(1);

                expect($test)->toThrow(new InvalidArgumentException);
            });

        });

    });

});
