ARG BUILD_FROM=ghcr.io/hassio-addons/base:latest
FROM ${BUILD_FROM}

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# Installieren der benötigten Pakete
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

# PHP-Konfigurationsanpassungen
RUN php_ini_path=$(php --ini | grep "Loaded Configuration File" | awk '{print $4}') && \
    echo "upload_max_filesize = 10M" >> $php_ini_path && \
    echo "post_max_size = 10M" >> $php_ini_path && \
    echo "memory_limit = 128M" >> $php_ini_path

# Arbeitsverzeichnis setzen
WORKDIR /app

# Skripte und Dateien kopieren
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY www /var/www/html

# Prüfen, ob die Dateien korrekt kopiert wurden
RUN ls -l /var/www/html

# Skript ausführbar machen
RUN chmod +x /run.sh

# Lighttpd konfigurieren
RUN mkdir -p /etc/lighttpd && \
    echo 'server.modules += ( "mod_fastcgi" )' > /etc/lighttpd/lighttpd.conf && \
    echo 'fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/tmp/php.socket" )))' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.indexfiles = ( "index.php", "index.html", "index.htm" )' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.document-root = "/var/www/html"' >> /etc/lighttpd/lighttpd.conf && \
    echo 'alias.url += ( "/icons/" => "/data/icons/" )' >> /etc/lighttpd/lighttpd.conf && \
    echo 'alias.url += ( "/pdfs/" => "/data/pdfs/" )' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.errorlog = "/dev/stderr"' >> /etc/lighttpd/lighttpd.conf && \
    echo 'server.bind = "0.0.0.0"' >> /etc/lighttpd/lighttpd.conf && \
    echo 'accesslog.filename = "/dev/stdout"' >> /etc/lighttpd/lighttpd.conf

# Überprüfung der Lighttpd-Konfiguration hinzufügen
RUN lighttpd -t -f /etc/lighttpd/lighttpd.conf

# Sicherstellen, dass Verzeichnisse vorhanden sind
RUN mkdir -p /data/icons /data/pdfs && \
    chmod -R 755 /data && \
    chown -R www-data:www-data /data


# Datenbankvolumen deklarieren
VOLUME ["/data"]

# Standardport deklarieren
EXPOSE 80

# Sicherstellen, dass PHP-CGI- und Lighttpd-Prozesse funktionieren
RUN php-cgi -v && lighttpd -v

# Start-Skript ausführen
CMD [ "/run.sh" ]
