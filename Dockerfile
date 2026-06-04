# ─────────────────────────────────────────────────────────────────────────────
# Stage 1 – Composer dependencies
# ─────────────────────────────────────────────────────────────────────────────
FROM composer:2.10 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
      --no-dev \
      --no-scripts \
      --no-autoloader \
      --ignore-platform-reqs \
      --prefer-dist

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ─────────────────────────────────────────────────────────────────────────────
# Stage 2 – Node / Vite asset build
# ─────────────────────────────────────────────────────────────────────────────
FROM node:22-alpine AS node-assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ─────────────────────────────────────────────────────────────────────────────
# Stage 3 – Production image (PHP-FPM + Nginx)
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-fpm-alpine AS production

LABEL org.opencontainers.image.title="Bulk Email Checker"
LABEL org.opencontainers.image.description="SaaS bulk email verification platform"

# ── System dependencies ───────────────────────────────────────────────────────
RUN apk add --no-cache \
      nginx \
      supervisor \
      curl \
      libpng-dev \
      libjpeg-turbo-dev \
      freetype-dev \
      libzip-dev \
      oniguruma-dev \
      icu-dev \
      sqlite-dev \
      mysql-client \
      shadow \
      && rm -rf /var/cache/apk/*

# ── PHP extensions ─────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        zip \
        gd \
        intl \
        opcache \
        pcntl \
        sockets \
    && docker-php-ext-enable opcache

# ── PHP configuration ──────────────────────────────────────────────────────────
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# ── Nginx configuration ────────────────────────────────────────────────────────
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# ── Supervisor configuration ────────────────────────────────────────────────────
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ── Application user ───────────────────────────────────────────────────────────
RUN addgroup -g 1000 -S www \
    && adduser -u 1000 -S www -G www \
    && mkdir -p /var/www/html \
    && chown -R www:www /var/www/html

WORKDIR /var/www/html

# ── Copy application files ─────────────────────────────────────────────────────
COPY --from=composer-deps --chown=www:www /app .
COPY --from=node-assets  --chown=www:www /app/public/build public/build

# ── Storage / cache directories ────────────────────────────────────────────────
RUN mkdir -p storage/framework/{cache,sessions,views} \
             storage/logs \
             bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ── Startup script ─────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# ── Nginx PID dir ─────────────────────────────────────────────────────────────
RUN mkdir -p /run/nginx && chown www:www /run/nginx

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
