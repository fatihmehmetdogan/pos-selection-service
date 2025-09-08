FROM php:8.1-fpm

# Gerekli sistem bağımlılıklarını kur
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    cron \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Zaman dilimi
ENV TZ=Europe/Istanbul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Gerekli PHP eklentilerini kur
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Redis
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer'ı en güncel haliyle al
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizinini ayarla
WORKDIR /var/www

COPY composer.json composer.lock* ./

RUN composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY . .

RUN composer dump-autoload --optimize && \
    composer run-script post-install-cmd

# Cron zamanlanmış görevini ekle
COPY crontab /etc/cron.d/ratios-cron
RUN chmod 0644 /etc/cron.d/ratios-cron

RUN chown -R www-data:www-data /var/www/var /var/www/storage

# Cron'u başlat ve PHP-FPM'i ön plana getir
CMD cron && php-fpm