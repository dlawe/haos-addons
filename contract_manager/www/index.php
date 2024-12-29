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
            background-color: #f8f9fa;
        }
        .card {
            margin: 10px;
            height: 150px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
        }
        .card-title {
            font-size: 1rem;
            font-weight: bold;
        }
        .card-text {
            font-size: 1.5rem;
        }
        .table-container {
            margin-top: 20px;
        }
        .table {
            font-size: 0.9rem;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .row-cols-4 > .col {
            flex: 0 0 25%;
        }
        .icon {
            font-size: 1.5rem;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Vertragsübersicht</h1>

        <!-- Button und Suchleiste -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <form class="search-bar w-50" method="GET" action="">
                <input type="text" class="form-control" name="search" placeholder="Nach Name oder Anbieter suchen..." value="<?= htmlspecialchars($search); ?>">
            </form>
            <a href="add_contract.php" class="btn btn-primary">Neuen Vertrag hinzufügen</a>
        </div>

        <!-- Übersicht der Statistiken -->
        <div class="row row-cols-4 gx-3 gy-3">
            <div class="col">
                <a href="index.php?filter=active" class="text-decoration-none">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title text-center"><i class="icon bi bi-check-circle"></i>Aktive Verträge</h5>
                            <p class="card-text text-center"><?= getActiveContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?filter=longterm" class="text-decoration-none">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title text-center"><i class="icon bi bi-calendar-range"></i>Langzeitverträge</h5>
                            <p class="card-text text-center"><?= getLongTermContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?filter=monthly" class="text-decoration-none">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title text-center"><i class="icon bi bi-calendar2-week"></i>Monatsverträge</h5>
                            <p class="card-text text-center"><?= getMonthlyContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?filter=expiring" class="text-decoration-none">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title text-center"><i class="icon bi bi-alarm"></i>Ablaufende Verträge</h5>
                            <p class="card-text text-center"><?= getExpiringContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Tabelle mit den Verträgen -->
        <div class="table-container">
            <h2>Verträge anzeigen</h2>
            <table class="table table-striped table-hover">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.js"></script>
</body>
</html>
