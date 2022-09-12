# this image allows to run tests with a specific php version.
# build: docker build . --build-arg PHP_VERSION=8.0 --tag quanta-container-tests:8.0
# run tests: docker run --rm quanta-container-tests:8.0
ARG PHP_VERSION
FROM php:${PHP_VERSION}-cli
RUN apt-get update && apt-get -y --no-install-recommends install git libzip-dev \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install zip
WORKDIR /app
COPY . /app
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=php
CMD php ./vendor/bin/phpunit tests
