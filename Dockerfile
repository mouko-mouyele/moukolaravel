FROM webdevops/php-nginx:8.1-alpine

WORKDIR /app

RUN apk add --no-cache nodejs npm php81-pdo_pgsql php81-pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --optimize && npm run build

ENV WEB_DOCUMENT_ROOT=/app/public
ENV APP_ENV=production

RUN chmod +x /app/docker/render-start.sh

CMD ["/app/docker/render-start.sh"]
