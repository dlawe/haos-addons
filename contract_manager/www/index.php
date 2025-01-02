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
        $query .= " AND (name LIKE '%$search%' OR provider LIKE '%$search%')";
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
            border-left: 8px solid transparent;
            cursor: pointer; /* Hand-Cursor, wenn man über die Karte fährt */
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

        /* Overlay (Vollbild) */
        .overlay {
            display: none; /* Anfangs unsichtbar */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 9999;
        }
        .overlay-content {
            background-color: #fff;
            width: 90%;
            height: 90%;
            margin: 3% auto;
            border-radius: 8px;
            overflow: hidden; /* damit die Ecken schön abgerundet sind */
            display: flex;    /* zweispaltiges Layout: links Details, rechts PDF */
            position: relative;
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
        }
        .overlay-pdf iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ccc;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
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

    <!-- Vertragskarten -->
    <div class="card-container">
        <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
                // JSON für das JavaScript aufbereiten
                // Wir escapen Sonderzeichen sicherheitshalber
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
                <!-- Wenn du willst, kannst du noch Start- oder Enddatum direkt hier anzeigen -->
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
            <button class="close-btn" onclick="closeOverlay()">Schließen</button>
            <iframe id="contractPdf" src=""></iframe>
        </div>
    </div>
</div>

<script>
function openOverlay(contractString) {
    // JSON-String in Objekt umwandeln
    const contract = JSON.parse(contractString);

    // Overlay anzeigen
    document.getElementById('contractOverlay').style.display = 'block';

    // Linke Spalte (Details) zusammenbauen
    let detailsHtml = `
        <h2>${escapeHtml(contract.name)}</h2>
        <p><strong>Anbieter:</strong> ${escapeHtml(contract.provider)}</p>
        <p><strong>Vertragsnehmer:</strong> ${escapeHtml(contract.contract_holder)}</p>
        <p><strong>Kosten:</strong> ${parseFloat(contract.cost).toFixed(2)} €</p>
        <p><strong>Start:</strong> ${formatDate(contract.start_date)}</p>
        <p><strong>Ende:</strong> ${formatDate(contract.end_date)}</p>
        <p><strong>Laufzeit (Monate):</strong> ${escapeHtml(contract.duration)}</p>
        <p><strong>Kündigungsfrist (Monate):</strong> ${escapeHtml(contract.cancellation_period)}</p>
    `;

    // Wenn du die Kategorie ausgeben willst (clientseitig):
    // Du hast sie als ID in contract.category_id. 
    // Entweder du gibst sie hier als ID aus oder wandelst sie clientseitig um.
    // Für die Demo hier einfach "Kategorie-ID"
    detailsHtml += `<p><strong>Kategorie-ID:</strong> ${escapeHtml(contract.category_id)}</p>`;

    document.getElementById('contractDetails').innerHTML = detailsHtml;

    // PDF im iframe laden
    const iframe = document.getElementById('contractPdf');
    if (contract.pdf_path && contract.pdf_path !== '') {
        // Ingress-Pfad anpassen, falls nötig, oder direkt:
        iframe.src = contract.pdf_path;
    } else {
        // Wenn kein PDF vorhanden ist, src leeren
        iframe.src = '';
    }
}

function closeOverlay() {
    document.getElementById('contractOverlay').style.display = 'none';
    // Beim Schließen iframe zurücksetzen, um z.B. das PDF neu zu laden, falls gewünscht
    document.getElementById('contractPdf').src = '';
}

// Hilfsfunktionen für sicheres Escapen und Datumsformatierung (ähnlich wie in PHP)
function escapeHtml(text) {
    if (typeof text !== 'string') return text;
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Einfaches Datumsformat (yyyy-mm-dd -> dd.mm.yyyy)
function formatDate(dateString) {
    if (!dateString) return '';
    const parts = dateString.split('-');
    if (parts.length !== 3) return dateString;
    return parts.reverse().join('.');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
