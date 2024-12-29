# Basis-Image auf Ubuntu setzen
FROM ubuntu:latest

# Umgebungsvariablen für nicht-interaktive Installation setzen
ENV DEBIAN_FRONTEND=noninteractive

# System aktualisieren und benötigte Pakete installieren
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
        python3 \
        python3-pip \
        sqlite3 \
        php \
        php-sqlite3 \
        php-mbstring \
        php-json \
        php-openssl \
        php-pdo \
        php-cli \
        php-fpm \
        php-cgi \
        lighttpd \
        && apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Arbeitsverzeichnis festlegen
WORKDIR /app

# Skripte und statische Dateien kopieren
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY ./www /config/www

# Ausführungsrechte für das Startskript setzen
RUN chmod +x /run.sh

# Lighttpd für PHP konfigurieren
RUN mkdir -p /etc/lighttpd/conf-available && \
    echo 'server.modules += ( "mod_fastcgi" )' > /etc/lighttpd/conf-available/15-fastcgi-php.conf && \
    echo 'fastcgi.server = ( ".php" => ((' >> /etc/lighttpd/conf-available/15-fastcgi-php.conf && \
    echo '    "bin-path" => "/usr/bin/php-cgi",' >> /etc/lighttpd/conf-available/15-fastcgi-php.conf && \
    echo '    "socket" => "/var/run/lighttpd/php.socket"' >> /etc/lighttpd/conf-available/15-fastcgi-php.conf && \
    echo ')))' >> /etc/lighttpd/conf-available/15-fastcgi-php.conf && \
    ln -s /etc/lighttpd/conf-available/15-fastcgi-php.conf /etc/lighttpd/conf-enabled/15-fastcgi-php.conf && \
    echo 'server.document-root = "/config/www"' >> /etc/lighttpd/lighttpd.conf

# Verzeichnis für persistente Daten definieren
VOLUME ["/data"]

# Exponiere den Standard-HTTP-Port
EXPOSE 80

# Startskript ausführen
CMD ["/run.sh"]
