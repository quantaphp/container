<?php

use function Eloquent\Phony\Kahlan\stub;

use Psr\Container\ContainerInterface;

use Quanta\Container;
use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

describe('Container::factories()', function () {

    context('when the given iterable is an array', function () {

        context('when the given array contains only stringable keys and callable values', function () {

            it('should return an instance of Container', function () {
                $factories = [
                    'id1' => fn () => 'value1',
                    2 => fn () => 'value2',
                    'id3' => fn () => 'value3',
                ];

                $test = Container::factories($factories);

                expect($test)->toBeAnInstanceOf(Container::class);
                expect($test->has('id1'))->toBeTruthy();
                expect($test->has('2'))->toBeTruthy();
                expect($test->has('id3'))->toBeTruthy();
            });
        });

        context('when the given array does not contain only callable values', function () {

            it('should throw an InvalidArgumentException', function () {
                $factories = [
                    'id1' => fn () => 'value1',
                    'id2' => 'value2',
                    'id3' => fn () => 'value3',
                ];

                $test = fn () => Container::factories($factories);

                expect($test)->toThrow(new InvalidArgumentException);
            });
        });
    });

    context('when the given iterable is an Iterator', function () {

        context('when the given Iterator contains only stringable keys and callable values', function () {

            it('should return an instance of Container', function () {
                $factories = new ArrayIterator([
                    'id1' => fn () => 'value1',
                    2 => fn () => 'value2',
                    'id3' => fn () => 'value3',
                ]);

                $test = Container::factories($factories);

                expect($test)->toBeAnInstanceOf(Container::class);
                expect($test->has('id1'))->toBeTruthy();
                expect($test->has('2'))->toBeTruthy();
                expect($test->has('id3'))->toBeTruthy();
            });
        });

        context('when the given Iterator does not contain only stringable keys', function () {

            it('should throw an InvalidArgumentException', function () {
                $factories = (function () {
                    yield 'id1' => fn () => 'value1';
                    yield new class
                    {
                    }
                        => fn () => 'value2';
                    yield 'id3' => fn () => 'value3';
                })();

                $test = fn () => Container::factories($factories);

                expect($test)->toThrow(new InvalidArgumentException);
            });
        });

        context('when the given Iterator does not contain only callable values', function () {

            it('should throw an InvalidArgumentException', function () {
                $factories = new ArrayIterator([
                    'id1' => fn () => 'value1',
                    'id2' => 'value2',
                    'id3' => fn () => 'value3',
                ]);

                $test = fn () => Container::factories($factories);

                expect($test)->toThrow(new InvalidArgumentException);
            });
        });
    });

    context('when the given iterable is an IteratorAggregate', function () {

        context('when the given IteratorAggregate contains only stringable keys and callable values', function () {

            it('should return an instance of Container', function () {
                $factories = new class implements IteratorAggregate
                {
                    public function getIterator(): Traversable
                    {
                        return new ArrayIterator([
                            'id1' => fn () => 'value1',
                            2 => fn () => 'value2',
                            'id3' => fn () => 'value3',
                        ]);
                    }
                };

                $test = Container::factories($factories);

                expect($test)->toBeAnInstanceOf(Container::class);
                expect($test->has('id1'))->toBeTruthy();
                expect($test->has('2'))->toBeTruthy();
                expect($test->has('id3'))->toBeTruthy();
            });
        });

        context('when the given IteratorAggregate does not contain only stringable keys', function () {

            it('should throw an InvalidArgumentException', function () {
                $factories = new class implements IteratorAggregate
                {
                    public function getIterator(): Traversable
                    {
                        yield 'id1' => fn () => 'value1';
                        yield new class
                        {
                        }
                            => fn () => 'value2';
                        yield 'id3' => fn () => 'value3';
                    }
                };

                $test = fn () => Container::factories($factories);

                expect($test)->toThrow(new InvalidArgumentException);
            });
        });

        context('when the given IteratorAggregate does not contain only callable values', function () {

            it('should throw an InvalidArgumentException', function () {
                $factories = new class implements IteratorAggregate
                {
                    public function getIterator(): Traversable
                    {
                        return new ArrayIterator([
                            'id1' => fn () => 'value1',
                            'id2' => 'value2',
                            'id3' => fn () => 'value3',
                        ]);
                    }
                };

                $test = fn () => Container::factories($factories);

                expect($test)->toThrow(new InvalidArgumentException);
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

        context('when the given id is not associated to a factory', function () {

            it('should throw a NotFoundException', function () {
                $test = fn () => $this->container->get('notdefined');

                expect($test)->toThrow(new NotFoundException('notdefined'));
            });
        });

        context('when the given id is associated to a factory', function () {

            context('when the associated factory does not throw an exception', function () {

                it('should return the value produced by the factory (called with itself as argument)', function () {
                    $this->factory->with($this->container)->returns('value');

                    $test = $this->container->get('id');

                    expect($test)->toEqual('value');
                });

                it('should return the same value on multiple calls', function () {
                    $this->factory->with($this->container)->does(fn () => new class
                    {
                    });

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

                    expect($test)->toThrow(new ContainerException('id', 0, $exception));
                });
            });
        });
    });

    describe('->has()', function () {
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
});
