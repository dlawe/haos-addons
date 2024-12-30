<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Verarbeitung des Formulars
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingabedaten sicher verarbeiten
    $provider = htmlspecialchars($_POST['provider']);
    $name = htmlspecialchars($_POST['name']);
    $cost = (float)$_POST['cost'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $duration = (int)$_POST['duration'];
    $cancellation_period = (int)$_POST['cancellation_period'];
    $contract_type = htmlspecialchars($_POST['contract_type']); // Eingabe für Vertragstyp

    // Neuen Vertragstyp speichern, falls nicht vorhanden
    $stmt = $db->prepare("INSERT OR IGNORE INTO contract_types (type_name) VALUES (:type_name)");
    $stmt->bindValue(':type_name', $contract_type, SQLITE3_TEXT);
    $stmt->execute();

    // Vertragstyp-ID abrufen
    $contract_type_id = $db->querySingle("SELECT id FROM contract_types WHERE type_name = '$contract_type'");

    // Kündigungsdatum berechnen
    $cancellation_date = date('Y-m-d', strtotime("$end_date -$cancellation_period months"));

    // SQL zum Einfügen des neuen Vertrags
    $stmt = $db->prepare("
        INSERT INTO contracts (name, provider, cost, start_date, end_date, duration, cancellation_period, cancellation_date, contract_type_id) 
        VALUES (:name, :provider, :cost, :start_date, :end_date, :duration, :cancellation_period, :cancellation_date, :contract_type_id)
    ");
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':provider', $provider, SQLITE3_TEXT);
    $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
    $stmt->bindValue(':start_date', $start_date, SQLITE3_TEXT);
    $stmt->bindValue(':end_date', $end_date, SQLITE3_TEXT);
    $stmt->bindValue(':duration', $duration, SQLITE3_INTEGER);
    $stmt->bindValue(':cancellation_period', $cancellation_period, SQLITE3_INTEGER);
    $stmt->bindValue(':cancellation_date', $cancellation_date, SQLITE3_TEXT);
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
    <style>
        body {
            background-color: #f0f4f8;
            font-family: Arial, sans-serif;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 1.3rem;
            margin-bottom: 20px;
        }
    </style>
    <script>
        // Automatische Berechnung der Laufzeit
        function calculateDuration() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const durationField = document.getElementById('duration');

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                durationField.value = months > 0 ? months : 0; // Laufzeit in Monaten
            } else {
                durationField.value = ''; // Keine Laufzeit berechnen
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Neuen Vertrag hinzufügen</h1>
        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="alert <?= strpos($message, 'erfolgreich') !== false ? 'alert-success' : 'alert-danger'; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <!-- Formular für neuen Vertrag -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="provider" class="form-label">Anbieter</label>
                    <input type="text" class="form-control" id="provider" name="provider" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name des Vertrags</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="cost" class="form-label">Kosten (€)</label>
                    <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Startdatum</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required onchange="calculateDuration()">
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">Enddatum</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required onchange="calculateDuration()">
                </div>
                <div class="mb-3">
                    <label for="duration" class="form-label">Laufzeit (in Monaten)</label>
                    <input type="number" class="form-control" id="duration" name="duration" readonly>
                </div>
                <div class="mb-3">
                    <label for="cancellation_period" class="form-label">Kündigungsfrist (in Monaten)</label>
                    <input type="number" class="form-control" id="cancellation_period" name="cancellation_period" required>
                </div>
                <div class="mb-3">
                    <label for="contract_type" class="form-label">Vertragstyp</label>
                    <input type="text" class="form-control" id="contract_type" name="contract_type" required>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Vertrag speichern</button>
                    <a href="index.php" class="btn btn-secondary">Zurück</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
