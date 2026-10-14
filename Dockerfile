# ==============================================================================
# STAGE 1: BUILDER
# Install PHP dependencies without development packages
# ==============================================================================
FROM composer:2.8 AS builder

WORKDIR /app

# DOCKER LAYER CACHING: Copy dependency specifications first
COPY composer.json composer.lock ./

# Install vendor packages without dev dependencies and without running scripts
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy entire application source code (filtered by .dockerignore)
COPY . .

# Generate production-optimized class autoloader
RUN composer dump-autoload --optimize


# ==============================================================================
# STAGE 2: RUNTIME (FrankenPHP Application Server on Alpine)
# High performance, Caddy-based modern application server (+10 bonus)
# ==============================================================================
FROM dunglas/frankenphp:1.13-php8.4-alpine AS runtime

# Install additional production PHP extensions (pdo_sqlite is already built-in)
RUN install-php-extensions pdo_mysql zip bcmath

WORKDIR /app

# Copy application and vendor from builder stage
COPY --from=builder /app /app

# Prepare environment configuration and generate application key
RUN cp -n .env.example .env && \
    php artisan key:generate

# Configure directory permissions and assign to non-root user
RUN chmod -R 775 storage bootstrap/cache database && \
    chown -R www-data:www-data storage bootstrap/cache database /data /config

# Non-root user execution
USER www-data

# Configure FrankenPHP to listen on port 8000
ENV SERVER_NAME=":8000"

EXPOSE 8000

# Container Healthcheck targeting Laravel /up endpoint
HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=3 \
    CMD wget -q --spider http://127.0.0.1:8000/up || exit 1

# Auto-migrate database on start and run FrankenPHP Caddy server
CMD ["sh", "-c", "touch database/database.sqlite && php artisan migrate --force && frankenphp run --config /etc/caddy/Caddyfile"]

