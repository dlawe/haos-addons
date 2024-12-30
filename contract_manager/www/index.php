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

// Monatliche Kosten berechnen
function getMonthlyCost($db) {
    return $db->querySingle("SELECT SUM(cost) FROM contracts WHERE canceled = 0");
}
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
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }
        .card {
            border-radius: 16px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            background-color: white;
        }
        .card .section {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .section-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
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
        .stats {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .description {
            font-size: 0.9rem;
            color: #555;
        }
        @media (min-width: 768px) {
            .row-cols-2 > * {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Vertragsübersicht</h1>
        <div class="row row-cols-1 row-cols-md-2">
            <!-- Erste Kachel -->
            <div class="col">
                <div class="card">
                    <div class="section">
                        <div class="section-icon blue"></div>
                        <div>
                            <p class="stats"><?= getActiveContractsCount($db); ?></p>
                            <p class="description">Aktuell aktive Verträge</p>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-icon green"></div>
                        <div>
                            <p class="stats"><?= getLongTermContractsCount($db); ?></p>
                            <p class="description">Langzeitverträge</p>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-icon orange"></div>
                        <div>
                            <p class="stats"><?= getMonthlyContractsCount($db); ?></p>
                            <p class="description">Monatsverträge</p>
                        </div>
                    </div>
                    <div class="section">
                        <div class="section-icon red"></div>
                        <div>
                            <p class="stats"><?= getExpiringContractsCount($db); ?></p>
                            <p class="description">Ablaufende Verträge</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Zweite Kachel -->
            <div class="col">
                <div class="card">
                    <div class="section">
                        <div class="section-icon blue"></div>
                        <div>
                            <p class="stats">
                                <?php
                                $monthlyCost = getMonthlyCost($db);
                                echo $monthlyCost !== null ? number_format($monthlyCost, 2, ',', '.') . ' €' : '0,00 €';
                                ?>
                            </p>
                            <p class="description">Gesamtkosten pro Monat</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
