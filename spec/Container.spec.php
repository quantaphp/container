<?php

use function Eloquent\Phony\Kahlan\stub;

use Quanta\Container;
use Quanta\Container\ContainerException;
use Quanta\Container\NotFoundException;

describe('Container', function () {

    beforeEach(function () {

        $this->factory = stub();
        $this->entry = new class {};

        $this->container = new Container([
            'entry1' => $this->entry,
        ], [
            'factory1' => $this->factory,
        ]);

    });

    describe('->withEntries()', function () {

        it('should return a new Container', function () {

            $test = $this->container->withEntries([]);

            expect($test)->not->toBe($this->container);

        });

        it('should return a new Container with additional entries', function () {

            $entry1 = new class {};
            $entry2 = new class {};

            $test = $this->container->withEntries([
                'entry1' => $entry1,
                'entry2' => $entry2,
            ]);

            $container = new Container([
                'factory1' => $this->factory,
            ], [
                'entry1' => $entry1,
                'entry2' => $entry2,
            ]);

            expect($test)->toEqual($container);

        });

    });

    describe('->withFactories()', function () {

        it('should return a new Container', function () {

            $test = $this->container->withFactories([]);

            expect($test)->not->toBe($this->container);

        });

        it('should return a new Container with additional factories', function () {

            $factory1 = stub();
            $factory2 = stub();

            $test = $this->container->withFactories([
                'factory1' => $factory1,
                'factory2' => $factory2,
            ]);

            $container = new Container([
                'factory1' => $factory1,
                'factory2' => $factory2,
            ], [
                'entry1' => $this->entry,
            ]);

            expect($test)->toEqual($container);

        });

    });

    describe('->get()', function () {

        context('when the given id is associated with an entry', function () {

            it('should return the entry', function () {

                $test = $this->container->get('entry1');

                expect($test)->toBe($this->entry);

            });

        });

        context('when the given id is not associated with an entry', function () {

            context('when the given id is associated with a factory', function () {

                context('when the factory does not throw an exception', function () {

                    it('should call the factory with the container as parameter', function () {

                        $this->container->get('factory1');

                        $this->factory->calledWith($this->container);

                    });

                    it('should return the value produced by the factory', function () {

                        $instance = new class {};

                        $this->factory->returns($instance);

                        $test = $this->container->get('factory1');

                        expect($test)->toBe($instance);

                    });

                    context('when called multiple times with the same id', function () {

                        it('should call the factory only once', function () {

                            $this->container->get('factory1');
                            $this->container->get('factory1');

                            $this->factory->once()->called();

                        });

                        it('should return the same value', function () {

                            $instance = new class {};

                            $this->factory->returns($instance);

                            $test1 = $this->container->get('factory1');
                            $test2 = $this->container->get('factory1');

                            expect($test1)->toBe($test2);

                        });

                    });

                });

                context('when the factory throws an exception', function () {

                    it('should throw a ContainerException wrapped around the exception', function () {

                        $exception = new Exception;

                        $this->factory->throws($exception);

                        $test = function () { $this->container->get('factory1'); };

                        $expected = new ContainerException('factory1', $exception);

                        expect($test)->toThrow($expected);

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

    });

    describe('->has()', function () {

        context('when the given id is associated with an entry', function () {

            it('should return true', function () {

                $test = $this->container->has('entry1');

                expect($test)->toBeTruthy();

            });

        });

        context('when the given id is not associated with an entry', function () {

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

});
