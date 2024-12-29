# Basis-Image für Home Assistant Add-ons
ARG BUILD_FROM=ghcr.io/hassio-addons/base:16.2.0
FROM ${BUILD_FROM}

# Installiere zusätzliche Pakete
RUN apk add --no-cache \
    python3 \
    py3-pip \
    sqlite \
    php \
    php-sqlite3 \
    php-mbstring \
    php-session \
    php-json \
    php-openssl \
    php-pdo \
    php-pdo_sqlite \
    php-cli \
    php-fpm \
    php-cgi \
    lighttpd \
    lighttpd-mod-fastcgi

# Setze das Arbeitsverzeichnis für das Add-on
WORKDIR /app

# Kopiere die Skripte und statischen Dateien
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY ./www /config/www

# Setze Berechtigungen für das Startskript
RUN chmod a+x /run.sh

# Konfiguriere Lighttpd (PHP FastCGI-Integration)
RUN mkdir -p /etc/lighttpd && \
    echo 'server.modules += ( "mod_fastcgi" )' >> /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/tmp/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/config/www"' >> /etc/lighttpd/lighttpd.conf

# Definiere ein Volumen für persistente Daten
VOLUME ["/data"]


# Startskript ausführen
CMD ["/run.sh"]
