<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Kategorien als assoziatives Array (ID => ['name' => ..., 'color' => ...])
$categories = [
    1  => ['name' => 'Strom', 'color' => '#007bff'],          // Blau
    2  => ['name' => 'Gas', 'color' => '#28a745'],            // Grün
    3  => ['name' => 'Internet', 'color' => '#dc3545'],       // Rot
    4  => ['name' => 'Mobilfunk', 'color' => '#ffc107'],      // Gelb
    5  => ['name' => 'Versicherung', 'color' => '#6f42c1'],   // Lila
    6  => ['name' => 'Streaming', 'color' => '#20c997'],      // Türkis
    7  => ['name' => 'Fitnessstudio', 'color' => '#fd7e14'],  // Orange
    8  => ['name' => 'Zeitschriften', 'color' => '#6610f2'],  // Dunkelblau
    9  => ['name' => 'Miete', 'color' => '#6c757d'],          // Grau
    10 => ['name' => 'Sonstiges', 'color' => '#17a2b8'],      // Blaugrün
    11 => ['name' => 'Wartungsverträge', 'color' => '#e83e8c'], // Pink
    12 => ['name' => 'Cloud-Dienste', 'color' => '#343a40'],  // Dunkelgrau
    13 => ['name' => 'Software-Abonnements', 'color' => '#ffc0cb'], // Hellrosa
    14 => ['name' => 'Mitgliedschaften', 'color' => '#ff7f50'],    // Korallenrot
    15 => ['name' => 'Autoversicherung', 'color' => '#8a2be2'],    // Blauviolett
    16 => ['name' => 'Rechtsschutz', 'color' => '#ff1493'],        // Tiefrosa
    17 => ['name' => 'Hausrat', 'color' => '#00ced1'],            // Dunkeltürkis
    18 => ['name' => 'Reiseversicherungen', 'color' => '#ff69b4'],// Hot Pink
    19 => ['name' => 'Bausparen', 'color' => '#2e8b57'],           // Seetanggrün
    20 => ['name' => 'Kreditverträge', 'color' => '#ff8c00']      // Dunkelorange
];

// Funktionen für die Statistiken
function getContractsCount($db, $condition = '1=1') {
    return $db->querySingle("SELECT COUNT(*) FROM contracts WHERE $condition");
}

function getContracts($db, $condition = '1=1', $search = '') {
    $query = "SELECT * FROM contracts WHERE $condition";
    if (!empty($search)) {
        // Sicherheit: Verwende SQLite3::escapeString, um SQL-Injection zu verhindern
        $searchEscaped = SQLite3::escapeString($search);
        $query .= " AND (name LIKE '%$searchEscaped%' OR provider LIKE '%$searchEscaped%')";
    }
    return $db->query($query);
}

// Funktion zur Anpassung der Pfade für Ingress
function getIngressPath($path) {
    $base_path = $_SERVER['HTTP_X_INGRESS_PATH'] ?? '';
    return htmlspecialchars($base_path . '/' . ltrim($path, '/'));
}

// Funktion zum Formatieren des Datums
function formatDate($date) {
    if (!$date) return '';
    return date('d.m.Y', strtotime($date));
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

// Verträge aus der DB holen
$contracts = getContracts($db, $condition, $search);

// -------------------------------------------------------------
// Beispielhafte Statistiken ermitteln
// -------------------------------------------------------------

// 1. Gesamtanzahl aller Verträge
$totalContracts = getContractsCount($db);

// 2. Anzahl laufender (nicht gekündigter) Verträge
$activeCount = getContractsCount($db, "canceled = 0 AND (end_date IS NULL OR end_date > date('now'))");

// 3. Anzahl gekündigter Verträge
$canceledCount = getContractsCount($db, "canceled = 1");

// 4. Summierte Kosten aller aktiven Verträge (z. B. Grundkosten)
$sumCostsQuery = $db->querySingle("
    SELECT SUM(cost) 
    FROM contracts 
    WHERE canceled = 0 
      AND (end_date IS NULL OR end_date > date('now'))
");
$totalCosts = $sumCostsQuery ? round($sumCostsQuery, 2) : 0.0;

// 6. Kosten im Monat und im Jahr berechnen
$totalMonthlyCosts = $totalCosts;
$totalYearlyCosts = round($totalMonthlyCosts * 12, 2);

// 5. Kosten nach Kategorie, um ein Diagramm zu erstellen (Beispiel)
$costsPerCategoryQuery = $db->query("
    SELECT category_id, SUM(cost) AS total 
    FROM contracts 
    WHERE canceled = 0
    GROUP BY category_id
");

$costsPerCategory = [];
while ($row = $costsPerCategoryQuery->fetchArray(SQLITE3_ASSOC)) {
    $catId = $row['category_id'];
    if (isset($categories[$catId])) {
        $catName = $categories[$catId]['name'];
        $cost = (float)$row['total'];
        $costsPerCategory[$catId] = [
            'name' => $catName,
            'cost' => $cost,
            'color' => $categories[$catId]['color']
        ];
    }
}

// Vorbereitung der Daten für das Diagramm in der Reihenfolge der Kategorien
$chartLabels = [];
$chartCosts = [];
$chartColors = [];

foreach ($categories as $catId => $catInfo) {
    if (isset($costsPerCategory[$catId])) {
        $chartLabels[] = $catInfo['name'];
        $chartCosts[] = $costsPerCategory[$catId]['cost'];
        $chartColors[] = $catInfo['color'];
    }
}

$categoryLabels = json_encode($chartLabels, JSON_UNESCAPED_UNICODE);
$categoryCosts  = json_encode($chartCosts, JSON_UNESCAPED_UNICODE);
$categoryChartColors = json_encode($chartColors, JSON_UNESCAPED_UNICODE);

// Korrekte Zuordnung von Kategorie-IDs zu Namen für JavaScript
$categoryNames = [];
foreach ($categories as $catId => $catInfo) {
    $categoryNames[$catId] = $catInfo['name'];
}

$categoryNameJson = json_encode($categoryNames, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertragsübersicht</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js von CDN laden -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 56px;
            background-color: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 15px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .header h1 {
            font-size: 1.2rem;
            margin: 0;
        }
        .header a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            padding-top: 70px;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Statistik-Bereich links */
        .statistics-section {
            background-color: #ffffff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1 1 250px;
            min-width: 250px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .statistics-section h2 {
            margin-top: 0;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        /* Inneres Layout der Statistik-Sektion */
        .stat-content {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        /* Numerische Statistiken */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 15px;
            flex: 1;
        }
        .stat-card {
            background: #f7f7f7;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 1.5rem; /* Angepasste Schriftgröße */
            color: #007bff;
        }
        .stat-card p {
            margin: 5px 0 0;
            font-size: 1rem;
            color: #555;
        }

        /* Diagramm */
        .chart-container {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }

        /* Vertragskarten rechts */
        .contracts-section {
            flex: 3 1 700px;
            min-width: 300px;
        }
        .contracts-section h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .card-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .contract-card {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            width: 300px;
            padding: 15px;
            position: relative;
            margin-bottom: 20px;
            border-left: 8px solid transparent; /* Farbe wird inline gesetzt */
            cursor: pointer; /* Hand-Cursor, wenn man über die Karte fährt */
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .contract-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        .contract-card h5 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        .contract-card p {
            margin: 5px 0;
            font-size: 1rem;
            color: #555;
        }
        .contract-card img.icon {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            object-fit: contain;
            max-width: 100%;
            max-height: 100%;
        }

        /* Overlay (Vollbild) */
        .overlay {
            display: none; /* Anfangs unsichtbar */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .overlay.show {
            display: block;
            opacity: 1;
            pointer-events: auto; 
            animation: fadeIn 0.3s forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .overlay-content {
            background-color: #fff;
            width: 90%;
            height: 90%;
            margin: 3% auto;
            border-radius: 8px;
            overflow: hidden; 
            display: flex;    
            position: relative;
            animation: slideUp 0.4s ease forwards;
            transform: translateY(100px);
            opacity: 0;
        }
        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .overlay-details {
            width: 40%;
            padding: 20px;
            overflow-y: auto;
            box-sizing: border-box;
        }
        .overlay-details h2 {
            margin-top: 0;
            font-size: 1.5rem;
        }
        .overlay-details p {
            margin-bottom: 8px;
            font-size: 1rem;
            color: #333;
        }
        .overlay-pdf {
            width: 60%;
            background-color: #f0f0f0;
            position: relative;
            box-shadow: -4px 0 12px rgba(0,0,0,0.05);
        }
        .overlay-pdf iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            background: #dc3545;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            color: #fff;
            font-weight: bold;
            font-size: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background 0.2s;
        }
        .close-btn:hover {
            background: #c82333;
        }

        /* Responsive Anpassungen */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            .statistics-section, .contracts-section {
                min-width: 100%;
            }
            .overlay-content {
                flex-direction: column;
            }
            .overlay-details, .overlay-pdf {
                width: 100%;
                height: 50%;
            }
            .overlay-pdf {
                height: 50%;
            }
            /* Statistik-Innenlayout anpassen */
            .stat-content {
                flex-direction: column;
            }
            .chart-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Vertragsmanager</h1>
    <a href="add_contract.php">+ Vertrag hinzufügen</a>
</div>

<div class="container">
    <!-- Statistik-Bereich links -->
    <div class="statistics-section">
        <h2>Statistiken</h2>

        <!-- Inneres Layout: Numerische Statistiken und Diagramm nebeneinander -->
        <div class="stat-content">
            <!-- Numerische Statistiken -->
            <div class="stat-grid">
                <!-- Gesamtanzahl Verträge -->
                <div class="stat-card">
                    <h3><?= $totalContracts ?></h3>
                    <p>Gesamt-Verträge</p>
                </div>

                <!-- Anzahl aktive Verträge -->
                <div class="stat-card">
                    <h3><?= $activeCount ?></h3>
                    <p>Aktive Verträge</p>
                </div>

                <!-- Anzahl gekündigte Verträge -->
                <div class="stat-card">
                    <h3><?= $canceledCount ?></h3>
                    <p>Gekündigte Verträge</p>
                </div>

                <!-- Summierte Kosten aller aktiven Verträge -->
                <div class="stat-card">
                    <h3><?= number_format($totalCosts, 2, ',', '.') ?> €</h3>
                    <p>Gesamtkosten (aktiv)</p>
                </div>

                <!-- Neue Statistik-Karte: Kosten im Monat -->
                <div class="stat-card">
                    <h3><?= number_format($totalMonthlyCosts, 2, ',', '.') ?> €</h3>
                    <p>Kosten im Monat</p>
                </div>

                <!-- Neue Statistik-Karte: Kosten im Jahr -->
                <div class="stat-card">
                    <h3><?= number_format($totalYearlyCosts, 2, ',', '.') ?> €</h3>
                    <p>Kosten im Jahr</p>
                </div>
            </div>

            <!-- Diagramm rechts neben den numerischen Statistiken -->
            <div class="chart-container">
                <canvas id="costChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Vertragskarten rechts -->
    <div class="contracts-section">
        <h2>Vertragsübersicht</h2>

        <!-- Vertragskarten -->
        <div class="card-container">
            <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
                <?php
                    // JSON für das JavaScript aufbereiten
                    $contractJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                    // Farbe basierend auf der Kategorie
                    $categoryId = $row['category_id'];
                    $categoryColor = isset($categories[$categoryId]['color']) ? $categories[$categoryId]['color'] : '#000000'; // Fallback-Farbe
                ?>
                <div 
                    class="contract-card"
                    onclick="openOverlay('<?= $contractJson ?>')"
                    style="border-left-color: <?= $categoryColor ?>;" 
                >
                    <?php if (!empty($row['icon_path'])): ?>
                        <img src="<?= getIngressPath($row['icon_path']); ?>" alt="Icon" class="icon">
                    <?php endif; ?>

                    <!-- Kurze Infos direkt auf der Karte -->
                    <h5><?= htmlspecialchars($row['name']); ?></h5>
                    <p>Anbieter: <?= htmlspecialchars($row['provider']); ?></p>
                    <p>Kosten: <?= number_format($row['cost'], 2, ',', '.'); ?> €</p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Overlay für detaillierte Anzeige -->
<div class="overlay" id="contractOverlay">
    <div class="overlay-content">
        <!-- Linke Spalte: Details -->
        <div class="overlay-details" id="contractDetails">
            <!-- Wird per JS gefüllt -->
        </div>

        <!-- Rechte Spalte: PDF-Ansicht -->
        <div class="overlay-pdf">
            <button class="close-btn" onclick="closeOverlay()">✕</button>
            <iframe id="contractPdf" src=""></iframe>
        </div>
    </div>
</div>

<script>
    // Kategorie-IDs zu Namen aus PHP übertragen
    const categories = <?= $categoryNameJson; ?>;

    // Chart.js Diagramm erstellen
    window.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('costChart').getContext('2d');
        const catLabels = <?= $categoryLabels ?>; // ["Strom","Gas","Internet",...]
        const catCosts  = <?= $categoryCosts ?>;  // [120,50,20,...]
        const chartColors = <?= $categoryChartColors ?>; // ["#007bff", "#28a745", ...]

        new Chart(ctx, {
            type: 'pie',  // Du kannst 'bar', 'pie', 'doughnut' usw. wählen
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Kosten je Kategorie (€)',
                    data: catCosts,
                    backgroundColor: chartColors, // Verwende die gleichen Farben wie in den Vertragskarten
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false, // Damit es auch bei weniger Platz gut aussieht
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        enabled: true,
                    }
                }
            }
        });
    });

    // Overlay-Funktionen
    function openOverlay(contractString) {
        const contract = JSON.parse(contractString);
        const overlay = document.getElementById('contractOverlay');

        let detailsHtml = `
            <h2>${escapeHtml(contract.name)}</h2>
            <p><strong>Anbieter:</strong> ${escapeHtml(contract.provider)}</p>
            <p><strong>Vertragsnehmer:</strong> ${escapeHtml(contract.contract_holder)}</p>
            <p><strong>Kosten:</strong> ${parseFloat(contract.cost).toFixed(2)} €</p>
            <p><strong>Start:</strong> ${formatDate(contract.start_date)}</p>
            <p><strong>Ende:</strong> ${formatDate(contract.end_date)}</p>
            <p><strong>Laufzeit (Monate):</strong> ${escapeHtml(contract.duration)}</p>
            <p><strong>Kündigungsfrist (Monate):</strong> ${escapeHtml(contract.cancellation_period)}</p>
            <p><strong>Kategorie:</strong> ${getCategoryName(contract.category_id)}</p>
        `;
        document.getElementById('contractDetails').innerHTML = detailsHtml;

        // PDF im iframe laden
        const iframe = document.getElementById('contractPdf');
        if (contract.pdf_path && contract.pdf_path !== '') {
            iframe.src = contract.pdf_path;
        } else {
            iframe.src = '';
        }
        // Overlay anzeigen
        overlay.classList.add('show');
    }

    function closeOverlay() {
        const overlay = document.getElementById('contractOverlay');
        const iframe = document.getElementById('contractPdf');
        overlay.classList.remove('show');
        iframe.src = '';
    }

    // Hilfsfunktionen
    function escapeHtml(text) {
        if (typeof text !== 'string') return text;
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(dateString) {
        if (!dateString) return '';
        const parts = dateString.split('-');
        if (parts.length !== 3) return dateString;
        return `${parts[2]}.${parts[1]}.${parts[0]}`;
    }

    // Funktion zur Umwandlung der Kategorie-ID in den Namen (clientseitig)
    function getCategoryName(catId) {
        return categories[catId] || 'Unbekannt';
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
