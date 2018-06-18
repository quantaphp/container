<?php

use Psr\Container\ContainerExceptionInterface;

use Quanta\Container\FactoryTypeException;

describe('FactoryTypeException', function () {

    it('should implement ContainerExceptionInterface', function () {

        $test = new FactoryTypeException('notcallable');

        expect($test)->toBeAnInstanceOf(ContainerExceptionInterface::class);

    });

});
