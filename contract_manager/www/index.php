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

// Verträge abrufen
$activeContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE canceled = 0 AND (end_date IS NULL OR end_date > date('now'))
");

$longTermContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE duration >= 12
");

$monthlyContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE duration = 1
");

$expiringContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE canceled = 0 
    AND end_date BETWEEN date('now') AND date('now', '+30 days') 
    AND cancellation_date < date('now', '+30 days')
");
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
        }
        .card {
            margin: 10px;
            height: 150px;
        }
        .card h5 {
            font-size: 1.2rem;
        }
        .card .card-text {
            font-size: 2.5rem;
        }
        .table-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Vertragsübersicht</h1>

        <!-- Button zum Hinzufügen eines neuen Vertrags -->
        <div class="text-end mb-3">
            <a href="add_contract.php" class="btn btn-primary">Neuen Vertrag hinzufügen</a>
        </div>

        <!-- Übersicht der Statistiken -->
        <div class="row">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Aktive Verträge</h5>
                        <p class="card-text text-center"><?= getActiveContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Langzeitverträge</h5>
                        <p class="card-text text-center"><?= getLongTermContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Monatsverträge</h5>
                        <p class="card-text text-center"><?= getMonthlyContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Ablaufende Verträge</h5>
                        <p class="card-text text-center"><?= getExpiringContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabelle: Ablaufende Verträge -->
        <div class="table-container">
            <h2>Ablaufende Verträge</h2>
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
                    <?php while ($row = $expiringContracts->fetchArray(SQLITE3_ASSOC)): ?>
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
