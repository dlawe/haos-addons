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
        }
        .header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }
        .header input {
            flex: 1;
            min-width: 200px;
        }
        .header .btn {
            white-space: nowrap;
        }
        .card {
            margin: 10px;
            height: 150px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: scale(1.05);
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.2);
        }
        .card-title {
            font-size: 1rem;
            font-weight: bold;
        }
        .card-text {
            font-size: 1.5rem;
        }
        .table-container {
            margin-top: 20px;
            overflow-x: auto; /* Scrollbare Tabelle */
        }
        .table {
            font-size: 0.9rem;
            border-collapse: collapse;
            min-width: 800px; /* Mindestbreite */
        }
        .table th {
            position: sticky;
            top: 0;
            background-color: #fff;
            z-index: 2;
        }
        .table th, .table td {
            text-align: center;
            padding: 10px;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center my-4">Vertragsübersicht</h1>

        <!-- Header: Suchleiste und Button -->
        <div class="header">
            <form class="d-flex flex-grow-1" method="GET" action="">
                <input type="text" class="form-control" name="search" placeholder="Nach Name oder Anbieter suchen..." value="<?= htmlspecialchars($search ?? ''); ?>">
            </form>
            <a href="add_contract.php" class="btn btn-primary px-4 py-2">Neuen Vertrag hinzufügen</a>
        </div>

        <!-- Übersicht der Statistiken -->
        <div class="row row-cols-1 row-cols-md-4 gx-3 gy-3">
            <div class="col">
                <a href="index.php?filter=active" class="text-decoration-none">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <h5 class="card-title text-center">Aktive Verträge</h5>
                            <p class="card-text text-center"><?= getActiveContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?filter=longterm" class="text-decoration-none">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5 class="card-title text-center">Langzeitverträge</h5>
                            <p class="card-text text-center"><?= getLongTermContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?filter=monthly" class="text-decoration-none">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5 class="card-title text-center">Monatsverträge</h5>
                            <p class="card-text text-center"><?= getMonthlyContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col">
                <a href="index.php?filter=expiring" class="text-decoration-none">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5 class="card-title text-center">Ablaufende Verträge</h5>
                            <p class="card-text text-center"><?= getExpiringContractsCount($db); ?></p>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Tabelle mit den Verträgen -->
        <div class="table-container">
            <h2>Verträge anzeigen</h2>
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Anbieter</th>
                        <th>Kosten</th>
                        <th>Startdatum</th>
                        <th>Enddatum</th>
                        <th>Kündigungsfrist</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $contracts->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']); ?></td>
                            <td><?= htmlspecialchars($row['name']); ?></td>
                            <td><?= htmlspecialchars($row['provider']); ?></td>
                            <td><?= number_format($row['cost'], 2, ',', '.'); ?> €</td>
                            <td><?= htmlspecialchars($row['start_date']); ?></td>
                            <td><?= htmlspecialchars($row['end_date']); ?></td>
                            <td><?= htmlspecialchars($row['cancellation_date']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
