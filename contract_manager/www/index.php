<?php
// Verbindung zur Datenbank herstellen
$db = new PDO('sqlite:/data/contracts.db');

// Filteroptionen und Suchbegriff verarbeiten
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';
$whereClauses = [];
$params = [];

// Filter anwenden
switch ($filter) {
    case 'active':
        $whereClauses[] = "(end_date IS NULL OR date(end_date) > date('now'))";
        break;
    case 'expiring':
        $whereClauses[] = "date(end_date) BETWEEN date('now') AND date('now', '+30 days')";
        break;
    case 'canceled':
        $whereClauses[] = "canceled = 1";
        break;
}

// Suchfunktion anwenden
if (!empty($search)) {
    $whereClauses[] = "(name LIKE :search OR provider LIKE :search)";
    $params[':search'] = "%$search%";
}

// WHERE-Klausel zusammenbauen
$whereClause = '';
if (count($whereClauses) > 0) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Verträge aus der Datenbank abrufen
$sql = "SELECT * FROM contracts $whereClause";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertragsübersicht</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        h1 {
            text-align: center;
            margin: 20px 0;
            color: #333;
        }
        .search-filter {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-filter input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            flex: 1;
            max-width: 300px;
        }
        .search-filter button, .search-filter a {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
        }
        .search-filter a.active {
            background-color: #0056b3;
        }
        .container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
        }
        .contract-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
            position: relative;
            width: calc(25% - 20px);
            box-sizing: border-box;
        }
        .contract-card img.contract-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .contract-card h3 {
            margin: 0 0 10px;
            font-size: 1.2em;
            color: #333;
        }
        .contract-card p {
            margin: 5px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <h1>Vertragsübersicht</h1>

    <!-- Such- und Filteroptionen -->
    <div class="search-filter">
        <form method="GET" style="flex: 1; display: flex; gap: 10px;">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Verträge durchsuchen...">
            <button type="submit">Suchen</button>
        </form>
        <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">Alle</a>
        <a href="?filter=active" class="<?= $filter === 'active' ? 'active' : '' ?>">Aktive</a>
        <a href="?filter=expiring" class="<?= $filter === 'expiring' ? 'active' : '' ?>">Bald Auslaufende</a>
        <a href="?filter=canceled" class="<?= $filter === 'canceled' ? 'active' : '' ?>">Gekündigte</a>
    </div>

    <!-- Vertragskarten -->
    <div class="container">
        <?php if (count($contracts) > 0): ?>
            <?php foreach ($contracts as $contract): ?>
                <div class="contract-card">
                    <!-- Icon oben rechts -->
                    <?php if (!empty($contract['icon_path'])): ?>
                        <img class="contract-icon" src="<?= htmlspecialchars($contract['icon_path']) ?>" alt="Icon">
                    <?php endif; ?>

                    <!-- Vertragsdetails -->
                    <h3><?= htmlspecialchars($contract['name']) ?></h3>
                    <p><strong>Anbieter:</strong> <?= htmlspecialchars($contract['provider']) ?></p>
                    <p><strong>Kosten:</strong> <?= number_format($contract['cost'], 2) ?> €</p>
                    <p><strong>Startdatum:</strong> <?= htmlspecialchars($contract['start_date']) ?></p>
                    <p><strong>Enddatum:</strong> <?= htmlspecialchars($contract['end_date']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center; color: #555;">Keine Verträge gefunden.</p>
        <?php endif; ?>
    </div>
</body>
</html>
