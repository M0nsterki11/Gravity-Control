FROM php:8.2-apache

ARG CACHE_BUST=1

RUN set -eux; \
    docker-php-ext-install pdo_mysql; \
    a2dismod mpm_event || true; \
    a2dismod mpm_worker || true; \
    a2enmod mpm_prefork rewrite headers expires; \
    echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf; \
    a2enconf servername; \
    apache2ctl -M | grep mpm || true; \
    ls -l /etc/apache2/mods-enabled/*mpm* || true

WORKDIR /var/www/html

COPY . /var/www/html

RUN set -eux; \
    sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf; \
    sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public/>!g' /etc/apache2/apache2.conf; \
    sed -ri -e '/<Directory \/var\/www\/html\/public\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf; \
    chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["sh", "-lc", "ls -l /etc/apache2/mods-enabled/*mpm*; apache2ctl -M; apache2-foreground"]
