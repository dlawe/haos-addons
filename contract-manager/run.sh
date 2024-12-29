#!/usr/bin/with-contenv bashio

set -x  # Debugging aktivieren
echo "Vertragsmanager Add-on wird gestartet..."

# Datenbankinitialisierung
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

# Verzeichnis für statische Dateien
WEB_DIR="/config/www"

# Stelle sicher, dass das Verzeichnis für die statischen Dateien existiert
if [ ! -d "$WEB_DIR" ]; then
  echo "Fehler: Das Verzeichnis $WEB_DIR existiert nicht!"
  mkdir -p "$WEB_DIR" || { 
    echo "Fehler beim Erstellen des Verzeichnisses $WEB_DIR!"; 
    exit 1; 
  }
fi

# Abrufen des Ingress-Ports
PORT=$(bashio::addon.ingress_port || echo "Ingress-Port konnte nichtt abgerufen werden!")

# Überprüfen des Ports und Standardport setzen, falls nicht verfügbar
if [ -z "$PORT" ]; then
  echo "Kein Ingress-Port gefunden, Standardport wird verwendet: 80"
  PORT=80
fi

# Lighttpd konfigurieren, um auf dem dynamischen Ingress-Port zu lauschen
if [ -n "$PORT" ]; then
  sed -i "s/server.port.*/server.port = ${PORT}/g" /etc/lighttpd/lighttpd.conf || {
    echo "Fehler bei der Anpassung der Lighttpd-Konfiguration!"
    exit 1
  }
else
  echo "Standardport wird verwendet, da kein Ingress-Port angegeben ist."
fi

# Starte Lighttpd mit PHP-Unterstützung
echo "Starte Lighttpd-Webserver auf Port ${PORT}..."
lighttpd -f /etc/lighttpd/lighttpd.conf || { 
  echo "Fehler beim Start von Lighttpd!"; 
  exit 1; 
}
