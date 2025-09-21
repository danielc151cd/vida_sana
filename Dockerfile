FROM php:8.2-cli

# Instalar extensiones para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo pdo_pgsql

# Carpeta de trabajo
WORKDIR /app
COPY . /app

# Puerto que Railway usará
ENV PORT=8080

# Levantar servidor embebido de PHP
CMD ["bash", "-lc", "php -S 0.0.0.0:${PORT} -t /app"]
