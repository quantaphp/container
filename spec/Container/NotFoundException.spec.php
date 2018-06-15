<?php

use Psr\Container\NotFoundExceptionInterface;

use Quanta\Container\NotFoundException;

describe('NotFoundException', function () {

    it('should implement NotFoundExceptionInterface', function () {

        $test = new NotFoundException('notfound');

        expect($test)->toBeAnInstanceOf(NotFoundExceptionInterface::class);

    });

});
