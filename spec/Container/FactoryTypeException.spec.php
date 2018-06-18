<?php

use Psr\Container\ContainerExceptionInterface;

use Quanta\Container\FactoryTypeException;

describe('FactoryTypeException', function () {

    it('should implement ContainerExceptionInterface', function () {

        $test = new FactoryTypeException('id', 'notacallable');

        expect($test)->toBeAnInstanceOf(ContainerExceptionInterface::class);

    });

});
