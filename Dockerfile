FROM php:8.3-apache

# Install system dependencies and PHP extensions required by Symfony
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    libxml2-dev \
    libonig-dev \
    libsqlite3-dev \
    unzip \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pdo_sqlite \
    intl \
    mbstring \
    xml \
    zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache DocumentRoot to point to public directory
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set working directory
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy all application files first (vendor is excluded by .dockerignore)
COPY --chown=www-data:www-data . /var/www/html/

# Install dependencies after copying all files
# This ensures vendor is created in the final location
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev --optimize-autoloader

# Verify vendor directory exists and has required files
RUN ls -la vendor/autoload_runtime.php || (echo "ERROR: vendor/autoload_runtime.php missing!" && exit 1)

# Run composer scripts now that all files are in place
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative || true

# Create var directories and set permissions
RUN mkdir -p var/cache var/log && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 var/cache var/log && \
    chmod -R 755 vendor || true

# Set Apache environment variable for DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

EXPOSE 80

# Run a script to fix permissions on startup (since volumes override them)
RUN echo '#!/bin/bash\n# Verify vendor directory exists\nif [ ! -f /var/www/html/vendor/autoload_runtime.php ]; then\n    echo "ERROR: vendor/autoload_runtime.php is missing!"\n    echo "Attempting to reinstall dependencies..."\n    cd /var/www/html && composer install --no-interaction --prefer-dist --no-scripts --no-dev --optimize-autoloader\n    if [ ! -f /var/www/html/vendor/autoload_runtime.php ]; then\n        echo "FATAL: Failed to install vendor dependencies"\n        exit 1\n    fi\nfi\nchown -R www-data:www-data /var/www/html/var\nchmod -R 775 /var/www/html/var\nif [ -d /var/www/html/config/jwt ]; then\n  chown -R www-data:www-data /var/www/html/config/jwt\n  chmod 640 /var/www/html/config/jwt/private.pem\n  chmod 644 /var/www/html/config/jwt/public.pem\nfi\napache2-foreground' > /usr/local/bin/docker-entrypoint.sh && \
    chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]

