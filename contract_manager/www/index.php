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
        h1 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        .card-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .card {
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: white;
            flex: 1;
            max-width: 250px;
        }
        .filter-button {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .blue {
            background-color: #007bff;
        }
        .green {
            background-color: #28a745;
        }
        .orange {
            background-color: #ffc107;
        }
        .red {
            background-color: #dc3545;
        }
        .search-bar {
            margin: 20px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Vertragsübersicht</h1>

        <!-- Kacheln -->
        <div class="card-container">
            <div class="card">
                <a href="index.php?filter=active" class="filter-button blue">
                    <span><?= getContractsCount($db, "canceled = 0 AND (end_date IS NULL OR end_date > date('now'))"); ?> Aktive Verträge</span>
                </a>
                <a href="index.php?filter=longterm" class="filter-button green">
                    <span><?= getContractsCount($db, "duration >= 12"); ?> Langzeitverträge</span>
                </a>
                <a href="index.php?filter=monthly" class="filter-button orange">
                    <span><?= getContractsCount($db, "duration = 1"); ?> Monatsverträge</span>
                </a>
                <a href="index.php?filter=expiring" class="filter-button red">
                    <span><?= getContractsCount($db, "canceled = 0 AND end_date BETWEEN date('now') AND date('now', '+30 days') AND cancellation_date < date('now', '+30 days')"); ?> Ablaufende Verträge</span>
                </a>
            </div>
            <div class="card">
                <h5>Gesamtkosten</h5>
                <p><?= number_format($db->querySingle("SELECT SUM(cost) FROM contracts WHERE canceled = 0"), 2, ',', '.'); ?> €</p>
            </div>
        </div>

        <!-- Suchleiste -->
        <div class="search-bar">
            <form method="GET" action="">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter); ?>">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Nach Vertrag oder Anbieter suchen..." value="<?= htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">Suchen</button>
                </div>
            </form>
        </div>

        <!-- Vertragskarten -->
        <div class="card-container">
            <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
                <div class="contract-card">
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
