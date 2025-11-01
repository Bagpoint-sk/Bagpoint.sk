FROM php:8.4-apache

# 1. Nainštaluj PHP rozšírenia
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 2. Skopíruj celý projekt
COPY . /var/www/html/

# 3. Povedz Apache-u, že web sa nachádza v priečinku frontend
RUN rm -rf /var/www/html/index.html
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/frontend#g' /etc/apache2/sites-available/000-default.conf

# 4. Povolenie mod_rewrite a oprava práv
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
RUN sed -i 's/Require all denied/Require all granted/g' /etc/apache2/apache2.conf
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# 5. Spusti Apache
CMD ["apache2-foreground"]
