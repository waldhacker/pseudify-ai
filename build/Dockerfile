ARG PHP_VERSION=8.3

FROM php:$PHP_VERSION-fpm-alpine

ARG BUILD_TAG
ARG MSODBC_SQL_APK_URI
ARG MSSQL_TOOLS_APK_URI
ARG MSODBC_SQL_SIG_URI
ARG MSSQL_TOOLS_SIG_URI

ENV BUILD_TAG=$BUILD_TAG
ENV COMPOSER_HOME=/tmp/composer
ENV COMPOSER_NO_INTERACTION=1
ENV APP_HOME=/opt/pseudify/core/
ENV APP_ENV=prod
ENV APP_DEBUG=true

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./entrypoint /usr/local/bin/entrypoint
COPY ./entrypoint_gui /usr/local/bin/entrypoint_gui
COPY ./supervisor.ini /etc/supervisor.d/supervisor.ini
COPY ./symfony.nginx.conf /etc/nginx/http.d/default.conf
COPY ./fpm.conf /usr/local/etc/php-fpm.d/zz_www.conf
COPY ./php.ini /usr/local/etc/php/conf.d/zz_custom.ini

COPY ./core/src/ /opt/pseudify/core/
COPY ./userdata/ /opt/pseudify/userdata/
COPY ./userdata/ /opt/pseudify/userdata_template/

LABEL maintainer="waldhacker <hello@waldhacker.dev>"

RUN \
    apk -U update \
    && apk add --no-cache \
        autoconf \
        bash \
        dpkg-dev \
        dpkg \
        file \
        g++ \
        gcc \
        git \
        gnupg \
        libc-dev \
        make \
        mariadb-client \
        mysql-client \
        nginx \
        pkgconf \
        postgresql-client \
        postgresql-dev \
        re2c \
        rsync \
        runit \
        shadow \
        sqlite \
        sudo \
        supervisor \
        unixodbc-dev \
    \
    && update-ca-certificates \
    \
    \
    && addgroup -g 61042 pseudify \
    && adduser --uid 61042 --shell /bin/bash --home /home/pseudify/ --disabled-password --ingroup pseudify pseudify \
    && echo 'pseudify ALL=(ALL) NOPASSWD:SETENV: ALL' >> /etc/sudoers \
    \
    \
    && sed -i -e 's/user nginx;/user pseudify;/g' /etc/nginx/nginx.conf \
    \
    \
    && cd /tmp \
    \
    && curl -o msodbcsql.apk $MSODBC_SQL_APK_URI \
    && curl -o mssql_tools.apk $MSSQL_TOOLS_APK_URI \
    \
    && curl -o msodbcsql.sig $MSODBC_SQL_SIG_URI \
    && curl -o mssql_tools.sig $MSSQL_TOOLS_SIG_URI \
    \
    && curl https://packages.microsoft.com/keys/microsoft.asc  | gpg --import - \
    && gpg --verify msodbcsql.sig msodbcsql.apk \
    && gpg --verify mssql_tools.sig mssql_tools.apk \
    \
    && apk add --allow-untrusted msodbcsql.apk \
    && apk add --allow-untrusted mssql_tools.apk \
    \
    && pecl install pdo_sqlsrv \
    && docker-php-ext-enable pdo_sqlsrv.so \
    \
    && pecl install sqlsrv \
    && docker-php-ext-enable sqlsrv.so \
    \
    && docker-php-ext-install -j$(nproc) \
        mysqli \
        opcache \
        pdo_mysql \
        pdo_pgsql \
    \
    \
    && mkdir -p /opt/pseudify/userdata_template/var/ \
    && cd /opt/pseudify/core/ \
    && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-progress --no-interaction \
    \
    \
    && rm -rf /opt/pseudify/userdata \
    && mkdir -p /opt/pseudify/userdata/ \
    && chmod o+rx /opt/pseudify/core/bin/pseudify \
    && chmod -R 777 /opt/pseudify/core/ \
    && chown -R -h pseudify:pseudify /opt/pseudify \
    && chown -R -h pseudify /var/lib/nginx/ \
    \
    \
    && chmod +x /usr/local/bin/entrypoint \
    && chmod +x /usr/local/bin/entrypoint_gui \
    \
    && chmod 444 /etc/supervisord.conf \
    && chmod 555 /etc/supervisor.d/ \
    && chmod 444 /etc/supervisor.d/*.ini \
    \
    \
    && rm -rf /home/www-data \
    && rm -rf /tmp/* \
    && rm -rf /usr/local/bin/docker-php-* \
    && rm -rf /usr/src/* \
    && rm -rf /var/cache/apk/* \
    && rm -rf /var/cache/distfiles/* \
    \
    && apk del --no-network \
        autoconf \
        dpkg-dev \
        dpkg \
        file \
        g++ \
        gcc \
        git \
        gnupg \
        libc-dev \
        llvm18 \
        make \
        pkgconf \
        postgresql-dev \
        re2c \
        unixodbc-dev

WORKDIR /opt/pseudify/core

VOLUME ["/opt/pseudify/userdata/"]

ENTRYPOINT ["/usr/local/bin/entrypoint"]

EXPOSE 80

USER pseudify
