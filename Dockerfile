FROM richarvey/nginx-php-fpm:latest
COPY . .
ENV SKIP_COMPOSER 0
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
EXPOSE 80
