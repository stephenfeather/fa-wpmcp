FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        less \
        libfcgi-bin \
        mariadb-client \
        procps \
        unzip \
        vim \
        php8.3-fpm \
        php8.3-cli \
        php8.3-common \
        php8.3-exif \
        php8.3-gd \
        php8.3-intl \
        php8.3-mbstring \
        php8.3-mysql \
        php8.3-opcache \
        php8.3-redis \
        php8.3-zip \
        php-imagick \
        imagemagick \
    && curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp \
    && wp --info --allow-root >/dev/null \
    && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /run/php \
    && chown -R www-data:www-data /run/php \
    && sed -i 's|^listen = .*|listen = 9000|' /etc/php/8.3/fpm/pool.d/www.conf \
    && sed -i 's|^error_log = .*|error_log = /proc/self/fd/2|' /etc/php/8.3/fpm/php-fpm.conf

EXPOSE 9000

USER www-data

WORKDIR /var/www/html

CMD ["php-fpm8.3", "-F"]
