# Bilet Satın Alma Platformu - Docker Configuration

# Use official PHP image with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Create database directory
RUN mkdir -p /var/www/html/database
RUN chown -R www-data:www-data /var/www/html/database
RUN chmod -R 755 /var/www/html/database

# Create Apache virtual host configuration
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Expose port 80
EXPOSE 80

# Create startup script
RUN echo '#!/bin/bash\n\
# Initialize database if it does not exist\n\
if [ ! -f /var/www/html/database/bilet_satin_alma.db ]; then\n\
    echo "Initializing database..."\n\
    php /var/www/html/init_database.php\n\
fi\n\
\n\
# Start Apache\n\
apache2-foreground' > /start.sh

RUN chmod +x /start.sh

# Set startup command
CMD ["/start.sh"]
