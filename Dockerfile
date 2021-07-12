FROM php:7.3-fpm-alpine

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --from=mlocati/php-extension-installer:1.1.3 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions xdebug

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} && \
    ln -sf /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    echo "display_errors = 0" >> /usr/local/etc/php/php.ini && \
    docker-php-ext-enable xdebug ; \
    apk del pcre-dev ${PHPIZE_DEPS}

COPY src ./