# Python-Basis-Image mit Alpine
FROM python:3.9-alpine

# Umgebungsvariablen für nicht-interaktive Installation
ENV PYTHONUNBUFFERED=1
ENV DEBIAN_FRONTEND=noninteractive

# Installiere zusätzliche Pakete
RUN apk add --no-cache \
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

# Arbeitsverzeichnis setzen
WORKDIR /app

# Kopiere Skripte und statische Dateien
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY ./www /var/www/html

# Setze Berechtigungen für das Startskript
RUN chmod +x /run.sh

# Konfiguriere Lighttpd
RUN mkdir -p /etc/lighttpd && \
    echo 'server.modules += ( "mod_fastcgi" )' > /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/var/run/lighttpd/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/var/www/html"' >> /etc/lighttpd/lighttpd.conf

# Definiere ein Volume für persistente Daten
VOLUME ["/data"]

# Exponiere Port 80
EXPOSE 80

# Startskript ausführen
CMD ["/run.sh"]
