FROM php:8.1-cli-alpine

RUN apk add --no-cache \
    nodejs npm \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring bcmath zip

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize && npm run build

RUN chmod +x /app/docker/render-start.sh

EXPOSE 8000

CMD ["/app/docker/render-start.sh"]
