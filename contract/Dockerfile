ARG BUILD_FROM
FROM $BUILD_FROM

# Installiere Python3 und Flask
RUN apk add --no-cache python3 py3-pip
RUN pip install flask flask-cors

# Arbeitsverzeichnis setzen
WORKDIR /app

# Kopiere die Startskripte ins Image
COPY run.sh /run.sh
RUN chmod a+x /run.sh

CMD [ "/run.sh" ]
