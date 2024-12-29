ARG BUILD_FROM=ghcr.io/hassio-addons/base:latest
FROM ${BUILD_FROM}

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Python + PHP + Lighttpd etc. installieren
RUN apk add --no-cache \
    python3 \
    php \
    php-sqlite3 \
    php-mbstring \
    php-json \
    php-openssl \
    php-pdo \
    php-pdo_sqlite \
    php-cli \
    php-fpm \
    php-cgi \
    sqlite \
    lighttpd \
    bash

# /var/run/lighttpd vorbereiten + Eigentümer setzen
RUN mkdir -p /var/run/lighttpd \
    && chown -R lighttpd:lighttpd /var/run/lighttpd

WORKDIR /app
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY www /var/www/html

RUN chmod +x /run.sh

# Lighttpd-Konfiguration einrichten...
RUN mkdir -p /etc/lighttpd && \
    echo 'server.modules += ( "mod_fastcgi" )' > /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/var/run/lighttpd/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/var/www/html"' >> /etc/lighttpd/lighttpd.conf

VOLUME ["/data"]
EXPOSE 80

CMD [ "/run.sh" ]
