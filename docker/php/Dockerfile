FROM php:8.4-fpm AS base

# System dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libpq-dev libzip-dev libicu-dev zip unzip wget \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql mbstring exif pcntl \
        bcmath gd zip intl opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Node.js 22.x LTS
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && npm install -g npm@latest

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create helpdesk user with YOUR UID (1001)
RUN groupadd -g 33 www-data || true && \
    useradd -u 1001 -g www-data -G root -m -s /bin/bash helpdesk && \
    mkdir -p /home/helpdesk/.composer && \
    chown -R helpdesk:www-data /home/helpdesk

WORKDIR /var/www

# =============================================================================
# DEVELOPMENT STAGE
# =============================================================================
FROM base AS development

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh

# Install PostgreSQL client for pg_isready
RUN apt-get update && apt-get install -y postgresql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN mkdir -p \
    /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/bootstrap/cache && \
    chown -R helpdesk:www-data /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache && \
    chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

USER helpdesk

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]

# =============================================================================
# VITE STAGE
# =============================================================================
FROM base AS vite

COPY docker/php/entrypoint-vite.sh /usr/local/bin/entrypoint-vite.sh

RUN chmod +x /usr/local/bin/entrypoint-vite.sh

WORKDIR /var/www

USER helpdesk

EXPOSE 5173

ENTRYPOINT ["/usr/local/bin/entrypoint-vite.sh"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]