FROM php:8.4-fpm-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    libxml2-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd \
    && apk del --no-cache $PHPIZE_DEPS

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy code
COPY . /var/www

# Ensure bootstrap/cache and storage are writable
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
