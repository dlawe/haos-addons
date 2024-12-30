<?php
// Verbindung zur Datenbank herstellen
$db = new PDO('sqlite:/data/contracts.db');

// Filteroptionen anwenden
$filter = $_GET['filter'] ?? 'all';
$whereClause = '';

switch ($filter) {
    case 'active':
        $whereClause = "WHERE end_date IS NULL OR date(end_date) > date('now')";
        break;
    case 'expiring':
        $whereClause = "WHERE date(end_date) BETWEEN date('now') AND date('now', '+30 days')";
        break;
    case 'canceled':
        $whereClause = "WHERE canceled = 1";
        break;
    default:
        $whereClause = ""; // Zeige alle Verträge
}

// Verträge aus der Datenbank abrufen
$sql = "SELECT * FROM contracts $whereClause";
$stmt = $db->query($sql);
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
            color: #333;
            margin: 20px 0;
        }
        .filter {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter a {
            text-decoration: none;
            color: white;
            background-color: #007BFF;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .filter a.active {
            background-color: #0056b3;
        }
        .container {
            width: 90%;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .contract-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            flex: 1 1 calc(33.333% - 20px);
            max-width: calc(33.333% - 20px);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .contract-card img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .contract-card h2 {
            font-size: 1.2em;
            margin: 10px 0;
            color: #444;
        }
        .contract-card p {
            margin: 5px 0;
            color: #666;
        }
        .contract-card a {
            margin-top: 10px;
            display: inline-block;
            color: #007BFF;
            text-decoration: none;
        }
        .contract-card a:hover {
            text-decoration: underline;
        }
        .actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        .actions a {
            text-decoration: none;
            color: white;
            background-color: #28a745;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .actions a.delete {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <h1>Vertragsübersicht</h1>
    
    <!-- Filteroptionen -->
    <div class="filter">
        <a href="?filter=all" class="<?= $filter === 'all' ? 'active' : '' ?>">Alle</a>
        <a href="?filter=active" class="<?= $filter === 'active' ? 'active' : '' ?>">Aktive</a>
        <a href="?filter=expiring" class="<?= $filter === 'expiring' ? 'active' : '' ?>">Laufende</a>
        <a href="?filter=canceled" class="<?= $filter === 'canceled' ? 'active' : '' ?>">Gekündigte</a>
    </div>
    
    <!-- Vertragskacheln -->
    <div class="container">
        <?php if (count($contracts) > 0): ?>
            <?php foreach ($contracts as $contract): ?>
                <div class="contract-card">
                    <!-- Icon anzeigen -->
                    <?php if (!empty($contract['icon_path'])): ?>
                        <img src="<?= htmlspecialchars($contract['icon_path']) ?>" alt="Icon">
                    <?php else: ?>
                        <img src="/var/www/html/default-icon.png" alt="Standard-Icon">
                    <?php endif; ?>

                    <!-- Vertragsdetails -->
                    <h2><?= htmlspecialchars($contract['name']) ?></h2>
                    <p><strong>Anbieter:</strong> <?= htmlspecialchars($contract['provider']) ?></p>
                    <p><strong>Kosten:</strong> <?= number_format($contract['cost'], 2) ?> €</p>
                    <p><strong>Startdatum:</strong> <?= htmlspecialchars($contract['start_date']) ?></p>
                    <p><strong>Enddatum:</strong> <?= htmlspecialchars($contract['end_date']) ?></p>

                    <!-- PDF-Link anzeigen -->
                    <?php if (!empty($contract['pdf_path'])): ?>
                        <a href="<?= htmlspecialchars($contract['pdf_path']) ?>" target="_blank">PDF anzeigen</a>
                    <?php endif; ?>

                    <!-- Aktionen -->
                    <div class="actions">
                        <a href="edit_contract.php?id=<?= $contract['id'] ?>">Bearbeiten</a>
                        <a href="delete_contract.php?id=<?= $contract['id'] ?>" class="delete">Löschen</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-contracts">Keine Verträge vorhanden.</p>
        <?php endif; ?>
    </div>
</body>
</html>
