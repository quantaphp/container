<?php

use Quanta\Printable;
use Quanta\Container\FactoryTypeErrorMessage;

describe('FactoryTypeErrorMessage', function () {

    describe('->__toString()', function () {

        it('should return a string containing the invalid factory and its id', function () {

            $test = (string) new FactoryTypeErrorMessage([
                'factory1' => function () {},
                'factory2' => 'testfactory',
                'factory3' => function () {},
            ]);

            expect($test)->toContain('factory2');
            expect($test)->toContain((string) new Printable('testfactory'));

        });

    });

});
