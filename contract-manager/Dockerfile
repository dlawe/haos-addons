# Base image für Home Assistant
ARG BUILD_FROM
FROM $BUILD_FROM

# Installiere notwendige Pakete
RUN apk add --no-cache \
  python3 \
  py3-pip \
  sqlite  # SQLite für die Datenbank hinzufügen

# Setze das Arbeitsverzeichnis für das Add-on
WORKDIR /app

# Kopiere die Skripte ins Arbeitsverzeichnis
COPY run.sh /run.sh
COPY init_db.py /app/init_db.py
COPY ./www /config/www

# Setze die Ausführungsrechte für das Startskript
RUN chmod a+x /run.sh

# Definiere ein Volumen für persistente Daten
VOLUME [ "/data" ]

# Startskript ausführen
CMD ["/run.sh"]
