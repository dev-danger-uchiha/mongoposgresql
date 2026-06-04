# ─────────────────────────────────────────────
# Etapa 1: imagen base PHP 8.2 con Apache
# ─────────────────────────────────────────────
FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libssl-dev \
    pkg-config \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ── Extensiones PHP ──────────────────────────
# PDO + driver PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql

# Extensión PECL de MongoDB (requerida por mongodb/mongodb)
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# ── Composer ────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Configuración Apache ─────────────────────
# Habilitar mod_rewrite para URLs limpias
RUN a2enmod rewrite

# Apuntar DocumentRoot al directorio public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Permitir .htaccess en public/
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/public.conf \
    && a2enconf public

# ── Código fuente ───────────────────────────
WORKDIR /var/www/html

# Copiar composer.json primero (caching de capas)
COPY composer.json composer.lock* ./

# Instalar dependencias PHP sin dev y optimizando autoloader
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copiar el resto del proyecto
COPY . .

# Permisos para uploads
RUN mkdir -p uploads && chmod -R 775 uploads \
    && chown -R www-data:www-data /var/www/html

# Render inyecta PORT como variable de entorno.
# Apache por defecto escucha en 80; ajustamos dinámicamente.
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2-foreground"]
