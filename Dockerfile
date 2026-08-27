FROM trueasync/php-true-async:latest

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

CMD ["php", "-v"]
