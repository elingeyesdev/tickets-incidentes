FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    wget \
    supervisor \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Node.js 20.x and npm
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u 1000 -d /home/user user
RUN mkdir -p /home/user/.composer && \
    chown -R user:user /home/user

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=user:user . /var/www

# Create required directories
RUN mkdir -p \
    /var/www/storage/logs \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache

# Set permissions
RUN chown -R user:www-data /var/www \
    && chmod -R 755 /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

# Install PHP dependencies
USER user
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node.js dependencies
RUN npm install

# Switch back to root for final configurations
USER root

# Copy PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor configuration for managing processes
COPY docker/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 9000 for PHP-FPM and 5173 for Vite
EXPOSE 9000 5173

# Create startup script
RUN echo '#!/bin/bash\n\
php-fpm -D\n\
if [ "$1" = "dev" ]; then\n\
    su user -c "npm run dev -- --host 0.0.0.0 --port 5173"\n\
else\n\
    tail -f /dev/null\n\
fi' > /usr/local/bin/start.sh && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]