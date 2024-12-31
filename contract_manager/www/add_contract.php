<?php
// Verbindung zur Datenbank herstellen
$db = new PDO('sqlite:/data/contracts.db');

// Funktion, um eine Tabelle zu erstellen, falls sie nicht existiert
function ensureTableExists($db, $table, $columns) {
    $db->exec("CREATE TABLE IF NOT EXISTS $table ($columns)");
    echo "Tabelle '$table' wurde erstellt oder existierte bereits.<br>";
}

// Funktion, um eine Spalte zu prüfen und ggf. hinzuzufügen
function ensureColumnExists($db, $table, $column, $definition) {
    $result = $db->query("PRAGMA table_info($table)");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($column, $columns)) {
        $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "Spalte '$column' in Tabelle '$table' hinzugefügt.<br>";
    }
}

// Sicherstellen, dass die Tabelle "contracts" existiert
ensureTableExists($db, 'contracts', "
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
    icon_path TEXT,
    pdf_path TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (contract_type_id) REFERENCES contract_types(id)
");

// Sicherstellen, dass alle benötigten Spalten existieren
$columns = [
    'cancellation_date' => 'DATE',
    'canceled' => 'BOOLEAN DEFAULT 0',
    'auto_renew' => 'BOOLEAN DEFAULT 1',
    'duration' => 'INTEGER',
    'cancellation_period' => 'INTEGER',
    'category_id' => 'INTEGER',
    'contract_type_id' => 'INTEGER',
    'icon_path' => 'TEXT',
    'pdf_path' => 'TEXT'
];
foreach ($columns as $column => $definition) {
    ensureColumnExists($db, 'contracts', $column, $definition);
}

// Überprüfen, ob das Formular abgesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $provider = $_POST['provider'];
    $cost = $_POST['cost'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $cancellation_date = $_POST['cancellation_date'] ?? null;
    $cancellation_period = $_POST['cancellation_period'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $contract_type_id = $_POST['contract_type_id'] ?? null;

    // Verzeichnisse definieren
    $icon_dir = '/data/icons/';
    $pdf_dir = '/data/pdfs/';

    // Standardwerte für Icon und PDF
    $icon_path = null;
    $pdf_path = null;

    $errors = [];

    // Hochladen des Icons
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION);
        $icon_name = uniqid('icon_', true) . '.' . $fileExtension;
        $icon_path = $icon_dir . $icon_name;

        if (!is_dir($icon_dir)) {
            mkdir($icon_dir, 0777, true);
        }
        if (!move_uploaded_file($_FILES['icon']['tmp_name'], $icon_path)) {
            $errors[] = "Das Icon konnte nicht hochgeladen werden.";
        }
    } else {
        $errors[] = "Bitte ein gültiges Icon hochladen.";
    }

    // Hochladen des PDFs
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_name = basename($_FILES['pdf']['name']);
        $pdf_path = $pdf_dir . uniqid() . '-' . $pdf_name;

        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path)) {
            $errors[] = "Das PDF konnte nicht hochgeladen werden.";
        }
    } else {
        $errors[] = "Bitte ein gültiges PDF hochladen.";
    }

    // Überprüfen, ob Fehler vorliegen
    if (empty($errors)) {
        // Daten in die Datenbank einfügen
        $sql = "INSERT INTO contracts (
            name, provider, cost, start_date, end_date, cancellation_date, 
            canceled, auto_renew, duration, cancellation_period, 
            category_id, contract_type_id, icon_path, pdf_path
        ) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $name, $provider, $cost, $start_date, $end_date, $cancellation_date,
            $duration, $cancellation_period, $category_id, $contract_type_id, $icon_path, $pdf_path
        ]);

        echo "Vertrag erfolgreich hinzugefügt!";
    } else {
        // Fehler ausgeben
        foreach ($errors as $error) {
            echo "<p style='color:red;'>$error</p>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertrag hinzufügen</title>
</head>
<body>
    <h1>Neuen Vertrag hinzufügen</h1>
    <form action="add_contract.php" method="post" enctype="multipart/form-data">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required><br>

        <label for="provider">Anbieter:</label>
        <input type="text" id="provider" name="provider" required><br>

        <label for="cost">Kosten:</label>
        <input type="number" step="0.01" id="cost" name="cost" required><br>

        <label for="start_date">Startdatum:</label>
        <input type="date" id="start_date" name="start_date"><br>

        <label for="end_date">Enddatum:</label>
        <input type="date" id="end_date" name="end_date"><br>

        <label for="cancellation_date">Kündigungsdatum:</label>
        <input type="date" id="cancellation_date" name="cancellation_date"><br>

        <label for="cancellation_period">Kündigungsfrist (Monate):</label>
        <input type="number" id="cancellation_period" name="cancellation_period"><br>

        <label for="duration">Laufzeit (Monate):</label>
        <input type="number" id="duration" name="duration"><br>

        <label for="category_id">Kategorie:</label>
        <select id="category_id" name="category_id">
            <option value="1">Kategorie 1</option>
            <option value="2">Kategorie 2</option>
        </select><br>

        <label for="contract_type_id">Vertragstyp:</label>
        <select id="contract_type_id" name="contract_type_id">
            <option value="1">Typ 1</option>
            <option value="2">Typ 2</option>
        </select><br>

        <label for="icon">Icon hochladen:</label>
        <input type="file" id="icon" name="icon" accept="image/*"><br>

        <label for="pdf">PDF hochladen:</label>
        <input type="file" id="pdf" name="pdf" accept="application/pdf"><br>

        <button type="submit">Vertrag hinzufügen</button>
    </form>
</body>
</html>
