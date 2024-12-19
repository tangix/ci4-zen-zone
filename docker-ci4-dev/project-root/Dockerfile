# syntax=docker/dockerfile:1

#
# Create a PHP 8.2 image suitable for CodeIgniter
# Source of inspiration: https://docs.docker.com/guides/php/containerize/
#

FROM php:8.2-apache as base

RUN apt-get -y update \
    && apt-get install -y  \
		libfreetype6-dev \
        libmagickwand-dev \
		libjpeg62-turbo-dev \
        libpcre3-dev \
        libjudy-dev \
        libzip-dev \
		libpng-dev \
		libonig-dev \
		libicu-dev

RUN docker-php-source extract \
    && pecl install -o -f redis xdebug \
	&&  rm -rf /tmp/pear \
	&&  docker-php-ext-enable redis \
    &&  docker-php-ext-enable xdebug \
	&& MAKEFLAGS="-j $(nproc)" pecl install imagick \
	&& MAKEFLAGS="-j $(nproc)" pecl install memprof \
    && docker-php-ext-install pdo pdo_mysql mbstring zip iconv mysqli \
    && docker-php-ext-install -j$(nproc) gd \
	&& docker-php-ext-configure intl \
	&& docker-php-ext-install intl \
    && docker-php-source delete

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY ./backend /var/www/html
RUN chown -R www-data /var/www/html/writable

# Copy the start-script doing DB migration and then starting apache
COPY backend/start.sh /var/www/html/
RUN chmod -R 775 /var/www/html/start.sh

# Configure apache
RUN a2enmod rewrite
COPY ./deploy/my-000-default.conf /etc/apache2/sites-available/000-default.conf

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
#RUN sed -ri -e "s!/var/www/html!$APACHE_DOCUMENT_ROOT!g" /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!$APACHE_DOCUMENT_ROOT!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Run the custom start-up script to get database migrations running
CMD ["sh", "-c", "/var/www/html/start.sh"]

#
# Build the production image
#

FROM base as prod-deps
WORKDIR /app
RUN --mount=type=bind,source=./backend/composer.json,target=composer.json \
    --mount=type=bind,source=./backend/composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction

FROM base as dev-deps
WORKDIR /app
RUN --mount=type=bind,source=./backend/composer.json,target=composer.json \
    --mount=type=bind,source=./backend/composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-interaction

FROM base as development
COPY ./deploy/xdebug.ini /usr/local/etc/php/conf.d
COPY ./backend/tests /var/www/html/tests
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY --from=dev-deps app/vendor/ /var/www/html/vendor

FROM development as test
WORKDIR /var/www/html
RUN ./vendor/bin/phpunit tests/HelloWorldTest.php

FROM base as final
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --from=prod-deps app/vendor/ /var/www/html/vendor
USER www-data
