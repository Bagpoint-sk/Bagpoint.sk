FROM php:8.4-apache

# 1️⃣ Nainštaluj systémové knižnice potrebné pre PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev pkg-config

# 2️⃣ Nainštaluj PHP rozšírenia pre PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# 3️⃣ Nastavenie pracovného priečinka
WORKDIR /var/www/html

# 4️⃣ Skopíruj frontend aj backend
COPY ./frontend /var/www/html/frontend
COPY ./backend /var/www/html/backend

# 5️⃣ Nastav Apache DocumentRoot na frontend
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/frontend#g' /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf && \
    sed -i 's/Require all denied/Require all granted/g' /etc/apache2/apache2.conf && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# 6️⃣ Povolenie backend priečinka
RUN echo "<Directory /var/www/html/backend>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" >> /etc/apache2/apache2.conf

# 7️⃣ Nastavenie DirectoryIndex
RUN echo "DirectoryIndex index.html index.php" >> /etc/apache2/apache2.conf

# 8️⃣ Debug výpis (pre kontrolu v logoch)
RUN echo '===== FRONTEND =====' && ls -R /var/www/html/frontend && \
    echo '===== BACKEND =====' && ls -R /var/www/html/backend

# 9️⃣ Spusti Apache
CMD ["apache2-foreground"]
