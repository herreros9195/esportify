FROM php:8.2-apache

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    pkg-config \
    libssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN if [ -f composer.json ]; then composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader; fi

CMD sed -i "s/Listen 80/Listen ${PORT:-8080}/" /etc/apache2/ports.conf && \
    sed -i "s/:80/:${PORT:-8080}/" /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground