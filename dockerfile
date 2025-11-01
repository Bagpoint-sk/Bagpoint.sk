FROM php:8.4-apache

# 1. PostgreSQL 
RUN docker-php-ext-install pdo pdo_pgsql

# 2. Nastavenie pracovneho adresara
WORKDIR /var/www/html
COPY . .

# 3. Nastavenie Apache rootu
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/frontend#g' /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf && \
    sed -i 's/Require all denied/Require all granted/g' /etc/apache2/apache2.conf && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# 4.Povolenie backend priecinka
RUN echo "<Directory /var/www/html/backend>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" >> /etc/apache2/apache2.conf

# 5. DirectoryIndex pre frontend
RUN echo "DirectoryIndex index.html index.php" >> /etc/apache2/apache2.conf

CMD ["apache2-foreground"]
