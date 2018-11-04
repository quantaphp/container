<?php

use Quanta\Printable;
use Quanta\Container\IdentifierTypeErrorMessage;

describe('IdentifierTypeErrorMessage', function () {

    describe('->__toString()', function () {

        it('should return a string containing the invalid id', function () {

            $test = (string) new IdentifierTypeErrorMessage([]);

            expect($test)->toContain((string) new Printable([]));

        });

    });

});
