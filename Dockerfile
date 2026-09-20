FROM php:8.2-cli

RUN docker-php-ext-install openssl 2>/dev/null || true

WORKDIR /var/www/html
COPY login.php profile.php jwt.php ./
COPY keys ./keys

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080"]
