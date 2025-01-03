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
    contract_holder TEXT,
    canceled BOOLEAN DEFAULT 0,
    auto_renew BOOLEAN DEFAULT 1,
    duration INTEGER,
    cancellation_period INTEGER,
    category_id INTEGER,
    icon_path TEXT,
    pdf_path TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
");

// Sicherstellen, dass alle benötigten Spalten existieren
$columns = [
    'canceled' => 'BOOLEAN DEFAULT 0',
    'auto_renew' => 'BOOLEAN DEFAULT 1',
    'duration' => 'INTEGER',
    'cancellation_period' => 'INTEGER',
    'category_id' => 'INTEGER',
    'icon_path' => 'TEXT',
    'pdf_path' => 'TEXT',
    'contract_holder' => 'TEXT'
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
    $contract_holder = $_POST['contract_holder'] ?? null;
    $cancellation_period = $_POST['cancellation_period'] ?? null;
    $duration = $_POST['duration'] ?? null;
    $category_id = $_POST['category_id'] ?? null;

    // Verzeichnisse definieren
    $icon_dir = '/data/icons/';
    $pdf_dir = '/data/pdfs/';

    // Standardwerte für Icon und PDF
    $icon_path = null;
    $pdf_path = null;

    $errors = [];

    // Hochladen des Icons
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif'];
        if (!in_array($fileExtension, $allowed_extensions)) {
            $errors[] = "Nur PNG, JPG, JPEG und GIF sind als Icon erlaubt.";
        } else {
            $icon_name = uniqid('icon_', true) . '.' . $fileExtension; // Eindeutiger Name
            $icon_path = 'icons/' . $icon_name; // Relativer Pfad für die Datenbank
            $full_icon_path = $icon_dir . $icon_name; // Absoluter Pfad für die Speicherung

            if (!is_dir($icon_dir)) {
                mkdir($icon_dir, 0777, true);
            }
            if (!move_uploaded_file($_FILES['icon']['tmp_name'], $full_icon_path)) {
                $errors[] = "Das Icon konnte nicht hochgeladen werden.";
            }
        }
    } else {
        $errors[] = "Bitte ein gültiges Icon hochladen.";
    }

    // Hochladen des PDFs
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'pdf') {
            $errors[] = "Nur PDF-Dateien sind erlaubt.";
        } else {
            $pdf_name = uniqid('pdf_', true) . '.' . $fileExtension; // Eindeutiger Name
            $pdf_path = 'pdfs/' . $pdf_name; // Relativer Pfad für die Datenbank
            $full_pdf_path = $pdf_dir . $pdf_name; // Absoluter Pfad für die Speicherung

            if (!is_dir($pdf_dir)) {
                mkdir($pdf_dir, 0777, true);
            }
            if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $full_pdf_path)) {
                $errors[] = "Das PDF konnte nicht hochgeladen werden.";
            }
        }
    } else {
        $errors[] = "Bitte ein gültiges PDF hochladen.";
    }


    // Überprüfen, ob Fehler vorliegen
    if (empty($errors)) {
        // Daten in die Datenbank einfügen
        $sql = "INSERT INTO contracts (
            name, provider, cost, start_date, end_date, contract_holder, 
            canceled, auto_renew, duration, cancellation_period, 
            category_id, icon_path, pdf_path
        ) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $name, $provider, $cost, $start_date, $end_date, $contract_holder,
            $duration, $cancellation_period, $category_id, $icon_path, $pdf_path
        ]);

        echo "<p style='color:green;'>Vertrag erfolgreich hinzugefügt!</p>";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h1 class="text-center">Neuen Vertrag hinzufügen</h1>
            </div>
            <div class="card-body">
                <form action="add_contract.php" method="post" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name:</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="provider" class="form-label">Anbieter:</label>
                        <input type="text" id="provider" name="provider" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="contract_holder" class="form-label">Vertragsnehmer:</label>
                        <input type="text" id="contract_holder" name="contract_holder" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cost" class="form-label">Kosten:</label>
                        <input type="number" step="0.01" id="cost" name="cost" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Startdatum:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">Enddatum:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="cancellation_period" class="form-label">Kündigungsfrist (Monate):</label>
                        <input type="number" id="cancellation_period" name="cancellation_period" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="duration" class="form-label">Laufzeit (Monate):</label>
                        <input type="number" id="duration" name="duration" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Kategorie:</label>
                        <select id="category_id" name="category_id" class="form-select">
                            <option value="1">Strom</option>
                            <option value="2">Gas</option>
                            <option value="3">Internet</option>
                            <option value="4">Mobilfunk</option>
                            <option value="5">Versicherung</option>
                            <option value="6">Streaming</option>
                            <option value="7">Fitnessstudio</option>
                            <option value="8">Zeitschriften</option>
                            <option value="9">Miete</option>
                            <option value="10">Sonstiges</option>
                            <option value="11">Wartungsverträge</option>
                            <option value="12">Cloud-Dienste</option>
                            <option value="13">Software-Abonnements</option>
                            <option value="14">Mitgliedschaften</option>
                            <option value="15">Autoversicherung</option>
                            <option value="16">Rechtsschutz</option>
                            <option value="17">Hausrat</option>
                            <option value="18">Reiseversicherungen</option>
                            <option value="19">Bausparen</option>
                            <option value="20">Kreditverträge</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="icon" class="form-label">Icon hochladen:</label>
                        <input type="file" id="icon" name="icon" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label for="pdf" class="form-label">PDF hochladen:</label>
                        <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf">
                    </div>
                    <div class="col-12 text-center">
                        <button type="submit" class="btn btn-primary">Vertrag hinzufügen</button>
                        <a href="index.php" class="btn btn-secondary">Zurück zur Übersicht</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
