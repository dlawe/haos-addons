# 1) Home Assistant Add-on Base-Image (Community) verwenden
ARG BUILD_FROM=ghcr.io/hassio-addons/base:latest
FROM ${BUILD_FROM}

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# 2) Installiere benötigte Pakete (Python, PHP, Lighttpd, usw.)
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

# 3) Socket-Verzeichnis für Lighttpd erzeugen und gleich die Rechte setzen
#    (hier sind wir Root; das Add-on läuft später oft als non-root)
RUN mkdir -p /var/run/lighttpd \
    && chown -R lighttpd:lighttpd /var/run/lighttpd

# 4) Arbeitsverzeichnis definieren
WORKDIR /app

# 5) Dateien in Container kopieren
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY www /var/www/html

# Skript ausführbar machen
RUN chmod +x /run.sh

# 6) Lighttpd-Konfiguration vorbereiten
RUN mkdir -p /etc/lighttpd && \
    echo 'server.modules += ( "mod_fastcgi" )' > /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/var/run/lighttpd/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/var/www/html"' >> /etc/lighttpd/lighttpd.conf

# 7) (Optional) /data als persistent
VOLUME ["/data"]

# 8) Standard-Port (nur für lokale Tests, Ingress kommt dynamisch)
EXPOSE 80

# 9) Starte run.sh (S6-Overlay übernimmt diesen Aufruf)
CMD [ "/run.sh" ]
