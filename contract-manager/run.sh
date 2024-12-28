#!/usr/bin/with-contenv bashio

echo "Vertragsmanager Webserver wird gestartet..."

# Starte den Webserver auf Port 8080, nur für lokale Anfragen
python3 -m http.server 8080 --bind 0.0.0.0
