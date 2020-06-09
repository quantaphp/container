<?php

use Psr\Container\ContainerExceptionInterface;

use Quanta\Container\ContainerException;

describe('ContainerException', function () {

    beforeEach(function () {
        $this->previous = new Exception('test');

        $this->exception = new ContainerException('id', $this->previous);
    });

    it('should implement Throwable', function () {
        expect($this->exception)->toBeAnInstanceOf(Throwable::class);
    });

    it('should implement ContainerExceptionInterface', function () {
        expect($this->exception)->toBeAnInstanceOf(ContainerExceptionInterface::class);
    });

    describe('->getPrevious()', function () {

        it('should return the previous exception',function () {
            $test = $this->exception->getPrevious();

            expect($test)->toBe($this->previous);
        });

    });

});
