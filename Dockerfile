# Usamos una imagen oficial de PHP con Apache incluido
FROM php:8.2-apache

# Instalamos las extensiones de base de datos clásicas que usa PHP nativo
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitamos el módulo rewrite de Apache (muy útil para URLs limpias)
RUN a2enmod rewrite

# Copiamos todo el contenido de tu carpeta actual al servidor web del contenedor
COPY . /var/www/html/

# Le damos permisos al servidor web para que pueda leer y escribir (útil para temp_downloads)
RUN chown -R www-data:www-data /var/www/html/

# Exponemos el puerto 80 del contenedor
EXPOSE 80