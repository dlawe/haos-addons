# Basis-Image
FROM python:3.8-slim

# Installiere Abhängigkeiten
RUN apt-get update && \
    apt-get install -y \
    sqlite3 \
    php \
    php-sqlite3 \
    php-mbstring \
    php-json \
    php-openssl \
    php-pdo \
    php-cli \
    php-fpm \
    lighttpd && \
    apt-get clean

# Arbeitsverzeichnis setzen
WORKDIR /app

# Kopiere Skripte und Dateien
COPY run.sh /app/run.sh
COPY init_db.py /app/init_db.py
COPY ./www /app/www

# Setze Ausführungsrechte
RUN chmod +x /app/run.sh

# Exponiere Port
EXPOSE 80

# Startbefehl
CMD ["/app/run.sh"]
