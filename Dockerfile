FROM serversideup/php:8.4-fpm-nginx

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --chown=www-data:www-data . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist