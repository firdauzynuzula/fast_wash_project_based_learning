FROM php:8.2-apache

# Install ekstensi dan driver PostgreSQL untuk PDO
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite Apache agar routing .htaccess berfungsi
RUN a2enmod rewrite

# Pastikan Apache mengizinkan AllowOverride All
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Salin seluruh kode proyek ke dalam web root Apache
COPY . /var/www/html/

# Atur ownership ke www-data
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
