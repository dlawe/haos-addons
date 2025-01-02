<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Kategorien als assoziatives Array (ID => Kategoriename)
$categories = [
    1  => 'Strom',
    2  => 'Gas',
    3  => 'Internet',
    4  => 'Mobilfunk',
    5  => 'Versicherung',
    6  => 'Streaming',
    7  => 'Fitnessstudio',
    8  => 'Zeitschriften',
    9  => 'Miete',
    10 => 'Sonstiges',
    11 => 'Wartungsverträge',
    12 => 'Cloud-Dienste',
    13 => 'Software-Abonnements',
    14 => 'Mitgliedschaften',
    15 => 'Autoversicherung',
    16 => 'Rechtsschutz',
    17 => 'Hausrat',
    18 => 'Reiseversicherungen',
    19 => 'Bausparen',
    20 => 'Kreditverträge'
];

// Funktionen für die Statistiken
function getContractsCount($db, $condition = '1=1') {
    return $db->querySingle("SELECT COUNT(*) FROM contracts WHERE $condition");
}

function getContracts($db, $condition = '1=1', $search = '') {
    $query = "SELECT * FROM contracts WHERE $condition";
    if (!empty($search)) {
        // Hier besser auf Prepared Statements setzen, um SQL-Injection vorzubeugen
        $searchEscaped = SQLite3::escapeString($search);
        $query .= " AND (name LIKE '%$searchEscaped%' OR provider LIKE '%$searchEscaped%')";
    }
    return $db->query($query);
}

// Funktion zur Bestimmung der Klasse basierend auf dem Vertragsstatus
function getCardClass($contract) {
    $now = date('Y-m-d');

    if ($contract['canceled'] == 0 
        && isset($contract['end_date']) 
        && $contract['end_date'] <= date('Y-m-d', strtotime('+30 days'))) {
        return 'border-red'; // Rot für ablaufende Verträge
    }

    if ($contract['canceled'] == 0 
        && (!isset($contract['end_date']) || $contract['end_date'] > $now)) {
        return 'border-green'; // Grün für aktive Verträge
    }

    if ($contract['canceled'] == 1) {
        return 'border-gray'; // Grau für deaktivierte/kündigte Verträge
    }

    return ''; // Standard: Keine spezifische Farbe
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
    $catName = isset($categories[$catId]) ? $categories[$catId] : 'Unbekannt';
    $costsPerCategory[$catName] = (float)$row['total'];
}

// Daraus ein JSON bauen für JavaScript (Diagramm)
$categoryLabels = json_encode(array_keys($costsPerCategory));
$categoryCosts  = json_encode(array_values($costsPerCategory));
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
        }
        .card-container {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            padding: 0 20px;
        }
        .contract-card {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            width: 300px;
            padding: 15px;
            position: relative;
            margin-bottom: 20px;
            border-left: 8px solid transparent;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .contract-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        .contract-card h5 {
            margin-bottom: 10px;
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

        /* Farbliche Kennzeichnungen */
        .border-red {
            border-left-color: #dc3545;
        }
        .border-green {
            border-left-color: #28a745;
        }
        .border-gray {
            border-left-color: #6c757d;
        }

        /* Overlay (Vollbild) - (aus dem vorherigen Beispiel) */
        .overlay {
            display: none; 
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

        /* Statistik-Bereich */
        .statistics-section {
            background-color: #ffffff;
            margin: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .statistics-section h2 {
            margin-top: 0;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
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
            font-size: 2rem;
        }
        .stat-card p {
            margin: 5px 0 0;
            font-size: 1rem;
            color: #555;
        }
        /* Responsive Charts */
        .chart-container {
            width: 100%;
            max-width: 800px;
            margin: 30px auto;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Vertragsmanager</h1>
    <a href="add_contract.php">+ Vertrag hinzufügen</a>
</div>

<div class="container">
    <h1 class="text-center">Vertragsübersicht</h1>

    <!-- Statistik-Bereich -->
    <div class="statistics-section">
        <h2>Statistiken</h2>

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
        </div>

        <!-- Kleines Balken- oder Tortendiagramm (Chart.js) -->
        <div class="chart-container">
            <canvas id="costChart"></canvas>
        </div>
    </div>

    <!-- Vertragskarten -->
    <div class="card-container">
        <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
                // JSON für das JavaScript aufbereiten
                $contractJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
            ?>
            <div 
                class="contract-card <?= getCardClass($row); ?>"
                onclick="openOverlay('<?= $contractJson ?>')"
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
// Chart.js Diagramm erstellen
window.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('costChart').getContext('2d');
    const catLabels = <?= $categoryLabels ?>; // ["Strom","Gas","Internet",...]
    const catCosts  = <?= $categoryCosts ?>;  // [120,50,20,...]

    // Du kannst ein Balkendiagramm (bar) oder Tortendiagramm (pie/doughnut) wählen
    new Chart(ctx, {
        type: 'bar',  // oder 'pie', 'doughnut' usw.
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Kosten je Kategorie (€)',
                data: catCosts,
                backgroundColor: [
                    '#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1',
                    '#20c997', '#fd7e14', '#6610f2', '#6c757d', '#17a2b8',
                    // ... zufällig oder fest definierte Farben
                ],
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false, // damit es auch bei weniger Platz gut aussieht
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

// Overlay-Funktionen (aus vorherigen Beispielen)
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
        <p><strong>Kategorie-ID:</strong> ${escapeHtml(contract.category_id)}</p>
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
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
