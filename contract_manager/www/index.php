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

    // 1. Rot: Vertrag läuft bald ab
    if ($contract['canceled'] == 0 
        && isset($contract['end_date']) 
        && $contract['end_date'] <= date('Y-m-d', strtotime('+30 days'))) {
        return 'border-red'; // Rot für ablaufende Verträge
    }

    // 2. Grün: Vertrag ist aktiv und läuft nicht bald ab
    if ($contract['canceled'] == 0 
        && (!isset($contract['end_date']) || $contract['end_date'] > $now)) {
        return 'border-green'; // Grün für aktive Verträge
    }

    // 3. Grau: Vertrag ist deaktiviert oder gekündigt
    if ($contract['canceled'] == 1) {
        return 'border-gray'; // Grau für deaktivierte/kündigte Verträge
    }

    // Standard: Keine spezifische Farbe
    return '';
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
            padding-top: 70px; /* Platz für den Header einfügen */
        }
        .contract-card {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 12px; /* Alle Seiten abgerundet */
            width: 300px;
            padding: 15px;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
            border-left: 8px solid transparent; /* Standardfarbig für die Karten */
        }
        .border-red {
            border-left-color: #dc3545; /* Rot für ablaufende Verträge */
        }
        .border-green {
            border-left-color: #28a745; /* Grün für aktive Verträge */
        }
        .border-gray {
            border-left-color: #6c757d; /* Grau für deaktivierte/kündigte Verträge */
        }
        .contract-card h5 {
            font-size: 1rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        .contract-card .provider {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 10px;
        }
        .contract-card .cost {
            font-size: 1rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        .contract-card .dates {
            font-size: 0.85rem;
            color: #777;
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
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
                    <h5><?= htmlspecialchars($row['name']); ?></h5>
                    <p class="provider"><?= htmlspecialchars($row['provider']); ?></p>
                    <p class="cost"><?= number_format($row['cost'], 2, ',', '.'); ?> €</p>
                    <p class="dates">
                        Start: <?= htmlspecialchars($row['start_date']); ?><br>
                        Ende: <?= htmlspecialchars($row['end_date']); ?>
                    </p>
                    <p class="dates">Kündigungsfrist: <?= htmlspecialchars($row['cancellation_date']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
