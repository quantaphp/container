<?php

use Psr\Container\NotFoundExceptionInterface;

use Quanta\Container\NotFoundException;

describe('NotFoundException', function () {

    beforeEach(function () {
        $this->exception = new NotFoundException('id');
    });

    it('should implement Throwable', function () {
        expect($this->exception)->toBeAnInstanceOf(Throwable::class);
    });

    it('should implement NotFoundExceptionInterface', function () {
        expect($this->exception)->toBeAnInstanceOf(NotFoundExceptionInterface::class);
    });

});
