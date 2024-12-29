#!/usr/bin/with-contenv bashio

set -x  # Aktiviert ausführliches Debugging
echo "Vertragsmanager Addon wird gestartet..."

# Datenbankinitialisierung
DB_FILE="/data/contracts.db"
if [ ! -f "$DB_FILE" ]; then
  echo "SQLite-Datenbank wird initialisiert..."
  python3 /app/init_db.py
else
  echo "Datenbank existiert bereits: $DB_FILE"
fi

# Definiere das Verzeichnis, in dem die Webdateien liegen (z.B. www)
WEB_DIR="/config/www"

# Stelle sicher, dass das Verzeichnis existiert
if [ ! -d "$WEB_DIR" ]; then
  echo "Fehler: Das Verzeichnis $WEB_DIR existiert nicht!"
  exit 1
fi

# Abrufen des Ingress-Ports
PORT=$(bashio::addon.ingress_port)

# Starte den Python-HTTP-Server im richtigen Verzeichnis
cd "$WEB_DIR"
echo "Starte Webserver auf Port ${PORT}..."
python3 -m http.server "${PORT}" --bind 0.0.0.0
