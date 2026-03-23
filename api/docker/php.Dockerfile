FROM laravelsail/php84-composer

# Ensure Redis and Postgres extensions are available.
RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS libssl-dev pkg-config libpq-dev libpq5 \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && docker-php-ext-install pdo_pgsql \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false $PHPIZE_DEPS libssl-dev pkg-config \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
