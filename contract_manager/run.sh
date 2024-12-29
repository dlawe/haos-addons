#!/usr/bin/with-contenv bashio

set -x  # Debugging aktivieren
echo "Vertragsmanager Add-on wird gestartet..."

# ---------------------------------
# 1) Datenbank initialisieren
# ---------------------------------
DB_FILE="/data/contracts.db"
if [ ! -f "$DB_FILE" ]; then
  echo "SQLite-Datenbank wird initialisiert..."
  python3 /app/init_db.py || { 
    echo "Fehler bei der Datenbankinitialisierung!"; 
    exit 1; 
  }
else
  echo "Datenbank existiert bereits: $DB_FILE"
fi

# ---------------------------------
# 2) Verzeichnis für statische Dateien
#    (hier: /var/www/html entsprechend deiner Dockerfile)
# ---------------------------------
WEB_DIR="/var/www/html"

# Prüfen, ob das Verzeichnis bereits existiert
if [ ! -d "$WEB_DIR" ]; then
  echo "Verzeichnis $WEB_DIR existiert nicht! Erstelle es ..."
  mkdir -p "$WEB_DIR" || {
    echo "Fehler beim Erstellen des Verzeichnisses $WEB_DIR!"
    exit 1
  }
fi

# ---------------------------------
# 3) Ingress-Port auslesen
# ---------------------------------
PORT=$(bashio::addon.ingress_port 2>/dev/null || true)

if [ -z "$PORT" ] || [ "$PORT" = "null" ]; then
  echo "Kein Ingress-Port gefunden, Standardport wird verwendet: 80"
  PORT=80
else
  echo "Ingress-Port ermittelt: $PORT"
fi

# ---------------------------------
# 4) Lighttpd konfigurieren (Port anpassen)
# ---------------------------------
if [ -f /etc/lighttpd/lighttpd.conf ]; then
  sed -i "s/server.port.*/server.port = ${PORT}/g" /etc/lighttpd/lighttpd.conf || {
    echo "Fehler bei der Anpassung der Lighttpd-Konfiguration!"
    exit 1
  }
else
  echo "Warnung: /etc/lighttpd/lighttpd.conf wurde nicht gefunden!"
fi

# ---------------------------------
# 5) Lighttpd mit PHP-Unterstützung starten
# ---------------------------------
echo "Starte Lighttpd-Webserver auf Port ${PORT}..."
lighttpd -f /etc/lighttpd/lighttpd.conf || { 
  echo "Fehler beim Start von Lighttpd!"
  exit 1
}
