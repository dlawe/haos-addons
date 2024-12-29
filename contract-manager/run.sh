#!/usr/bin/with-contenv bashio

set -x  # Debugging aktivieren
echo "Vertragsmanager Addon wird gestartet..."

# Datenbankinitialisierung
DB_FILE="/data/contracts.db"
if [ ! -f "$DB_FILE" ]; then
  echo "SQLite-Datenbank wird initialisiert..."
  python3 /app/init_db.py
else
  echo "Datenbank existiert bereits: $DB_FILE"
fi

# Verzeichnis für statische Dateien
WEB_DIR="/config/www"

# Stelle sicher, dass das Verzeichnis für die statischen Dateien existiert
if [ ! -d "$WEB_DIR" ]; then
  echo "Fehler: Das Verzeichnis $WEB_DIR existiert nicht!"
  exit 1
fi

# Abrufen des Ingress-Ports (dynamisch von Home Assistant zugewiesen)
PORT=$(bashio::addon.ingress_port)

# Starte den Flask-Webserver
echo "Starte Flask-Webserver auf Port ${PORT}..."
python3 /app/app.py --port "${PORT}" --web-dir "${WEB_DIR}"
