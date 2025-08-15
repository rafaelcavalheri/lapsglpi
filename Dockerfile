FROM php:8.1-apache

# Instalar dependências necessárias
RUN apt-get update && apt-get install -y \
    wget \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Configurar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
        xml \
        curl \
        opcache

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Definir diretório de trabalho
WORKDIR /var/www/html

# Baixar e instalar GLPI
RUN wget https://github.com/glpi-project/glpi/releases/download/10.0.10/glpi-10.0.10.tgz \
    && tar -xzf glpi-10.0.10.tgz \
    && rm glpi-10.0.10.tgz \
    && chown -R www-data:www-data glpi \
    && chmod -R 755 glpi

# Copiar plugin LAPS para o diretório de plugins do GLPI
COPY . /var/www/html/glpi/plugins/lapsglpi/

# Definir permissões corretas para o plugin
RUN chown -R www-data:www-data /var/www/html/glpi/plugins/lapsglpi \
    && chmod -R 755 /var/www/html/glpi/plugins/lapsglpi

# Configurar Apache para GLPI
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf \
    && echo '    DocumentRoot /var/www/html/glpi' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    <Directory /var/www/html/glpi>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf \
    && echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Configurar PHP para GLPI
RUN echo 'memory_limit = 256M' >> /usr/local/etc/php/conf.d/glpi.ini \
    && echo 'upload_max_filesize = 20M' >> /usr/local/etc/php/conf.d/glpi.ini \
    && echo 'post_max_size = 20M' >> /usr/local/etc/php/conf.d/glpi.ini \
    && echo 'max_execution_time = 300' >> /usr/local/etc/php/conf.d/glpi.ini \
    && echo 'session.cookie_httponly = On' >> /usr/local/etc/php/conf.d/glpi.ini

# Script de inicialização
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expor porta 80
EXPOSE 80

# Comando de inicialização
CMD ["/usr/local/bin/docker-entrypoint.sh"]