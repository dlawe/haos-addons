# Basis-Image für Home Assistant
ARG BUILD_FROM
FROM $BUILD_FROM

# Installiere notwendige Pakete
RUN apk add --no-cache \
  python3 \
  py3-pip \
  sqlite  # SQLite für die Datenbank hinzufügen

# Arbeitsverzeichnis im Container setzen
WORKDIR /app

# Kopiere die Skripte ins Arbeitsverzeichnis
COPY run.sh /run.sh
COPY app.py /app/app.py
COPY init_db.py /app/init_db.py

# Kopiere statische Dateien ins www-Verzeichnis
COPY ./www /config/www

# Setze Berechtigungen für die Skripte
RUN chmod a+x /run.sh

# Installiere Python-Pakete (z.B. Flask)
RUN pip install flask

# Definiere ein Volumen für persistente Daten
VOLUME [ "/data" ]

# Startskript ausführen
CMD ["/run.sh"]
