<?php

use Quanta\Printable;
use Quanta\Container\FactoryTypeErrorMessage;

describe('FactoryTypeErrorMessage', function () {

    describe('->__toString()', function () {

        it('should return a string containing the invalid factory', function () {

            $test = (string) new FactoryTypeErrorMessage([
                'factory1' => function () {},
                'factory2' => 'factory',
                'factory3' => function () {},
            ]);

            expect($test)->toContain((string) new Printable('factory'));

        });

    });

});
