FROM richarvey/nginx-php-fpm:latest
COPY . /var/www/html
ENV APP_ENV=production
ENV APP_KEY=
ENV APP_DEBUG=false
ENV WEBROOT=/var/www/html/public
ENV RUN_COMPOSER=true