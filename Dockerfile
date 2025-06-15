FROM php:8.3-fpm

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libpq-dev \
    && docker-php-ext-install \
        bcmath \
        exif \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        gd \
        mbstring \
        zip \
        xml \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

CMD ["php-fpm"]
