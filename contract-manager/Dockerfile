# Base image für Home Assistant
ARG BUILD_FROM
FROM $BUILD_FROM

# Installiere notwendige Pakete, falls erforderlich
RUN apk add --no-cache \
  python3 \
  py3-pip

# Setze das Arbeitsverzeichnis für das Add-on
WORKDIR /data

# Kopiere die Dateien ins Arbeitsverzeichnis
COPY run.sh /run.sh

# Setze die Ausführungsrechte für das Skript
RUN chmod a+x /run.sh

# Starte das Skript, wenn der Container startet
CMD ["/run.sh"]
