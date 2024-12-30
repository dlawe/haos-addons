<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuen Vertrag hinzufügen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f4f8;
            font-family: Arial, sans-serif;
        }
        h1 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .form-label {
            font-size: 0.85rem;
            color: #555;
        }
        .form-control {
            font-size: 0.85rem;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .btn {
            font-size: 0.85rem;
            padding: 8px 12px;
            border-radius: 4px;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 10px;
            }
            h1 {
                font-size: 1.1rem;
            }
            .btn {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
        }
    </style>
    <script>
        // Automatische Berechnung der Laufzeit
        function calculateDuration() {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const durationField = document.getElementById('duration');

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                const months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                durationField.value = months > 0 ? months : 0; // Laufzeit in Monaten
            } else {
                durationField.value = ''; // Keine Laufzeit berechnen
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1 class="text-center">Neuen Vertrag hinzufügen</h1>
        <div class="form-container">
            <!-- Erfolg- oder Fehlermeldung -->
            <?php if (!empty($message)): ?>
                <div class="alert <?= strpos($message, 'erfolgreich') !== false ? 'alert-success' : 'alert-danger'; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <!-- Formular für neuen Vertrag -->
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="provider" class="form-label">Anbieter</label>
                    <input type="text" class="form-control" id="provider" name="provider" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Name des Vertrags</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="cost" class="form-label">Kosten (€)</label>
                    <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                </div>
                <div class="mb-3">
                    <label for="start_date" class="form-label">Startdatum</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" required onchange="calculateDuration()">
                </div>
                <div class="mb-3">
                    <label for="end_date" class="form-label">Enddatum</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" required onchange="calculateDuration()">
                </div>
                <div class="mb-3">
                    <label for="duration" class="form-label">Laufzeit (in Monaten)</label>
                    <input type="number" class="form-control" id="duration" name="duration" readonly>
                </div>
                <div class="mb-3">
                    <label for="cancellation_period" class="form-label">Kündigungsfrist (in Monaten)</label>
                    <input type="number" class="form-control" id="cancellation_period" name="cancellation_period" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Kategorie</label>
                    <input type="text" class="form-control" id="category" name="category" required>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-success">Vertrag speichern</button>
                    <a href="index.php" class="btn btn-secondary">Zurück</a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
