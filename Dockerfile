FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        git \
        libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mysqli curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first so this layer is cached unless composer.json/lock change.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts

COPY . .

# uploads/ is a mounted volume in Dokploy (see setup.md) so files survive redeploys;
# still needs to be writable by the web server user on first boot.
RUN mkdir -p uploads/residents \
    && chown -R www-data:www-data uploads \
    && chmod -R 775 uploads

# This app is served from the repo root (no public/ folder) and relies on .htaccess for
# everything (session hardening, path blocking, cache headers) -- mirrors XAMPP's
# AllowOverride All so the same .htaccess files work unchanged in both environments.
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/civentral.conf \
    && a2enconf civentral

RUN chmod +x docker/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["apache2-foreground"]
