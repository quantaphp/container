<?php

use function Eloquent\Phony\Kahlan\stub;
use function Eloquent\Phony\Kahlan\mock;

use Quanta\Container;
use Quanta\Container\NotFoundException;
use Quanta\Container\ContainerException;
use Quanta\Container\FactoryTypeException;

describe('Container', function () {

    beforeEach(function () {

        $this->factory1 = stub();
        $this->factory2 = stub();

        $this->container = new Container([
            'factory1' => $this->factory1,
            'factory2' => $this->factory2,
        ]);

    });

    describe('->withFactory()', function () {

        context('when the given id is not already associated with a factory', function () {

            it('should return a new Container with an additional factory', function () {

                $factory3 = stub();

                $test = $this->container->withFactory('factory3', $factory3);

                $container = new Container([
                    'factory1' => $this->factory1,
                    'factory2' => $this->factory2,
                    'factory3' => $factory3,
                ]);

                expect($test)->not->toBe($this->container);
                expect($test)->toEqual($container);

            });

        });

        context('when the given id is already associated with a factory', function () {

            it('should return a new Container with the given factory associated with the given id', function () {

                $factory1 = stub();

                $test = $this->container->withFactory('factory1', $factory1);

                $container = new Container([
                    'factory1' => $factory1,
                    'factory2' => $this->factory2,
                ]);

                expect($test)->not->toBe($this->container);
                expect($test)->toEqual($container);

            });

        });

    });

    describe('->withFactories()', function () {

        it('should return a new Container with additional factories', function () {

            $factory1 = stub();
            $factory3 = stub();

            $test = $this->container->withFactories([
                'factory1' => $factory1,
                'factory3' => $factory3,
            ]);

            $container = new Container([
                'factory1' => $factory1,
                'factory2' => $this->factory2,
                'factory3' => $factory3,
            ]);

            expect($test)->not->toBe($this->container);
            expect($test)->toEqual($container);

        });

    });

    describe('->get()', function () {

        context('when the given id is associated with a factory', function () {

            context('when it is the first time ->get() is called with the given id', function () {

                context('when the factory is a callable', function () {

                    it('should call the factory with the container as parameter', function () {

                        $this->container->get('factory1');

                        $this->factory1->calledWith($this->container);

                    });

                    context('when the factory does not throw an exception', function () {

                        it('should return the value produced by the factory', function () {

                            $instance = new class {};

                            $this->factory1->returns($instance);

                            $test = $this->container->get('factory1');

                            expect($test)->toBe($instance);

                        });

                    });

                    context('when the factory throws an exception', function () {

                        it('should throw a ContainerException wrapped around the exception', function () {

                            $exception = mock(Throwable::class)->get();

                            $this->factory1->throws($exception);

                            $test = function () { $this->container->get('factory1'); };

                            $expected = new ContainerException('factory1', $exception);

                            expect($test)->toThrow($expected);

                        });

                    });

                });

                context('when the factory is not a callable', function () {

                    it('should throw a FactoryTypeException', function () {

                        $container = new Container(['factory' => 'notacallable']);

                        $test = function () use ($container) {
                            $container->get('factory');
                        };

                        $exception = new FactoryTypeException('factory', 'notacallable');

                        expect($test)->toThrow($exception);

                    });

                });

            });

            context('when ->get() has already been called with the given id', function () {

                context('when the ->get() method returns a non null value for the given id', function () {

                    beforeEach(function () {

                        $this->instance = new class {};

                        $this->factory1->returns($this->instance);

                        $this->container->get('factory1');

                    });

                    it('should not call the factory again', function () {

                        $this->container->get('factory1');

                        $this->factory1->once()->called();

                    });

                    it('should return the same value as the one returned on the first call', function () {

                        $test = $this->container->get('factory1');

                        expect($test)->toEqual($this->instance);

                    });

                });

                context('when the ->get() method returns null for the given id', function () {

                    beforeEach(function () {

                        $this->factory1->returns(null);

                        $this->container->get('factory1');

                    });

                    it('should not call the factory again', function () {

                        $this->container->get('factory1');

                        $this->factory1->once()->called();

                    });

                    it('should return null', function () {

                        $test = $this->container->get('factory1');

                        expect($test)->toBeNull();

                    });

                });

            });

        });

        context('when the given id is not associated with a factory', function () {

            it('should throw a NotFoundException', function () {

                $test = function () { $this->container->get('notfound'); };

                $exception = new NotFoundException('notfound');

                expect($test)->toThrow($exception);

            });

        });

    });

    describe('->has()', function () {

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

});
