<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Funktionen für die Statistiken
function getActiveContractsCount($db) {
    return $db->querySingle("
        SELECT COUNT(*) 
        FROM contracts 
        WHERE canceled = 0 AND (end_date IS NULL OR end_date > date('now'))
    ");
}

function getLongTermContractsCount($db) {
    return $db->querySingle("
        SELECT COUNT(*) 
        FROM contracts 
        WHERE duration >= 12
    ");
}

function getMonthlyContractsCount($db) {
    return $db->querySingle("
        SELECT COUNT(*) 
        FROM contracts 
        WHERE duration = 1
    ");
}

function getExpiringContractsCount($db) {
    return $db->querySingle("
        SELECT COUNT(*) 
        FROM contracts 
        WHERE canceled = 0 
        AND end_date BETWEEN date('now') AND date('now', '+30 days') 
        AND cancellation_date < date('now', '+30 days')
    ");
}

// Filter aus der URL verarbeiten
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM contracts";
if ($filter === 'active') {
    $query .= " WHERE canceled = 0 AND (end_date IS NULL OR end_date > date('now'))";
} elseif ($filter === 'longterm') {
    $query .= " WHERE duration >= 12";
} elseif ($filter === 'monthly') {
    $query .= " WHERE duration = 1";
} elseif ($filter === 'expiring') {
    $query .= " WHERE canceled = 0 
                AND end_date BETWEEN date('now') AND date('now', '+30 days') 
                AND cancellation_date < date('now', '+30 days')";
}
if (!empty($search)) {
    $query .= ($filter !== 'all' ? " AND" : " WHERE") . " (name LIKE '%$search%' OR provider LIKE '%$search%')";
}

$contracts = $db->query($query);
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
            background-color: #f0f4f8;
            color: #333;
            font-family: Arial, sans-serif;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header input {
            flex: 1;
            margin-right: 10px;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .header .btn {
            background-color: #0078d4;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-align: center;
        }
        .header .btn:hover {
            background-color: #005a9e;
        }
        .stat-cards {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            text-align: center;
            padding: 15px;
            border-radius: 4px;
            color: white;
        }
        .stat-card.blue {
            background-color: #0078d4;
        }
        .stat-card.green {
            background-color: #28a745;
        }
        .stat-card.yellow {
            background-color: #ffc107;
        }
        .stat-card.red {
            background-color: #dc3545;
        }
        .table-container {
            margin-top: 20px;
            border-radius: 4px;
            overflow: hidden;
            background-color: white;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th {
            background-color: #f9f9f9;
            color: #555;
            text-align: left;
            padding: 10px;
        }
        .table td {
            padding: 10px;
            border-top: 1px solid #eee;
        }
        .table tbody tr:hover {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Vertragsübersicht</h1>

        <!-- Header: Suchleiste und Button -->
        <div class="header">
            <form class="d-flex flex-grow-1" method="GET" action="">
                <input type="text" class="form-control" name="search" placeholder="Nach Name oder Anbieter suchen..." value="<?= htmlspecialchars($search); ?>">
            </form>
            <a href="add_contract.php" class="btn">Neuen Vertrag hinzufügen</a>
        </div>

        <!-- Übersicht der Statistiken -->
        <div class="stat-cards">
            <div class="stat-card blue">
                <h2>Aktive Verträge</h2>
                <p><?= getActiveContractsCount($db); ?></p>
            </div>
            <div class="stat-card green">
                <h2>Langzeitverträge</h2>
                <p><?= getLongTermContractsCount($db); ?></p>
            </div>
            <div class="stat-card yellow">
                <h2>Monatsverträge</h2>
                <p><?= getMonthlyContractsCount($db); ?></p>
            </div>
            <div class="stat-card red">
                <h2>Ablaufende Verträge</h2>
                <p><?= getExpiringContractsCount($db); ?></p>
            </div>
        </div>

        <!-- Tabelle mit den Verträgen -->
        <div class="table-container">
            <table class="table">
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
</body>
</html>
