<?php
// Verbindung zur SQLite-Datenbank herstellen
$db = new SQLite3('/data/contracts.db');

// Kategorien als assoziatives Array (ID => ['name' => ..., 'color' => ...])
$categories = [
    1  => ['name' => 'Strom', 'color' => '#007bff'],
    2  => ['name' => 'Gas', 'color' => '#28a745'],
    3  => ['name' => 'Internet', 'color' => '#dc3545'],
    4  => ['name' => 'Mobilfunk', 'color' => '#ffc107'],
    5  => ['name' => 'Versicherung', 'color' => '#6f42c1'],
    6  => ['name' => 'Streaming', 'color' => '#20c997'],
    7  => ['name' => 'Fitnessstudio', 'color' => '#fd7e14'],
    8  => ['name' => 'Zeitschriften', 'color' => '#6610f2'],
    9  => ['name' => 'Miete', 'color' => '#6c757d'],
    10 => ['name' => 'Sonstiges', 'color' => '#17a2b8'],
    11 => ['name' => 'Wartungsverträge', 'color' => '#e83e8c'],
    12 => ['name' => 'Cloud-Dienste', 'color' => '#343a40'],
    13 => ['name' => 'Software-Abonnements', 'color' => '#ffc0cb'],
    14 => ['name' => 'Mitgliedschaften', 'color' => '#ff7f50'],
    15 => ['name' => 'Autoversicherung', 'color' => '#8a2be2'],
    16 => ['name' => 'Rechtsschutz', 'color' => '#ff1493'],
    17 => ['name' => 'Hausrat', 'color' => '#00ced1'],
    18 => ['name' => 'Reiseversicherungen', 'color' => '#ff69b4'],
    19 => ['name' => 'Bausparen', 'color' => '#2e8b57'],
    20 => ['name' => 'Kreditverträge', 'color' => '#ff8c00']
];

// Überprüfen, ob das Formular zum Hinzufügen eines Vertrags abgesendet wurde
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sammeln und Validieren der Eingaben
    $name = trim($_POST['name']);
    $provider = trim($_POST['provider']);
    $cost = floatval($_POST['cost']);
    $start_date = $_POST['start_date'] ? $_POST['start_date'] : null;
    $end_date = $_POST['end_date'] ? $_POST['end_date'] : null;
    $contract_holder = trim($_POST['contract_holder']);
    $cancellation_period = isset($_POST['cancellation_period']) ? intval($_POST['cancellation_period']) : null;
    $duration = isset($_POST['duration']) ? intval($_POST['duration']) : null;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;

    // Validierung der Pflichtfelder
    if (empty($name)) {
        $messages[] = ['type' => 'danger', 'text' => 'Der Name ist erforderlich.'];
    }
    if (empty($provider)) {
        $messages[] = ['type' => 'danger', 'text' => 'Der Anbieter ist erforderlich.'];
    }
    if ($cost <= 0) {
        $messages[] = ['type' => 'danger', 'text' => 'Die Kosten müssen größer als 0 sein.'];
    }
    if ($category_id < 1 || $category_id > 20) {
        $messages[] = ['type' => 'danger', 'text' => 'Ungültige Kategorie ausgewählt.'];
    }

    // Verzeichnisse definieren
    $icon_dir = '/data/icons/';
    $pdf_dir = '/data/pdfs/';

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
            $icon_path = 'icons/' . $icon_name; // Relativer Pfad für die Datenbank
            $full_icon_path = $icon_dir . $icon_name; // Absoluter Pfad für die Speicherung

            if (!is_dir($icon_dir)) {
                if (!mkdir($icon_dir, 0755, true)) {
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
            $pdf_path = 'pdfs/' . $pdf_name; // Relativer Pfad für die Datenbank
            $full_pdf_path = $pdf_dir . $pdf_name; // Absoluter Pfad für die Speicherung

            if (!is_dir($pdf_dir)) {
                if (!mkdir($pdf_dir, 0755, true)) {
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
        // Daten in die Datenbank einfügen
        $stmt = $db->prepare("INSERT INTO contracts (
            name, provider, cost, start_date, end_date, contract_holder, 
            canceled, auto_renew, duration, cancellation_period, 
            category_id, icon_path, pdf_path
        ) VALUES (
            :name, :provider, :cost, :start_date, :end_date, :contract_holder, 
            0, 1, :duration, :cancellation_period, 
            :category_id, :icon_path, :pdf_path
        )");

        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':provider', $provider, SQLITE3_TEXT);
        $stmt->bindValue(':cost', $cost, SQLITE3_FLOAT);
        $stmt->bindValue(':start_date', $start_date, SQLITE3_TEXT);
        $stmt->bindValue(':end_date', $end_date, SQLITE3_TEXT);
        $stmt->bindValue(':contract_holder', $contract_holder, SQLITE3_TEXT);
        $stmt->bindValue(':duration', $duration, SQLITE3_INTEGER);
        $stmt->bindValue(':cancellation_period', $cancellation_period, SQLITE3_INTEGER);
        $stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
        $stmt->bindValue(':icon_path', $icon_path, SQLITE3_TEXT);
        $stmt->bindValue(':pdf_path', $pdf_path, SQLITE3_TEXT);

        $result = $stmt->execute();

        if ($result) {
            $messages[] = ['type' => 'success', 'text' => 'Vertrag erfolgreich hinzugefügt!'];
        } else {
            $messages[] = ['type' => 'danger', 'text' => 'Fehler beim Hinzufügen des Vertrags.'];
        }
    } else {
        foreach ($errors as $error) {
            $messages[] = ['type' => 'danger', 'text' => $error];
        }
    }
}

// Funktion zur Anpassung der Pfade für Ingress
function getIngressPath($path) {
    $base_path = $_SERVER['HTTP_X_INGRESS_PATH'] ?? '';
    return htmlspecialchars($base_path . '/' . ltrim($path, '/'));
}

// Funktionen für die Statistiken
function getContractsCount($db, $condition = '1=1') {
    return $db->querySingle("SELECT COUNT(*) FROM contracts WHERE $condition");
}

function getContracts($db, $condition = '1=1', $search = '') {
    $query = "SELECT * FROM contracts WHERE $condition";
    if (!empty($search)) {
        $searchEscaped = SQLite3::escapeString($search);
        $query .= " AND (name LIKE '%$searchEscaped%' OR provider LIKE '%$searchEscaped%')";
    }
    return $db->query($query);
}

// Verträge aus der DB holen (alle Verträge ohne spezifischen Filter)
$contracts = getContracts($db);

// Statistiken ermitteln
$totalContracts = getContractsCount($db);
$activeCount = getContractsCount($db, "canceled = 0 AND (end_date IS NULL OR end_date > date('now'))");
$canceledCount = getContractsCount($db, "canceled = 1");

// Summierte Kosten aller aktiven Verträge
$sumCostsQuery = $db->querySingle("
    SELECT SUM(cost) 
    FROM contracts 
    WHERE canceled = 0 
      AND (end_date IS NULL OR end_date > date('now'))
");
$totalCosts = $sumCostsQuery ? round($sumCostsQuery, 2) : 0.0;

// Kosten im Monat und im Jahr berechnen
$totalMonthlyCosts = $totalCosts;
$totalYearlyCosts = round($totalMonthlyCosts * 12, 2);

// Kosten nach Kategorie
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

// Vorbereitung der Daten für das Diagramm (Kosten pro Kategorie)
$categoryLabels = [];
$categoryCosts = [];
$categoryColors = [];

foreach ($categories as $catId => $catInfo) {
    if (isset($costsPerCategory[$catId])) {
        $categoryLabels[] = $catInfo['name'];
        $categoryCosts[] = $costsPerCategory[$catId]['cost'];
        $categoryColors[] = $catInfo['color'];
    }
}

$categoryLabelsJson = json_encode($categoryLabels, JSON_UNESCAPED_UNICODE);
$categoryCostsJson  = json_encode($categoryCosts, JSON_UNESCAPED_UNICODE);
$categoryColorsJson = json_encode($categoryColors, JSON_UNESCAPED_UNICODE);

// Korrekte Zuordnung von Kategorie-IDs zu Namen für JavaScript (falls benötigt)
$categoryNames = [];
foreach ($categories as $catId => $catInfo) {
    $categoryNames[$catId] = $catInfo['name'];
}

$categoryNamesJson = json_encode($categoryNames, JSON_UNESCAPED_UNICODE);
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
            padding: 15px; /* Angepasstes Padding */
            text-align: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 9px rgba(0,0,0,0.15);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 1.2rem; /* Angepasste Schriftgröße */
            color: #007bff;
        }
        .stat-card p {
            margin: 5px 0 0;
            font-size: 0.9rem; /* Angepasste Schriftgröße */
            color: #555;
        }

        /* Anpassung der Vertragskarten */
        .contract-card {
            background-color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 15px; /* Reduziertes Padding */
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
            font-size: 1.1rem; /* Verkleinerte Schriftgröße */
            font-weight: 600;
        }
        .contract-card p {
            margin: 5px 0;
            font-size: 0.9rem; /* Verkleinerte Schriftgröße */
            color: #555;
        }
        .contract-card img.icon {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            object-fit: contain;
            border-radius: 50%;
            background-color: #f0f0f0;
            padding: 5px;
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
        .modal-pdf {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
        }
        .pdf-container {
            width: 100%;
            height: 100%;
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
            .chart-container {
                height: 250px; /* Anpassung für kleinere Bildschirme */
            }
        }

        /* Reduzierte vertikale Abstände im Modal */
        .contract-details .mb-2 {
            margin-bottom: 0.5rem; /* Ändere von mb-3 (1rem) zu mb-2 (0.5rem) */
        }

        /* Optional: Weitere Feinabstimmungen */
        .contract-details h6 {
            margin-bottom: 0.2rem;
        }
        .contract-details p {
            margin-bottom: 0.5rem;
        }

        /* Sicherstellen, dass die Modal-Body-Rows und -Columns gleich hoch sind */
        .modal-body .row {
            display: flex;
            align-items: stretch; /* Stellt sicher, dass beide Spalten die gleiche Höhe haben */
        }
        .contract-details, .pdf-container {
            flex: 1;
        }

        /* Anpassung der Diagramm-Container */
        .chart-container {
            width: 100%;
            max-width: 600px; /* Reduzierte maximale Breite */
            margin: 0 auto 20px; /* Zentrierung und Abstand unten */
            height: 300px; /* Reduzierte Höhe */
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
                        <button class="nav-link btn btn-link" data-bs-toggle="modal" data-bs-target="#addContractModal">+ Vertrag hinzufügen</button>
                    </li>
                    <!-- Einzelner Button für Kostenstatistiken -->
                    <li class="nav-item">
                        <button class="nav-link btn btn-link" id="btnOpenCostsStats">
                            <i class="fas fa-chart-bar"></i> Kosten Statistiken
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Erfolgsmeldungen und Fehlermeldungen anzeigen -->
    <div class="container mt-3">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-<?= htmlspecialchars($message['type']); ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Hauptcontainer -->
    <div class="container my-4">
        <!-- Statistiksektion oben -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Statistiken</h5>
                        <div class="search-bar">
                            <input type="text" id="searchInput" class="form-control" placeholder="Verträge suchen..." onkeyup="filterContracts()">
                        </div>
                        <div class="row g-3">
                            <!-- Gesamtanzahl Verträge -->
                            <div class="col-md-2 col-sm-4">
                                <div class="stat-card">
                                    <h3><?= htmlspecialchars($totalContracts) ?></h3>
                                    <p>Gesamt-Verträge</p>
                                </div>
                            </div>
                            <!-- Anzahl aktive Verträge -->
                            <div class="col-md-2 col-sm-4">
                                <div class="stat-card">
                                    <h3><?= htmlspecialchars($activeCount) ?></h3>
                                    <p>Aktive Verträge</p>
                                </div>
                            </div>
                            <!-- Anzahl gekündigte Verträge -->
                            <div class="col-md-2 col-sm-4">
                                <div class="stat-card">
                                    <h3><?= htmlspecialchars($canceledCount) ?></h3>
                                    <p>Gekündigte Verträge</p>
                                </div>
                            </div>
                            <!-- Gesamtkosten (aktiv) -->
                            <div class="col-md-2 col-sm-4">
                                <div class="stat-card">
                                    <h3><?= number_format($totalCosts, 2, ',', '.') ?> €</h3>
                                    <p>Gesamtkosten (aktiv)</p>
                                </div>
                            </div>
                            <!-- Kosten im Monat -->
                            <div class="col-md-2 col-sm-4">
                                <div class="stat-card">
                                    <h3><?= number_format($totalMonthlyCosts, 2, ',', '.') ?> €</h3>
                                    <p>Kosten im Monat</p>
                                </div>
                            </div>
                            <!-- Kosten im Jahr -->
                            <div class="col-md-2 col-sm-4">
                                <div class="stat-card">
                                    <h3><?= number_format($totalYearlyCosts, 2, ',', '.') ?> €</h3>
                                    <p>Kosten im Jahr</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vertragsübersicht unten -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Vertragsübersicht</h5>
                        <div class="row row-cols-1 row-cols-md-3 g-4" id="contractsContainer">
                            <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
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
                                        style="border-left-color: <?= htmlspecialchars($categoryColor); ?>;">
                                        <?php if (!empty($row['icon_path'])): ?>
                                            <img src="<?= getIngressPath($row['icon_path']); ?>" alt="Icon" class="icon">
                                        <?php endif; ?>
                                        <h5><?= htmlspecialchars($row['name']); ?></h5>
                                        <p><strong>Anbieter:</strong> <?= htmlspecialchars($row['provider']); ?></p>
                                        <p><strong>Kosten:</strong> <?= number_format($row['cost'], 2, ',', '.'); ?> €</p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
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
                        <!-- Details und Buttons -->
                        <div class="col-md-6 d-flex flex-column contract-details">
                            <div class="mb-2">
                                <h6><i class="fas fa-building"></i> Anbieter:</h6>
                                <p id="modalProvider"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-user"></i> Vertragsnehmer:</h6>
                                <p id="modalHolder"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-euro-sign"></i> Kosten:</h6>
                                <p id="modalCost"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-calendar-alt"></i> Start:</h6>
                                <p id="modalStart"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-calendar-alt"></i> Ende:</h6>
                                <p id="modalEnd"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-clock"></i> Laufzeit (Monate):</h6>
                                <p id="modalDuration"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-hourglass-start"></i> Kündigungsfrist (Monate):</h6>
                                <p id="modalCancellation"></p>
                            </div>
                            <div class="mb-2">
                                <h6><i class="fas fa-tags"></i> Kategorie:</h6>
                                <p id="modalCategory"></p>
                            </div>
                            <!-- Buttons nach links setzen -->
                            <div class="mt-auto">
                                <a href="#" id="downloadPdf" class="btn btn-danger me-2" target="_blank">
                                    <i class="fas fa-download"></i> PDF herunterladen
                                </a>
                                <a href="#" id="openPdf" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> PDF öffnen
                                </a>
                            </div>
                        </div>
                        <!-- PDF -->
                        <div class="col-md-6 d-flex flex-column">
                            <h6><i class="fas fa-file-pdf"></i> Vertragsdokument:</h6>
                            <div class="pdf-container flex-grow-1 mt-2">
                                <iframe id="modalPdf" src="" class="modal-pdf"></iframe>
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

    <!-- Bootstrap Modal für das Hinzufügen eines neuen Vertrags -->
    <div class="modal fade" id="addContractModal" tabindex="-1" aria-labelledby="addContractModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addContractModalLabel">Neuen Vertrag hinzufügen</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <form action="index.php" method="post" enctype="multipart/form-data" class="row g-3">
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
                                <option value="">-- Bitte wählen --</option>
                                <?php foreach ($categories as $id => $cat): ?>
                                    <option value="<?= htmlspecialchars($id); ?>"><?= htmlspecialchars($cat['name']); ?></option>
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
                        <!-- CSRF-Token hinzufügen (Optional) -->
                        <!-- <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>"> -->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal für die Diagramme -->
    <div class="modal fade" id="chartsModal" tabindex="-1" aria-labelledby="chartsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="chartsModalLabel" class="modal-title">
                        <i class="fas fa-chart-bar info-icon"></i> Kosten Statistiken
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <!-- Diagramm-Container -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container mb-3">
                                <canvas id="monthlyCostsChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container mb-3">
                                <canvas id="yearlyCostsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="categoryCostsChart"></canvas>
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
        const categories = <?= $categoryNamesJson; ?>;

        // Chart.js Diagramme erstellen
        let monthlyCostsChart, yearlyCostsChart, categoryCostsChart;

        document.addEventListener('DOMContentLoaded', function() {
            // Event Listener für den Kosten Statistiken Button
            document.getElementById('btnOpenCostsStats').addEventListener('click', function() {
                // Öffne das Modal
                const chartsModal = new bootstrap.Modal(document.getElementById('chartsModal'));
                chartsModal.show();

                // Erstelle die Diagramme beim Öffnen des Modals, falls sie noch nicht erstellt wurden
                if (!monthlyCostsChart && !yearlyCostsChart && !categoryCostsChart) {
                    createMonthlyCostsChart();
                    createYearlyCostsChart();
                    createCategoryCostsChart();
                }
            });
        });

        function createMonthlyCostsChart() {
            const ctx = document.getElementById('monthlyCostsChart').getContext('2d');
            monthlyCostsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Monatliche Kosten'],
                    datasets: [{
                        label: 'Kosten im Monat (€)',
                        data: [<?= $totalMonthlyCosts ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Kosten im Monat'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        function createYearlyCostsChart() {
            const ctx = document.getElementById('yearlyCostsChart').getContext('2d');
            yearlyCostsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jährliche Kosten'],
                    datasets: [{
                        label: 'Kosten im Jahr (€)',
                        data: [<?= $totalYearlyCosts ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Kosten im Jahr'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 10
                                }
                            }
                        }
                    }
                }
            });
        }

        function createCategoryCostsChart() {
            const ctx = document.getElementById('categoryCostsChart').getContext('2d');
            categoryCostsChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= $categoryLabelsJson; ?>,
                    datasets: [{
                        label: 'Kosten pro Kategorie (€)',
                        data: <?= $categoryCostsJson; ?>,
                        backgroundColor: <?= $categoryColorsJson; ?>,
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Kosten pro Kategorie'
                        },
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value.toFixed(2)} €`;
                                }
                            }
                        }
                    }
                }
            });
        }

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
                    document.getElementById('downloadPdf').classList.remove('disabled');
                    document.getElementById('openPdf').classList.remove('disabled');
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

        // Suchfunktion für Verträge
        function filterContracts() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const contracts = document.querySelectorAll('.contract-card-item');

            contracts.forEach(function(contract) {
                const card = contract.querySelector('.contract-card');
                const name = card.querySelector('h5').textContent.toLowerCase();
                const provider = card.querySelector('p:nth-child(2)').textContent.toLowerCase();

                if (name.includes(filter) || provider.includes(filter)) {
                    contract.style.display = '';
                } else {
                    contract.style.display = 'none';
                }
            });
        }

        // Optional: Automatisches Schließen des Add-Vertrag-Modals nach erfolgreichem Hinzufügen
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Schließe das Add-Vertrag-Modals nach 2 Sekunden
                setTimeout(function() {
                    var addContractModal = bootstrap.Modal.getInstance(document.getElementById('addContractModal'));
                    if (addContractModal) {
                        addContractModal.hide();
                    }
                }, 2000);
            });
        <?php endif; ?>
    </script>
</body>
</html>
