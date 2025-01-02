<?php
// Verbindung zur Datenbank herstellen mit PDO
try {
    $db = new PDO('sqlite:/data/contracts.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Funktion, um eine Tabelle zu erstellen, falls sie nicht existiert
function ensureTableExists($db, $table, $columns) {
    $db->exec("CREATE TABLE IF NOT EXISTS $table ($columns)");
}

// Funktion, um eine Spalte zu prüfen und ggf. hinzuzufügen
function ensureColumnExists($db, $table, $column, $definition) {
    $result = $db->query("PRAGMA table_info($table)");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($column, $columns)) {
        $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    }
}

// Sicherstellen, dass die Tabelle "contracts" existiert
ensureTableExists($db, 'contracts', "
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    provider TEXT NOT NULL,
    cost REAL NOT NULL,
    start_date DATE,
    end_date DATE,
    contract_holder TEXT,
    canceled BOOLEAN DEFAULT 0,
    auto_renew BOOLEAN DEFAULT 1,
    duration INTEGER,
    cancellation_period INTEGER,
    category_id INTEGER,
    icon_path TEXT,
    pdf_path TEXT,
    FOREIGN KEY (category_id) REFERENCES categories(id)
");

// Sicherstellen, dass alle benötigten Spalten existieren
$columns = [
    'canceled' => 'BOOLEAN DEFAULT 0',
    'auto_renew' => 'BOOLEAN DEFAULT 1',
    'duration' => 'INTEGER',
    'cancellation_period' => 'INTEGER',
    'category_id' => 'INTEGER',
    'icon_path' => 'TEXT',
    'pdf_path' => 'TEXT',
    'contract_holder' => 'TEXT'
];
foreach ($columns as $column => $definition) {
    ensureColumnExists($db, 'contracts', $column, $definition);
}

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
    $stmt = $db->prepare("SELECT COUNT(*) FROM contracts WHERE $condition");
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getContracts($db, $condition = '1=1', $search = '') {
    $query = "SELECT * FROM contracts WHERE $condition";
    if (!empty($search)) {
        $query .= " AND (name LIKE :search OR provider LIKE :search)";
    }
    $stmt = $db->prepare($query);
    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Funktion zur Anpassung der Pfade für Ingress
function getIngressPath($path) {
    $base_path = $_SERVER['HTTP_X_INGRESS_PATH'] ?? '';
    return htmlspecialchars($base_path . '/' . ltrim($path, '/'));
}

// Funktion zum Formatieren des Datums
function formatDate($date) {
    if (!$date) return 'Unbekannt';
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
                  AND cancellation_period < 30";
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
$sumCostsQuery = $db->query("SELECT SUM(cost) FROM contracts WHERE canceled = 0 AND (end_date IS NULL OR end_date > date('now'))");
$sumCostsResult = $sumCostsQuery->fetchColumn();
$totalCosts = $sumCostsResult ? round($sumCostsResult, 2) : 0.0;

// 5. Kosten nach Kategorie, um ein Diagramm zu erstellen (Beispiel)
$costsPerCategoryQuery = $db->query("
    SELECT category_id, SUM(cost) AS total 
    FROM contracts 
    WHERE canceled = 0
    GROUP BY category_id
");

$costsPerCategory = [];
while ($row = $costsPerCategoryQuery->fetch(PDO::FETCH_ASSOC)) {
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

// Zusätzlich: Zähle die Anzahl der gefilterten Verträge für die Anzeige
$filteredContractsCount = count($contracts);

// Verarbeitung des Add-Contract Formulars
$addContractMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_contract'])) {
    // Überprüfen und Sanitisieren der Eingaben
    $name = trim($_POST['name']);
    $provider = trim($_POST['provider']);
    $cost = floatval($_POST['cost']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $contract_holder = trim($_POST['contract_holder']) ?: null;
    $cancellation_period = isset($_POST['cancellation_period']) && is_numeric($_POST['cancellation_period']) ? intval($_POST['cancellation_period']) : null;
    $duration = isset($_POST['duration']) && is_numeric($_POST['duration']) ? intval($_POST['duration']) : null;
    $category_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? intval($_POST['category_id']) : null;

    // Verzeichnisse definieren
    $icon_dir = __DIR__ . '/data/icons/';
    $pdf_dir = __DIR__ . '/data/pdfs/';

    // Standardwerte für Icon und PDF
    $icon_path = null;
    $pdf_path = null;

    $errors = [];

    // Hochladen des Icons
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif'];
        if (!in_array($fileExtension, $allowed_extensions)) {
            $errors[] = "Nur PNG, JPG, JPEG und GIF sind als Icon erlaubt.";
        } else {
            $icon_name = uniqid('icon_', true) . '.' . $fileExtension; // Eindeutiger Name
            $icon_path = 'data/icons/' . $icon_name; // Relativer Pfad für die Datenbank
            $full_icon_path = $icon_dir . $icon_name; // Absoluter Pfad für die Speicherung

            if (!is_dir($icon_dir)) {
                if (!mkdir($icon_dir, 0777, true)) {
                    $errors[] = "Das Icon-Verzeichnis konnte nicht erstellt werden.";
                }
            }
            if (!move_uploaded_file($_FILES['icon']['tmp_name'], $full_icon_path)) {
                $errors[] = "Das Icon konnte nicht hochgeladen werden.";
            }
        }
    } else {
        $errors[] = "Bitte ein gültiges Icon hochladen.";
    }

    // Hochladen des PDFs
    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
        if ($fileExtension !== 'pdf') {
            $errors[] = "Nur PDF-Dateien sind erlaubt.";
        } else {
            $pdf_name = uniqid('pdf_', true) . '.' . $fileExtension; // Eindeutiger Name
            $pdf_path = 'data/pdfs/' . $pdf_name; // Relativer Pfad für die Datenbank
            $full_pdf_path = $pdf_dir . $pdf_name; // Absoluter Pfad für die Speicherung

            if (!is_dir($pdf_dir)) {
                if (!mkdir($pdf_dir, 0777, true)) {
                    $errors[] = "Das PDF-Verzeichnis konnte nicht erstellt werden.";
                }
            }
            if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $full_pdf_path)) {
                $errors[] = "Das PDF konnte nicht hochgeladen werden.";
            }
        }
    } else {
        $errors[] = "Bitte ein gültiges PDF hochladen.";
    }

    // Überprüfen, ob Fehler vorliegen
    if (empty($errors)) {
        try {
            // Daten in die Datenbank einfügen
            $sql = "INSERT INTO contracts (
                name, provider, cost, start_date, end_date, contract_holder, 
                canceled, auto_renew, duration, cancellation_period, 
                category_id, icon_path, pdf_path
            ) VALUES (:name, :provider, :cost, :start_date, :end_date, :contract_holder, 0, 1, :duration, :cancellation_period, :category_id, :icon_path, :pdf_path)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':provider', $provider, PDO::PARAM_STR);
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->bindParam(':contract_holder', $contract_holder, PDO::PARAM_STR);
            $stmt->bindParam(':duration', $duration, PDO::PARAM_INT);
            $stmt->bindParam(':cancellation_period', $cancellation_period, PDO::PARAM_INT);
            $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
            $stmt->bindParam(':icon_path', $icon_path, PDO::PARAM_STR);
            $stmt->bindParam(':pdf_path', $pdf_path, PDO::PARAM_STR);
            $stmt->execute();

            $addContractMessage = "<div class='alert alert-success'>Vertrag erfolgreich hinzugefügt!</div>";
        } catch (PDOException $e) {
            $addContractMessage = "<div class='alert alert-danger'>Fehler beim Hinzufügen des Vertrags: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        // Fehler ausgeben
        $errorMessages = "";
        foreach ($errors as $error) {
            $errorMessages .= "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
        }
        $addContractMessage = $errorMessages;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertragsübersicht</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome für Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js von CDN laden -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.0.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
        }

        /* Anpassung der Navbar */
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
        }
        .navbar-nav .nav-link:hover {
            color: #d1d1d1 !important;
        }

        /* Anpassung der Statistik-Karten */
        .stat-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #007bff;
        }
        .stat-card p {
            margin: 10px 0 0;
            font-size: 1rem;
            color: #555;
        }

        /* Anpassung der Vertragskarten */
        .contract-card {
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            border-left: 8px solid transparent; /* Farbe wird inline gesetzt */
            cursor: pointer; /* Hand-Cursor, wenn man über die Karte fährt */
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .contract-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .contract-card h5 {
            margin-bottom: 10px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .contract-card p {
            margin: 5px 0;
            font-size: 0.95rem;
            color: #555;
        }
        .contract-card img.icon {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            object-fit: contain;
            border-radius: 50%;
            background-color: #f0f0f0;
            padding: 5px;
        }

        /* Anpassung des Diagramms */
        .chart-container {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
            height: 300px;
        }

        /* Anpassung des Modals */
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .modal-title {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-body p {
            font-size: 1rem;
            color: #333;
        }
        .modal-body .info-icon {
            color: #007bff;
            margin-right: 5px;
        }
        .modal-pdf iframe {
            width: 100%;
            height: 400px;
            border: none;
            border-radius: 8px;
        }
        .modal-footer {
            background-color: #f8f9fa;
        }

        /* Suchleiste */
        .search-bar {
            margin-bottom: 20px;
        }

        /* Responsive Anpassungen */
        @media (max-width: 768px) {
            .stat-content {
                flex-direction: column;
            }
            .chart-container {
                max-width: 100%;
                height: 200px;
            }
            .contract-card {
                width: 100%;
            }
            .modal-pdf iframe {
                height: 300px;
            }
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Vertragsmanager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addContractModal">+ Vertrag hinzufügen</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hauptcontainer -->
    <div class="container my-4">
        <?php if (!empty($addContractMessage)): ?>
            <?= $addContractMessage ?>
        <?php endif; ?>
        <div class="row">
            <!-- Statistik-Bereich -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Statistiken</h5>
                        <div class="search-bar">
                            <input type="text" id="searchInput" class="form-control" placeholder="Verträge suchen..." onkeyup="filterContracts()">
                        </div>
                        <div class="row g-3">
                            <!-- Gesamtanzahl Verträge -->
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3><?= htmlspecialchars($totalContracts) ?></h3>
                                    <p>Gesamt-Verträge</p>
                                </div>
                            </div>
                            <!-- Anzahl aktive Verträge -->
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3><?= htmlspecialchars($activeCount) ?></h3>
                                    <p>Aktive Verträge</p>
                                </div>
                            </div>
                            <!-- Anzahl gekündigte Verträge -->
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3><?= htmlspecialchars($canceledCount) ?></h3>
                                    <p>Gekündigte Verträge</p>
                                </div>
                            </div>
                            <!-- Gesamtkosten (aktiv) -->
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3><?= number_format($totalCosts, 2, ',', '.') ?> €</h3>
                                    <p>Gesamtkosten (aktiv)</p>
                                </div>
                            </div>
                            <!-- Kosten im Monat -->
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3><?= number_format($totalMonthlyCosts, 2, ',', '.') ?> €</h3>
                                    <p>Kosten im Monat</p>
                                </div>
                            </div>
                            <!-- Kosten im Jahr -->
                            <div class="col-6">
                                <div class="stat-card">
                                    <h3><?= number_format($totalYearlyCosts, 2, ',', '.') ?> €</h3>
                                    <p>Kosten im Jahr</p>
                                </div>
                            </div>
                        </div>
                        <!-- Diagramm -->
                        <div class="mt-4">
                            <div class="chart-container">
                                <canvas id="costChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vertragskarten-Bereich -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Vertragsübersicht</h5>
                        <?php if ($filteredContractsCount > 0): ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4" id="contractsContainer">
                                <?php foreach ($contracts as $row): ?>
                                    <?php
                                        // JSON für das JavaScript aufbereiten
                                        $contractJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                        // Farbe basierend auf der Kategorie
                                        $categoryId = $row['category_id'];
                                        $categoryColor = isset($categories[$categoryId]['color']) ? $categories[$categoryId]['color'] : '#000000'; // Fallback-Farbe
                                    ?>
                                    <div class="col contract-card-item">
                                        <div 
                                            class="contract-card" 
                                            data-contract='<?= $contractJson ?>' 
                                            style="border-left-color: <?= htmlspecialchars($categoryColor) ?>;">
                                            <?php if (!empty($row['icon_path'])): ?>
                                                <img src="<?= getIngressPath($row['icon_path']); ?>" alt="Icon" class="icon">
                                            <?php endif; ?>
                                            <h5><?= htmlspecialchars($row['name']); ?></h5>
                                            <p><strong>Anbieter:</strong> <?= htmlspecialchars($row['provider']); ?></p>
                                            <p><strong>Kosten:</strong> <?= number_format($row['cost'], 2, ',', '.'); ?> €</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center my-5">
                                <p class="fs-5">Keine Verträge gefunden. <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addContractModal">+ Vertrag hinzufügen</button></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal für das Hinzufügen eines Vertrags -->
    <div class="modal fade" id="addContractModal" tabindex="-1" aria-labelledby="addContractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addContractModalLabel"><i class="fas fa-plus-circle"></i> Neuen Vertrag hinzufügen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php<?= !empty($_SERVER['QUERY_STRING']) ? '?' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>" method="post" enctype="multipart/form-data" class="row g-3">
                        <input type="hidden" name="add_contract" value="1">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Name:</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="provider" class="form-label">Anbieter:</label>
                            <input type="text" id="provider" name="provider" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contract_holder" class="form-label">Vertragsnehmer:</label>
                            <input type="text" id="contract_holder" name="contract_holder" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cost" class="form-label">Kosten:</label>
                            <input type="number" step="0.01" id="cost" name="cost" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Startdatum:</label>
                            <input type="date" id="start_date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">Enddatum:</label>
                            <input type="date" id="end_date" name="end_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="cancellation_period" class="form-label">Kündigungsfrist (Monate):</label>
                            <input type="number" id="cancellation_period" name="cancellation_period" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="duration" class="form-label">Laufzeit (Monate):</label>
                            <input type="number" id="duration" name="duration" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Kategorie:</label>
                            <select id="category_id" name="category_id" class="form-select" required>
                                <?php foreach ($categories as $id => $cat): ?>
                                    <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="icon" class="form-label">Icon hochladen:</label>
                            <input type="file" id="icon" name="icon" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-6">
                            <label for="pdf" class="form-label">PDF hochladen:</label>
                            <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf" required>
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary">Vertrag hinzufügen</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal für Vertragsdetails -->
    <div class="modal fade" id="contractModal" tabindex="-1" aria-labelledby="contractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="contractModalLabel" class="modal-title">
                        <i class="fas fa-file-contract info-icon"></i> Vertragsdetails
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Details -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6><i class="fas fa-building"></i> Anbieter:</h6>
                                <p id="modalProvider"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-user"></i> Vertragsnehmer:</h6>
                                <p id="modalHolder"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-euro-sign"></i> Kosten:</h6>
                                <p id="modalCost"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-calendar-alt"></i> Start:</h6>
                                <p id="modalStart"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-calendar-alt"></i> Ende:</h6>
                                <p id="modalEnd"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-clock"></i> Laufzeit (Monate):</h6>
                                <p id="modalDuration"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-hourglass-start"></i> Kündigungsfrist (Monate):</h6>
                                <p id="modalCancellation"></p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-tags"></i> Kategorie:</h6>
                                <p id="modalCategory"></p>
                            </div>
                        </div>
                        <!-- PDF -->
                        <div class="col-md-6">
                            <h6><i class="fas fa-file-pdf"></i> Vertragsdokument:</h6>
                            <div class="mt-2">
                                <iframe id="modalPdf" src="" class="modal-pdf"></iframe>
                            </div>
                            <div class="mt-3">
                                <a href="#" id="downloadPdf" class="btn btn-danger me-2" target="_blank">
                                    <i class="fas fa-download"></i> PDF herunterladen
                                </a>
                                <a href="#" id="openPdf" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> PDF öffnen
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS und Abhängigkeiten -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Kategorie-IDs zu Namen aus PHP übertragen
        const categories = <?= $categoryNameJson; ?>;

        // Chart.js Diagramm erstellen
        document.addEventListener('DOMContentLoaded', function() {
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
                    responsive: true,
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

        // Funktion zum Öffnen des Modals mit Vertragsdetails
        const contractModal = new bootstrap.Modal(document.getElementById('contractModal'), {
            keyboard: false
        });

        document.querySelectorAll('.contract-card').forEach(card => {
            card.addEventListener('click', function() {
                const contract = JSON.parse(this.getAttribute('data-contract'));

                // Füllen der Modal-Inhalte
                document.getElementById('contractModalLabel').textContent = contract.name;
                document.getElementById('modalProvider').textContent = contract.provider;
                document.getElementById('modalHolder').textContent = contract.contract_holder;
                document.getElementById('modalCost').textContent = parseFloat(contract.cost).toFixed(2) + ' €';
                document.getElementById('modalStart').textContent = formatDate(contract.start_date);
                document.getElementById('modalEnd').textContent = formatDate(contract.end_date);
                document.getElementById('modalDuration').textContent = contract.duration;
                document.getElementById('modalCancellation').textContent = contract.cancellation_period;
                document.getElementById('modalCategory').textContent = categories[contract.category_id] || 'Unbekannt';

                // PDF laden, falls vorhanden
                const modalPdf = document.getElementById('modalPdf');
                if (contract.pdf_path && contract.pdf_path !== '') {
                    modalPdf.src = contract.pdf_path;
                    document.getElementById('downloadPdf').href = contract.pdf_path;
                    document.getElementById('openPdf').href = contract.pdf_path;
                } else {
                    modalPdf.src = 'about:blank';
                    document.getElementById('downloadPdf').href = '#';
                    document.getElementById('openPdf').href = '#';
                    document.getElementById('downloadPdf').classList.add('disabled');
                    document.getElementById('openPdf').classList.add('disabled');
                }

                // Öffnen des Modals
                contractModal.show();
            });
        });

        // Hilfsfunktionen
        function formatDate(dateString) {
            if (!dateString) return 'Unbekannt';
            const parts = dateString.split('-');
            if (parts.length !== 3) return dateString;
            return `${parts[2]}.${parts[1]}.${parts[0]}`;
        }

        // Suchfunktion
        function filterContracts() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const contracts = document.querySelectorAll('.contract-card-item');

            let visibleCount = 0;

            contracts.forEach(function(contract) {
                const card = contract.querySelector('.contract-card');
                const name = card.querySelector('h5').textContent.toLowerCase();
                const provider = card.querySelector('p:nth-child(2)').textContent.toLowerCase();

                if (name.includes(filter) || provider.includes(filter)) {
                    contract.style.display = '';
                    visibleCount++;
                } else {
                    contract.style.display = 'none';
                }
            });

            // Anzeige anpassen, wenn keine Verträge sichtbar sind
            if (visibleCount === 0) {
                document.getElementById('contractsContainer').style.display = 'none';
                document.getElementById('noContractsMessage').style.display = 'block';
            } else {
                document.getElementById('contractsContainer').style.display = 'flex';
                document.getElementById('noContractsMessage').style.display = 'none';
            }
        }
    </script>
</body>
</html>
