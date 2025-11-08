FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)\n\
xdebug.mode=\${XDEBUG_MODE}\n\
xdebug.start_with_request=yes\n\
xdebug.client_host=\${XDEBUG_CLIENT_HOST}\n\
xdebug.client_port=\${XDEBUG_CLIENT_PORT}\n" \
> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /workspace
