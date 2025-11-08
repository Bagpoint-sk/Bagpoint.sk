FROM php:8.4-apache

# Postgresql
RUN apt-get update && apt-get install -y libpq-dev pkg-config && docker-php-ext-install pdo pdo_pgsql

# Nastavenie adresara
WORKDIR /var/www/html

# Skopruj frontend aj backend
COPY frontend/ /var/www/html/frontend/
COPY backend/ /var/www/html/backend/

# Nastav Apache root na frontend
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/frontend#g' /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

#  Pridaj špeciálnu Apache konfiguráciu pre backend priečinok
RUN printf "\nAlias /backend /var/www/html/backend\n\
<Directory /var/www/html/backend>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n" >> /etc/apache2/apache2.conf

# Nastavenie predvolenych indexov
RUN echo "DirectoryIndex index.html index.php" >> /etc/apache2/apache2.conf

# Debug 
RUN echo '===== FRONTEND =====' && ls -R /var/www/html/frontend && \
    echo '===== BACKEND =====' && ls -R /var/www/html/backend

# Spusti Apache
CMD ["apache2-foreground"]
