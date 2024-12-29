# Base image für Home Assistant
ARG BUILD_FROM
FROM $BUILD_FROM

# Installiere notwendige Pakete
RUN apk add --no-cache \
    python3 \
    py3-pip \
    sqlite \
    php7 \
    php7-sqlite3 \
    php7-mbstring \
    php7-session \
    php7-json \
    php7-openssl \
    php7-pdo \
    php7-pdo_sqlite \
    php7-cli \
    php7-fpm \
    php7-cgi \
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
RUN mkdir -p /etc/lighttpd
RUN echo 'server.modules += ( "mod_fastcgi" )' >> /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/tmp/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/config/www"' >> /etc/lighttpd/lighttpd.conf

# Definiere ein Volumen für persistente Daten
VOLUME ["/data"]

# Exponiere Standard-HTTP-Port (optional, falls nicht nur Ingress genutzt wird)
EXPOSE 80

# Startskript ausführen
CMD ["/run.sh"]
