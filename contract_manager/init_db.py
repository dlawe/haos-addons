import sqlite3
import os

# Speicherort der Datenbank im persistierenden /data-Verzeichnis
db_path = "/data/contracts.db"

# Verbindung zur Datenbank herstellen
def initialize_database():
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    # Tabellen erstellen
    cursor.execute("""
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_name TEXT NOT NULL
    )
    """)

    cursor.execute("""
    CREATE TABLE IF NOT EXISTS contract_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        type_name TEXT NOT NULL
    )
    """)

    cursor.execute("""
    CREATE TABLE IF NOT EXISTS contracts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        provider TEXT NOT NULL,
        cost REAL NOT NULL,
        start_date DATE,
        end_date DATE,
        cancellation_date DATE,
        canceled BOOLEAN DEFAULT 0,
        auto_renew BOOLEAN DEFAULT 1,
        duration INTEGER,
        cancellation_period INTEGER,
        category_id INTEGER,
        contract_type_id INTEGER,
        FOREIGN KEY (category_id) REFERENCES categories(id),
        FOREIGN KEY (contract_type_id) REFERENCES contract_types(id)
    )
    """)

    cursor.execute("""
    CREATE TABLE IF NOT EXISTS payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_id INTEGER,
        payment_date DATE,
        amount REAL NOT NULL,
        payment_method TEXT NOT NULL,
        FOREIGN KEY (contract_id) REFERENCES contracts(id)
    )
    """)

    cursor.execute("""
    CREATE TABLE IF NOT EXISTS reminders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        contract_id INTEGER,
        reminder_date DATE,
        reminder_type TEXT NOT NULL,
        message TEXT NOT NULL,
        FOREIGN KEY (contract_id) REFERENCES contracts(id)
    )
    """)

    conn.commit()
    conn.close()

    print("Datenbank und Tabellen erfolgreich initialisiert.")

# Überprüfen, ob die Datenbank existiert, und falls nicht, initialisieren
if not os.path.exists(db_path):
    initialize_database()
else:
    print("Datenbank existiert bereits.")
