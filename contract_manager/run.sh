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
# 2) Web-Verzeichnis prüfen
# ---------------------------------
WEB_DIR="/var/www/html"
if [ ! -d "$WEB_DIR" ]; then
  echo "Verzeichnis $WEB_DIR existiert nicht! Erstelle es ..."
  mkdir -p "$WEB_DIR" || {
    echo "Fehler beim Erstellen des Verzeichnisses $WEB_DIR!"
    exit 1
  }
fi

# ---------------------------------
# 3) Ingress-Port abfragen (Bashio)
# ---------------------------------
PORT=$(bashio::addon.ingress_port 2>/dev/null || true)
if [ -z "$PORT" ] || [ "$PORT" = "null" ]; then
  echo "Kein Ingress-Port gefunden, Standardport wird verwendet: 80"
  PORT=80
else
  echo "Ingress-Port ermittelt: $PORT"
fi

# ---------------------------------
# 4) Lighttpd-Port anpassen
# ---------------------------------
if [ -f /etc/lighttpd/lighttpd.conf ]; then
  # Wir setzen den Port
  sed -i "s/server.port.*/server.port = ${PORT}/g" /etc/lighttpd/lighttpd.conf
else
  echo "Warnung: /etc/lighttpd/lighttpd.conf wurde nicht gefunden!"
fi

# ---------------------------------
# 5) Log-Ausgaben in Container-Logs umleiten
# ---------------------------------
echo 'server.errorlog = "/dev/stderr"' >> /etc/lighttpd/lighttpd.conf
echo 'accesslog.filename = "/dev/stdout"' >> /etc/lighttpd/lighttpd.conf

# Optional: auf 0.0.0.0 binden
echo 'server.bind = "0.0.0.0"' >> /etc/lighttpd/lighttpd.conf

# ---------------------------------
# 6) Verzeichnis für PHP-Socket anlegen und Berechtigungen setzen
# ---------------------------------
mkdir -p /var/run/lighttpd
chown -R lighttpd:lighttpd /var/run/lighttpd

# ---------------------------------
# 7) Lighttpd im Foreground starten (Debug/Don’t daemonize)
# ---------------------------------
echo "Starte Lighttpd-Webserver auf Port ${PORT}..."
lighttpd -D -f /etc/lighttpd/lighttpd.conf || { 
  echo "Fehler beim Start von Lighttpd!"
  exit 1
}
