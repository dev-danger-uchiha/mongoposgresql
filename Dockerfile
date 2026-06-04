# Usar la imagen oficial de PHP con Apache
FROM php:8.2-apache

# Instalar dependencias del sistema para compilar extensiones
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensión de PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# Instalar extensión de MongoDB vía PECL
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el código de la aplicación al servidor
COPY . /var/www/html/

# Instalar las dependencias de PHP (la librería de Mongo)
WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Ajustar permisos para Apache
RUN chown -R www-data:www-data /var/www/html
