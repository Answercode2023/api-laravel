FROM php:8.2-fpm

# Instalar extensões necessárias
RUN apt-get update && apt-get install -y \
    build-essential \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql zip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
