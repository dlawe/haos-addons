from flask import Flask, jsonify, send_from_directory
import sqlite3
import os
import argparse

# Argumente für dynamischen Port und Web-Verzeichnis
parser = argparse.ArgumentParser()
parser.add_argument("--port", type=int, required=True, help="Port für den Flask-Server")
parser.add_argument("--web-dir", type=str, required=True, help="Verzeichnis für statische Dateien")
args = parser.parse_args()

app = Flask(__name__)
db_path = "/data/contracts.db"
web_dir = args.web_dir

# Test-Endpoint für die Datenbank
@app.route('/check_db', methods=['GET'])
def check_db():
    try:
        if os.path.exists(db_path):
            conn = sqlite3.connect(db_path)
            conn.cursor().execute("SELECT 1")
            conn.close()
            return jsonify({"status": "success", "message": "Datenbankverbindung erfolgreich!"}), 200
        else:
            return jsonify({"status": "error", "message": "Datenbankdatei nicht gefunden!"}), 500
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

# Statische Dateien bereitstellen
@app.route('/', defaults={'path': 'index.html'})
@app.route('/<path:path>')
def serve_static(path):
    return send_from_directory(web_dir, path)

if __name__ == '__main__':
    app.run(host="0.0.0.0", port=args.port)
