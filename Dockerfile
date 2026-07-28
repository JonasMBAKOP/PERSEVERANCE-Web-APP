FROM php:8.3-cli

WORKDIR /app

# Installer dépendances système
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip

# Installer extensions PHP nécessaires à Laravel
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    xml \
    zip
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg

RUN docker-php-ext-install gd

ENV APP_KEY=base64:jJFGCB4C/QEDnbsc5mVbnMdNjfhMtx1gVFGZoJOJvc0=

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier le projet
COPY . .

# Installer dépendances PHP
RUN cp .env.example .env || true
RUN composer install --no-dev --optimize-autoloader --no-interaction -vvv

# Permissions Laravel
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8100

# RUN php artisan cache:clear
RUN php artisan config:clear || true

CMD php artisan config:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8100