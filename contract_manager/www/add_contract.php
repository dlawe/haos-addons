<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Kategorien und Vertragstypen abrufen
$categories = $db->query("SELECT id, category_name FROM categories");
$contractTypes = $db->query("SELECT id, type_name FROM contract_types");

// Verarbeitung des Formulars
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingabedaten sicher verarbeiten
    $name = htmlspecialchars($_POST['name']);
    $provider = htmlspecialchars($_POST['provider']);
    $cost = (float)$_POST['cost'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $duration = (int)$_POST['duration'];
    $cancellation_period = (int)$_POST['cancellation_period'];

    // Kategorie verarbeiten (existierend oder neu)
    $category_name = htmlspecialchars($_POST['category_name']);
    if (!empty($category_name)) {
        // Neue Kategorie speichern, falls sie nicht existiert
        $stmt = $db->prepare("INSERT OR IGNORE INTO categories (category_name) VALUES (:category_name)");
        $stmt->bindValue(':category_name', $category_name, SQLITE3_TEXT);
        $stmt->execute();

        // Kategorie-ID abrufen
        $category_id = $db->querySingle("SELECT id FROM categories WHERE category_name = :category_name", true, [':category_name' => $category_name]);
    } else {
        $category_id = (int)$_POST['category_id'];
    }

    // Vertragstyp verarbeiten (existierend oder neu)
    $contract_type_name = htmlspecialchars($_POST['contract_type_name']);
    if (!empty($contract_type_name)) {
        // Neuen Vertragstyp speichern, falls er nicht existiert
        $stmt = $db->prepare("INSERT OR IGNORE INTO contract_types (type_name) VALUES (:type_name)");
        $stmt->bindValue(':type_name', $contract_type_name, SQLITE3_TEXT);
        $stmt->execute();

        // Vertragstyp-ID abrufen
        $contract_type_id = $db->querySingle("SELECT id FROM contract_types WHERE type_name = :type_name", true, [':type_name' => $contract_type_name]);
    } else {
        $contract_type_id = (int)$_POST['contract_type_id'];
    }

    // Kündigungsdatum berechnen
    $cancellation_date = date('Y-m-d', strtotime("$end_date -$cancellation_period months"));

    // SQL zum Einfügen des neuen Vertrags
    $stmt = $db->prepare("
        INSERT INTO contracts (name, provider, cost, start_date, end_date, duration, cancellation_period, cancellation_date, category_id, contract_type_id) 
        VALUES (:name, :provider, :cost, :start_date, :end_date, :duration, :cancellation_period, :cancellation_date, :category_id, :contract_type_id)
    ");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':provider', $provider, SQLITE3_TEXT);
    $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
    $stmt->bindValue(':start_date', $start_date, SQLITE3_TEXT);
    $stmt->bindValue(':end_date', $end_date, SQLITE3_TEXT);
    $stmt->bindValue(':duration', $duration, SQLITE3_INTEGER);
    $stmt->bindValue(':cancellation_period', $cancellation_period, SQLITE3_INTEGER);
    $stmt->bindValue(':cancellation_date', $cancellation_date, SQLITE3_TEXT);
    $stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
    $stmt->bindValue(':contract_type_id', $contract_type_id, SQLITE3_INTEGER);

    // Vertrag einfügen und Feedback anzeigen
    if ($stmt->execute()) {
        $message = "Vertrag erfolgreich hinzugefügt!";
    } else {
        $message = "Fehler beim Hinzufügen des Vertrags.";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuen Vertrag hinzufügen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Neuen Vertrag hinzufügen</h1>

        <!-- Erfolg- oder Fehlermeldung -->
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, 'erfolgreich') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <!-- Formular für neuen Vertrag -->
        <form method="POST" action="">
            <div class="mb-3">
                <label for="name" class="form-label">Name des Vertrags</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="provider" class="form-label">Anbieter</label>
                <input type="text" class="form-control" id="provider" name="provider" required>
            </div>
            <div class="mb-3">
                <label for="cost" class="form-label">Kosten (€)</label>
                <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
            </div>
            <div class="mb-3">
                <label for="start_date" class="form-label">Startdatum</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">Enddatum</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
            <div class="mb-3">
                <label for="duration" class="form-label">Laufzeit (in Monaten)</label>
                <input type="number" class="form-control" id="duration" name="duration" required>
            </div>
            <div class="mb-3">
                <label for="cancellation_period" class="form-label">Kündigungsfrist (in Monaten)</label>
                <input type="number" class="form-control" id="cancellation_period" name="cancellation_period" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Kategorie</label>
                <select class="form-control" id="category_id" name="category_id">
                    <option value="">Bitte wählen oder eingeben</option>
                    <?php while ($row = $categories->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['category_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" class="form-control mt-2" id="category_name" name="category_name" placeholder="Neue Kategorie hinzufügen">
            </div>
            <div class="mb-3">
                <label for="contract_type_id" class="form-label">Vertragstyp</label>
                <select class="form-control" id="contract_type_id" name="contract_type_id">
                    <option value="">Bitte wählen oder eingeben</option>
                    <?php while ($row = $contractTypes->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?= $row['id']; ?>"><?= htmlspecialchars($row['type_name']); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" class="form-control mt-2" id="contract_type_name" name="contract_type_name" placeholder="Neuen Vertragstyp hinzufügen">
            </div>
            <button type="submit" class="btn btn-success">Vertrag speichern</button>
            <a href="index.php" class="btn btn-secondary">Zurück zur Übersicht</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
