#!/usr/bin/with-contenv bashio

set -x  # Aktiviert ausführliches Debugging
echo "Vertragsmanager Webserver wird gestartet..."
PORT=$(bashio::addon.ingress_port)
python3 -m http.server "${PORT}" --bind 0.0.0.0
