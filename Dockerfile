# Dockerfile – PHP + Apache for Render.com
FROM php:8.3-apache

# Install dependencies (cURL for Gemini API)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copy your app files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/ \
    && chmod -R 755 /var/www/html/

# Expose port (Render uses 10000 internally, but Apache on 80)
EXPOSE 8080

# Start Apache
CMD ["apache2-foreground"]
