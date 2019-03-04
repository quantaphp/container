<?php

use function Eloquent\Phony\Kahlan\stub;
use function Eloquent\Phony\Kahlan\mock;

use Psr\Container\ContainerInterface;

use Quanta\Container;
use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;

describe('Container', function () {

    context('when all the values of the array of factories are callables', function () {

        beforeEach(function () {

            $this->container = new Container([
                'factory1' => $this->factory1 = stub(),
                'factory2' => $this->factory2 = stub(),
            ]);

        });

        it('should implement ContainerInterface', function () {

            expect($this->container)->toBeAnInstanceOf(ContainerInterface::class);

        });

        describe('->with()', function () {

            context('when the given id is not already associated with a factory', function () {

                it('should return a new Container with an additional factory', function () {

                    $factory3 = function () {};

                    $test = $this->container->with('factory3', $factory3);

                    expect($test)->not->toBe($this->container);
                    expect($test)->toEqual(new Container([
                        'factory1' => $this->factory1,
                        'factory2' => $this->factory2,
                        'factory3' => $factory3,
                    ]));

                });

            });

            context('when the given id is already associated with a factory', function () {

                it('should return a new Container with the given id associated with the given factory', function () {

                    $factory1 = function () {};

                    $test = $this->container->with('factory1', $factory1);

                    expect($test)->not->toBe($this->container);
                    expect($test)->toEqual(new Container([
                        'factory1' => $factory1,
                        'factory2' => $this->factory2,
                    ]));

                });

            });

        });

        describe('->withEntries()', function () {

            context('when all the given factories are callables', function () {

                it('should return a new Container with additional factories', function () {

                    $factory1 = function () {};
                    $factory3 = function () {};

                    $test = $this->container->withEntries([
                        'factory1' => $factory1,
                        'factory3' => $factory3,
                    ]);

                    expect($test)->not->toBe($this->container);
                    expect($test)->toEqual(new Container([
                        'factory1' => $factory1,
                        'factory2' => $this->factory2,
                        'factory3' => $factory3,
                    ]));

                });

            });

            context('when at least one given factory is not a callable', function () {

                it('should throw an InvalidArgumentException', function () {

                    $test = function () {
                        $this->container->withEntries([
                            'factory1' => function () {},
                            'factory2' => 'factory',
                            'factory3' => function () {},
                        ]);
                    };

                    expect($test)->toThrow(new InvalidArgumentException);

                });

            });

        });

        describe('->get()', function () {

            context('when the given id is a string', function () {

                context('when the given id is associated with a factory', function () {

                    context('when the factory does not throw an exception', function () {

                        it('should return the value produced by the factory', function () {

                            $value = new class {};

                            $this->factory1->with($this->container)->returns($value);

                            $test = $this->container->get('factory1');

                            expect($test)->toBe($value);

                        });

                        it('should return the same value on multiple calls', function () {

                            $this->factory1->with($this->container)->does(function () {
                                return new class {};
                            });

                            $test1 = $this->container->get('factory1');
                            $test2 = $this->container->get('factory1');

                            expect($test1)->toBe($test2);

                        });

                        it('should cache null values', function () {

                            $this->factory1->with($this->container)->returns(null);

                            $this->container->get('factory1');
                            $this->container->get('factory1');

                            $this->factory1->once()->called();

                        });

                    });

                    context('when the factory throws an exception', function () {

                        it('should throw a ContainerException wrapped around the exception', function () {

                            $exception = mock(Throwable::class)->get();

                            $this->factory1->with($this->container)->throws($exception);

                            $test = function () { $this->container->get('factory1'); };

                            expect($test)->toThrow(new ContainerException('factory1', $exception));

                        });

                    });

                });

                context('when the given id is not associated with a factory', function () {

                    it('should throw a NotFoundException', function () {

                        $test = function () { $this->container->get('notfound'); };

                        expect($test)->toThrow(new NotFoundException('notfound'));

                    });

                });

            });

            context('when the given id is not a string', function () {

                it('should throw an InvalidArgumentException', function () {

                    $test = function () { $this->container->get([]); };

                    expect($test)->toThrow(new InvalidArgumentException);

                });

            });

        });

        describe('->has()', function () {

            context('when the given id is a string', function () {

                context('when the given id is associated with a factory', function () {

                    it('should return true', function () {

                        $test = $this->container->has('factory1');

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

                    $test = function () { $this->container->has([]); };

                    expect($test)->toThrow(new InvalidArgumentException);

                });

            });

        });

    });

    context('when a value of the array of factories is not a callable', function () {

        it('should throw an InvalidArgumentException', function () {

            $test = function () {
                new Container([
                    'factory1' => function () {},
                    'factory2' => 'factory',
                    'factory3' => function () {},
                ]);
            };

            expect($test)->toThrow(new InvalidArgumentException);

        });

    });

});
