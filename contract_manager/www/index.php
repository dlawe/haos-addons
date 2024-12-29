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

// Aktive Verträge abrufen
$activeContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE canceled = 0 AND (end_date IS NULL OR end_date > date('now'))
");

// Langzeitverträge abrufen
$longTermContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE duration >= 12
");

// Monatsverträge abrufen
$monthlyContracts = $db->query("
    SELECT * 
    FROM contracts 
    WHERE duration = 1
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
        }
        .table-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Vertragsübersicht</h1>

        <!-- Übersicht der Statistiken -->
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Aktive Verträge</h5>
                        <p class="card-text display-4"><?= getActiveContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Langzeitverträge (12+ Monate)</h5>
                        <p class="card-text display-4"><?= getLongTermContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Monatsverträge (1 Monat)</h5>
                        <p class="card-text display-4"><?= getMonthlyContractsCount($db); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabelle: Aktive Verträge -->
        <div class="table-container">
            <h2>Aktive Verträge</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Anbieter</th>
                        <th>Kosten</th>
                        <th>Startdatum</th>
                        <th>Enddatum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $activeContracts->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['provider']); ?></td>
                            <td><?= number_format($row['cost'], 2, ',', '.'); ?> €</td>
                            <td><?= htmlspecialchars($row['start_date']); ?></td>
                            <td><?= htmlspecialchars($row['end_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabelle: Langzeitverträge -->
        <div class="table-container">
            <h2>Langzeitverträge (12+ Monate)</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Anbieter</th>
                        <th>Kosten</th>
                        <th>Startdatum</th>
                        <th>Enddatum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $longTermContracts->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['provider']); ?></td>
                            <td><?= number_format($row['cost'], 2, ',', '.'); ?> €</td>
                            <td><?= htmlspecialchars($row['start_date']); ?></td>
                            <td><?= htmlspecialchars($row['end_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Tabelle: Monatsverträge -->
        <div class="table-container">
            <h2>Monatsverträge (1 Monat)</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Anbieter</th>
                        <th>Kosten</th>
                        <th>Startdatum</th>
                        <th>Enddatum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $monthlyContracts->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['provider']); ?></td>
                            <td><?= number_format($row['cost'], 2, ',', '.'); ?> €</td>
                            <td><?= htmlspecialchars($row['start_date']); ?></td>
                            <td><?= htmlspecialchars($row['end_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
