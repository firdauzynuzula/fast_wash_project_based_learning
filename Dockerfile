FROM php:8.3-apache-bookworm

# Update & upgrade paket keamanan OS + install ekstensi dan driver PostgreSQL untuk PDO
RUN apt-get update && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Gunakan php.ini-production & sembunyikan informasi versi PHP/Apache dari header publik
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "expose_php = Off" >> "$PHP_INI_DIR/conf.d/security.ini" \
    && printf "ServerTokens Prod\nServerSignature Off\n" > /etc/apache2/conf-available/security-custom.conf \
    && a2enconf security-custom

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
