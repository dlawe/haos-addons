<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Funktionen für die Statistiken mit Singular/Plural-Text
function formatCountText($count, $singular, $plural) {
    return $count . ' ' . ($count === 1 ? $singular : $plural);
}

// Statistiken abrufen
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

// Monatliche Kosten berechnen
function getMonthlyCost($db) {
    return $db->querySingle("SELECT SUM(cost) FROM contracts WHERE canceled = 0");
}

// Statistiken abrufen
$activeContractsCount = getActiveContractsCount($db);
$longTermContractsCount = getLongTermContractsCount($db);
$monthlyContractsCount = getMonthlyContractsCount($db);
$expiringContractsCount = getExpiringContractsCount($db);
$monthlyCost = getMonthlyCost($db);
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
            flex-wrap: nowrap; /* Keine Zeilenumbruch, auch auf Mobilgeräten */
            gap: 10px; /* Abstand zwischen den Kacheln */
            overflow-x: auto; /* Horizontal scrollen bei zu kleinen Bildschirmen */
        }
        .card {
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: white;
            flex: 0 0 48%; /* Zwei Kacheln pro Zeile */
            max-width: 48%; /* Breite begrenzen */
        }
        .card .section {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .section-icon {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .stats {
            font-size: 0.9rem;
            font-weight: bold;
            margin-right: 5px;
        }
        .description {
            font-size: 0.8rem;
            color: #555;
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
        .blue {
            background-color: #007bff;
        }
        @media (max-width: 576px) {
            .card {
                flex: 0 0 45%; /* Auf Mobilgeräten etwas schmalere Kacheln */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Vertragsübersicht</h1>
        <div class="card-container">
            <!-- Erste Kachel -->
            <div class="card">
                <div class="section">
                    <div class="section-icon blue"></div>
                    <span class="stats"><?= formatCountText($activeContractsCount, 'Aktiver Vertrag', 'Aktive Verträge'); ?></span>
                </div>
                <div class="section">
                    <div class="section-icon green"></div>
                    <span class="stats"><?= formatCountText($longTermContractsCount, 'Langzeitvertrag', 'Langzeitverträge'); ?></span>
                </div>
                <div class="section">
                    <div class="section-icon orange"></div>
                    <span class="stats"><?= formatCountText($monthlyContractsCount, 'Monatsvertrag', 'Monatsverträge'); ?></span>
                </div>
                <div class="section">
                    <div class="section-icon red"></div>
                    <span class="stats"><?= formatCountText($expiringContractsCount, 'Ablaufender Vertrag', 'Ablaufende Verträge'); ?></span>
                </div>
            </div>
            <!-- Zweite Kachel -->
            <div class="card">
                <div class="section">
                    <div class="section-icon blue"></div>
                    <span class="stats">
                        <?= number_format($monthlyCost ?? 0, 2, ',', '.') . ' €'; ?>
                    </span>
                    <span class="description">Gesamtkosten pro Monat</span>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
