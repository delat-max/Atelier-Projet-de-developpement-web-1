FROM php:8.3-fpm
RUN apt-get update \
	&& apt-get install -y --no-install-recommends unzip \
	&& docker-php-ext-install pdo_mysql \
	&& rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY backend/composer.json ./composer.json
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress
COPY backend/ ./
COPY docker/api/php.ini $PHP_INI_DIR/conf.d/nrv.ini
