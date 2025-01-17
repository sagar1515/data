# Use an official PHP runtime as a parent image
FROM php:8.2-fpm

# Set the working directory in the container
WORKDIR /var/www

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy the Laravel application into the container
COPY . .

# Install Laravel dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Expose port 9000 and start PHP-FPM server
EXPOSE 9000

# Set the default command to run PHP-FPM
CMD ["php-fpm"]

