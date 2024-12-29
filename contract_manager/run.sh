#!/usr/bin/with-contenv bashio

set -x
echo "Vertragsmanager Add-on wird gestartet..."

# 1) Datenbank initialisieren
DB_FILE="/data/contracts.db"
if [ ! -d "/data" ]; then
  echo "Fehler: /data-Verzeichnis existiert nicht oder ist nicht gemountet!"
  exit 1
fi

if [ ! -f "$DB_FILE" ]; then
  echo "SQLite-Datenbank wird initialisiert..."
  python3 /app/init_db.py || { 
    echo "Fehler bei der Datenbankinitialisierung!"; 
    exit 1; 
  }
else
  echo "Datenbank existiert bereits: $DB_FILE"
fi

# 2) Web-Verzeichnis prüfen
WEB_DIR="/var/www/html"
if [ ! -d "$WEB_DIR" ]; then
  echo "Verzeichnis $WEB_DIR existiert nicht! Erstelle es ..."
  mkdir -p "$WEB_DIR" || {
    echo "Fehler beim Erstellen des Verzeichnisses $WEB_DIR!"
    exit 1
  }
fi

if [ ! -f "$WEB_DIR/index.php" ]; then
  echo "Fehler: index.php fehlt im Web-Verzeichnis $WEB_DIR!"
  exit 1
fi

# 3) Ingress-Port abfragen (Bashio)
PORT=$(bashio::addon.ingress_port 2>/dev/null || true)
if [ -z "$PORT" ] || [ "$PORT" = "null" ]; then
  echo "Kein Ingress-Port gefunden, Standardport wird verwendet: 80"
  PORT=80
fi

# 4) Lighttpd-Port anpassen
if [ -f /etc/lighttpd/lighttpd.conf ]; then
  # Prüfen, ob server.port existiert, und entsprechend hinzufügen oder ersetzen
  if ! grep -q "server.port" /etc/lighttpd/lighttpd.conf; then
    echo "server.port = ${PORT}" >> /etc/lighttpd/lighttpd.conf
  else
    sed -i "s/server.port.*/server.port = ${PORT}/g" /etc/lighttpd/lighttpd.conf
  fi
else
  echo "Warnung: /etc/lighttpd/lighttpd.conf wurde nicht gefunden! Erstelle Standardkonfiguration..."
  cat <<EOL > /etc/lighttpd/lighttpd.conf
server.modules += ( "mod_fastcgi" )
fastcgi.server = ( ".php" => (( "bin-path" => "/usr/bin/php-cgi", "socket" => "/tmp/php.socket" )))
server.indexfiles = ( "index.php", "index.html", "index.htm" )
server.document-root = "$WEB_DIR"
server.port = $PORT
server.errorlog = "/dev/stderr"
accesslog.filename = "/dev/stdout"
server.bind = "0.0.0.0"
EOL
fi

# Lighttpd-Konfiguration prüfen
lighttpd -t -f /etc/lighttpd/lighttpd.conf || {
  echo "Fehler: Ungültige Lighttpd-Konfiguration!"
  exit 1
}

# 5) Lighttpd starten
echo "Starte Lighttpd-Webserver auf Port ${PORT}..."
lighttpd -D -f /etc/lighttpd/lighttpd.conf || { 
  echo "Fehler beim Start von Lighttpd!"
  exit 1
}
