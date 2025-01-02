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

// Zusätzlich: Zähle die Anzahl der gefilterten Verträge für die Anzeige
$filteredContractsCount = getContractsCount($db, $condition . (!empty($search) ? " AND (name LIKE '%" . SQLite3::escapeString($search) . "%' OR provider LIKE '%" . SQLite3::escapeString($search) . "%')" : ""));
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

        /* Responsive Anpassungen */
        @media (max-width: 768px) {
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
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Suchleiste zentriert -->
                <form class="d-flex mx-auto my-2 my-lg-0">
                    <input type="text" id="searchInput" class="form-control" placeholder="Verträge suchen..." onkeyup="filterContracts()">
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_contract.php">+ Vertrag hinzufügen</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hauptcontainer -->
    <div class="container my-4">
        <!-- Statistik-Bereich -->
        <div class="row mb-4">
            <div class="col-12">
                <h5>Statistiken</h5>
                <div class="row g-3">
                    <!-- Gesamtanzahl Verträge -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <div class="stat-card">
                            <h3><?= $totalContracts ?></h3>
                            <p>Gesamt-Verträge</p>
                        </div>
                    </div>
                    <!-- Anzahl aktive Verträge -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <div class="stat-card">
                            <h3><?= $activeCount ?></h3>
                            <p>Aktive Verträge</p>
                        </div>
                    </div>
                    <!-- Anzahl gekündigte Verträge -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <div class="stat-card">
                            <h3><?= $canceledCount ?></h3>
                            <p>Gekündigte Verträge</p>
                        </div>
                    </div>
                    <!-- Gesamtkosten (aktiv) -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <div class="stat-card">
                            <h3><?= number_format($totalCosts, 2, ',', '.') ?> €</h3>
                            <p>Gesamtkosten (aktiv)</p>
                        </div>
                    </div>
                    <!-- Kosten im Monat -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <div class="stat-card">
                            <h3><?= number_format($totalMonthlyCosts, 2, ',', '.') ?> €</h3>
                            <p>Kosten im Monat</p>
                        </div>
                    </div>
                    <!-- Kosten im Jahr -->
                    <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                        <div class="stat-card">
                            <h3><?= number_format($totalYearlyCosts, 2, ',', '.') ?> €</h3>
                            <p>Kosten im Jahr</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vertragskarten-Bereich -->
        <div class="row">
            <div class="col-12">
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
                                            style="border-left-color: <?= $categoryColor ?>;">
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
                                <p class="fs-5">Keine Verträge gefunden. <a href="add_contract.php" class="btn btn-primary mt-3">+ Vertrag hinzufügen</a></p>
                            </div>
                        <?php endif; ?>
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
        const categories = <?= json_encode(array_column($categories, 'name'), JSON_UNESCAPED_UNICODE); ?>;

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

        // Suchfunktion
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
    </script>
</body>
</html>
