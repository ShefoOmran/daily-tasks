FROM serversideup/php:8.4-fpm-nginx

ENV APP_ENV=production \
    APP_DEBUG=false \
    PHP_OPCACHE_ENABLE=1 \
    npm_config_cache=/tmp/.npm

# تثبيت Node.js عشان نبني الـ frontend
USER root
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*
USER www-data

COPY --chown=www-data:www-data . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && npm ci \
    && npm run build \
    && rm -rf node_modules