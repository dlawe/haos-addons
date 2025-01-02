<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Funktionen für die Statistiken
function getContractsCount($db, $condition = '1=1') {
    return $db->querySingle("SELECT COUNT(*) FROM contracts WHERE $condition");
}

function getContracts($db, $condition = '1=1', $search = '') {
    $query = "SELECT * FROM contracts WHERE $condition";
    if (!empty($search)) {
        $query .= " AND (name LIKE '%$search%' OR provider LIKE '%$search%')";
    }
    return $db->query($query);
}

// Funktion zur Bestimmung der Klasse basierend auf dem Vertragsstatus
function getCardClass($contract) {
    $now = date('Y-m-d');

    if ($contract['canceled'] == 0 
        && isset($contract['end_date']) 
        && $contract['end_date'] <= date('Y-m-d', strtotime('+30 days'))) {
        return 'border-red'; // Rot für ablaufende Verträge
    }

    if ($contract['canceled'] == 0 
        && (!isset($contract['end_date']) || $contract['end_date'] > $now)) {
        return 'border-green'; // Grün für aktive Verträge
    }

    if ($contract['canceled'] == 1) {
        return 'border-gray'; // Grau für deaktivierte/kündigte Verträge
    }

    return ''; // Standard: Keine spezifische Farbe
}

// Funktion zur Anpassung der Pfade für Ingress
function getIngressPath($path) {
    $base_path = $_SERVER['HTTP_X_INGRESS_PATH'] ?? '';
    return htmlspecialchars($base_path . '/' . ltrim($path, '/'));
}

// Funktion zum Formatieren des Datums
function formatDate($date) {
    if (!$date) return '';
    return date('d.m.Y', strtotime($date));
}

// Filter aus der URL verarbeiten
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$condition = '1=1'; // Standardbedingung
if ($filter === 'active') {
    $condition = "canceled = 0 AND (end_date IS NULL OR end_date > date('now'))";
} elseif ($filter === 'longterm') {
    $condition = "duration >= 12";
} elseif ($filter === 'monthly') {
    $condition = "duration = 1";
} elseif ($filter === 'expiring') {
    $condition = "canceled = 0 
                  AND end_date BETWEEN date('now') AND date('now', '+30 days') 
                  AND cancellation_date < date('now', '+30 days')";
}

$contracts = getContracts($db, $condition, $search);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertragsübersicht</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 56px;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .header h1 {
            font-size: 1.2rem;
            margin: 0;
        }
        .header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .container {
            padding-top: 70px;
        }
        .card-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .contract-card {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            width: 300px;
            padding: 15px;
            position: relative;
            margin-bottom: 20px;
            border-left: 8px solid transparent;
        }
        .contract-card img.icon {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            object-fit: contain;
            max-width: 100%;
            max-height: 100%;
        }
        .contract-card img.pdf-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            cursor: pointer;
        }
        .border-red {
            border-left-color: #dc3545;
        }
        .border-green {
            border-left-color: #28a745;
        }
        .border-gray {
            border-left-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Vertragsmanager</h1>
        <a href="add_contract.php">+ Vertrag hinzufügen</a>
    </div>

    <div class="container">
        <h1 class="text-center">Vertragsübersicht</h1>

        <!-- Vertragskarten -->
        <div class="card-container">
            <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
                <div class="contract-card <?= getCardClass($row); ?>">
                    <!-- Icon oben rechts -->
                    <?php if (!empty($row['icon_path'])): ?>
                        <img src="<?= getIngressPath($row['icon_path']); ?>" alt="Icon" class="icon">
                    <?php endif; ?>

                    <h5><?= htmlspecialchars($row['name']); ?></h5>
                    <p class="provider">Anbieter: <?= htmlspecialchars($row['provider']); ?></p>
                    <p>Vertragsnehmer: <?= htmlspecialchars($row['contract_holder']); ?></p>
					<p class="cost">Kosten: <?= number_format($row['cost'], 2, ',', '.'); ?> €</p>
                    <p class="dates">
                        Start: <?= formatDate($row['start_date']); ?><br>
                        Ende: <?= formatDate($row['end_date']); ?>
                    </p>
                    <p>Laufzeit: <?= htmlspecialchars($row['duration']); ?> Monate</p>
                    <p>Kündigungsfrist: <?= htmlspecialchars($row['cancellation_period']); ?> Monate</p>
                    <p>Kategorie-ID: <?= htmlspecialchars($row['category_id']); ?></p>

                    <!-- PDF Icon unten rechts -->
                    <?php if (!empty($row['pdf_path'])): ?>
                        <a href="<?= getIngressPath($row['pdf_path']); ?>" target="_blank">
                            <img src="pdf-icon.png" alt="PDF öffnen" class="pdf-icon">
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
