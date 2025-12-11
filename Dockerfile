FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system deps required for common PHP extensions and tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    zip \
    libicu-dev \
    libonig-dev \
    git \
 && docker-php-ext-configure intl \
 && docker-php-ext-install pdo pdo_mysql zip intl opcache \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Make Apache listen on the PORT env (Render uses $PORT, default 8080)
ARG PORT=8080
RUN sed -ri "s/Listen\s+80/Listen ${PORT}/g" /etc/apache2/ports.conf \
 && sed -ri "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Copy application files
COPY . /var/www/html

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose the port used by Render
EXPOSE ${PORT}

# Use the standard apache foreground command
CMD ["apache2-foreground"]
