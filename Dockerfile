FROM php:8.2-apache

ARG CACHE_BUST=3

RUN set -eux; \
    docker-php-ext-install pdo_mysql; \
    rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf; \
    rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf; \
    rm -f /etc/apache2/mods-enabled/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.conf; \
    a2enmod mpm_prefork; \
    a2enmod rewrite headers expires; \
    echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername; \
    ls -l /etc/apache2/mods-enabled/*mpm* || true; \
    apache2ctl -M | grep mpm || true

WORKDIR /var/www/html

COPY . /var/www/html

RUN set -eux; \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf; \
    sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf; \
    sed -ri -e '/<Directory \/var\/www\/html\/public\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf; \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["sh", "-lc", "ls -l /etc/apache2/mods-enabled/*mpm*; apache2ctl -M; exec apache2-foreground"]
