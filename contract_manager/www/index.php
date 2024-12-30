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
        }
        .card {
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: white;
            flex: 1;
        }
        .search-bar {
            margin: 20px 0;
        }
        .table-container {
            margin-top: 20px;
        }
        .table {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Vertragsübersicht</h1>

        <!-- Kacheln -->
        <div class="card-container">
            <div class="card">
                <h5>Statistiken</h5>
                <p><?= getContractsCount($db, "canceled = 0 AND (end_date IS NULL OR end_date > date('now'))"); ?> Aktive Verträge</p>
                <p><?= getContractsCount($db, "duration >= 12"); ?> Langzeitverträge</p>
                <p><?= getContractsCount($db, "duration = 1"); ?> Monatsverträge</p>
                <p><?= getContractsCount($db, "canceled = 0 AND end_date BETWEEN date('now') AND date('now', '+30 days') AND cancellation_date < date('now', '+30 days')"); ?> Ablaufende Verträge</p>
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

        <!-- Tabelle mit Verträgen -->
        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Anbieter</th>
                        <th>Kosten</th>
                        <th>Startdatum</th>
                        <th>Enddatum</th>
                        <th>Kündigungsfrist</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['provider']); ?></td>
                            <td><?= number_format($row['cost'], 2, ',', '.'); ?> €</td>
                            <td><?= htmlspecialchars($row['start_date']); ?></td>
                            <td><?= htmlspecialchars($row['end_date']); ?></td>
                            <td><?= htmlspecialchars($row['cancellation_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
