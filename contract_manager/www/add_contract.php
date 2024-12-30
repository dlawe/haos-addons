<?php
// Verbindung zur Datenbank herstellen
$db = new PDO('sqlite:/data/contracts.db');

// Funktion, um eine Spalte zu prüfen und ggf. hinzuzufügen
function ensureColumnExists($db, $table, $column, $definition) {
    $result = $db->query("PRAGMA table_info($table)");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($column, $columns)) {
        $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "Spalte '$column' in Tabelle '$table' hinzugefügt.<br>";
    }
}

// Tabellen und Spalten sicherstellen
$db->exec("
    CREATE TABLE IF NOT EXISTS contracts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        provider TEXT NOT NULL,
        cost REAL NOT NULL,
        start_date DATE,
        end_date DATE
    )
");
ensureColumnExists($db, 'contracts', 'icon_path', 'TEXT');
ensureColumnExists($db, 'contracts', 'pdf_path', 'TEXT');

// Überprüfen, ob das Formular abgesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $provider = $_POST['provider'];
    $cost = $_POST['cost'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Verzeichnisse definieren
    $icon_dir = '/data/icons/';
    $pdf_dir = '/data/pdfs/';
    
    // Standardwerte für Icon und PDF
    $icon_path = null;
    $pdf_path = null;

    // Hochladen des Icons
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $icon_name = basename($_FILES['icon']['name']);
        $icon_path = $icon_dir . uniqid() . '-' . $icon_name;
        
        if (!is_dir($icon_dir)) {
            mkdir($icon_dir, 0777, true);
        }
        move_uploaded_file($_FILES['icon']['tmp_name'], $icon_path);
    }

    // Hochladen des PDFs
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $pdf_name = basename($_FILES['pdf']['name']);
        $pdf_path = $pdf_dir . uniqid() . '-' . $pdf_name;
        
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        move_uploaded_file($_FILES['pdf']['tmp_name'], $pdf_path);
    }

    // Daten in die Datenbank einfügen
    $sql = "INSERT INTO contracts (name, provider, cost, start_date, end_date, icon_path, pdf_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$name, $provider, $cost, $start_date, $end_date, $icon_path, $pdf_path]);

    echo "Vertrag erfolgreich hinzugefügt!";
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

        <label for="icon">Icon hochladen:</label>
        <input type="file" id="icon" name="icon" accept="image/*"><br>

        <label for="pdf">PDF hochladen:</label>
        <input type="file" id="pdf" name="pdf" accept="application/pdf"><br>

        <button type="submit">Vertrag hinzufügen</button>
    </form>
</body>
</html>
