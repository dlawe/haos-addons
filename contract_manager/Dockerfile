# ----
# Baue dein Add-on auf dem Home-Assistant-Add-on-Basis-Image auf
# ----
ARG BUILD_FROM=ghcr.io/hassio-addons/base:latest
FROM ${BUILD_FROM}

# Setze eine Shell, die Pipefail zulässt (optional, erleichtert Debugging)
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# ----
# Installation weiterer Pakete
# ----
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

# ----
# Arbeitsverzeichnis für deine App
# ----
WORKDIR /app

# ----
# Kopiere Skripte & Dateien
# ----
# (run.sh nach /run.sh, damit s6-overlay es finden und ausführen kann)
COPY run.sh /run.sh
# Python-Skript zur DB-Initialisierung
COPY init_db.py /app/init_db.py
# Deine www-Dateien in das Webverzeichnis
COPY ./www /var/www/html

# ----
# Skript ausführbar machen
# ----
RUN chmod +x /run.sh

# ----
# Lighttpd konfigurieren
# ----
RUN mkdir -p /etc/lighttpd && \
    echo 'server.modules += ( "mod_fastcgi" )' > /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/var/run/lighttpd/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/var/www/html"' >> /etc/lighttpd/lighttpd.conf

# ----
# Datenverzeichnis (falls du persistente Daten unter /data ablegst)
# ----
VOLUME ["/data"]

# ----
# Exponiere Port 80 (für lokalen Zugriff)
# Ingress funktioniert in Home Assistant aber in der Regel über einen
# dynamischen Tunnel-Port, den dein Add-on zur Laufzeit bekommt.
# ----
EXPOSE 80

# ----
# Starte dein Skript (s6-overlay übernimmt das Management)
# ----
CMD [ "/run.sh" ]
