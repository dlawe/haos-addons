<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vertragsübersicht</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      background-color: #f9f9f9;
    }
    h1 {
      text-align: center;
      color: #333;
    }
    .dashboard {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 30px;
    }
    .card {
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      text-align: center;
      color: white;
    }
    .card h2 {
      margin: 0;
      font-size: 1.5em;
    }
    .card p {
      margin: 10px 0 0;
      font-size: 1.2em;
    }
    .green { background-color: #4CAF50; }
    .orange { background-color: #FF9800; }
    .red { background-color: #F44336; }
    .blue { background-color: #2196F3; }
  </style>
</head>
<body>
  <h1>Vertragsübersicht</h1>
  <div class="dashboard">
    <?php
      // Verbindung zur SQLite-Datenbank herstellen
      $db = new SQLite3('/data/contracts.db');

      // Alle Verträge zählen
      $totalContracts = $db->querySingle('SELECT COUNT(*) FROM contracts');

      // Aktive Verträge zählen
      $activeContracts = $db->querySingle('SELECT COUNT(*) FROM contracts WHERE canceled = 0 AND end_date > DATE("now")');

      // Ablaufende Verträge (innerhalb der nächsten 30 Tage)
      $expiringContracts = $db->querySingle('SELECT COUNT(*) FROM contracts WHERE end_date BETWEEN DATE("now") AND DATE("now", "+30 days")');

      // Verträge mit monatlicher Laufzeit
      $monthlyContracts = $db->querySingle('SELECT COUNT(*) FROM contracts WHERE duration = 1');

      // Karten erstellen
      $cards = [
        ['title' => 'Alle Verträge', 'count' => $totalContracts, 'class' => 'green'],
        ['title' => 'Aktive Verträge', 'count' => $activeContracts, 'class' => 'blue'],
        ['title' => 'Ablaufende Verträge', 'count' => $expiringContracts, 'class' => 'orange'],
        ['title' => 'Monatliche Verträge', 'count' => $monthlyContracts, 'class' => 'red']
      ];

      // Karten in HTML ausgeben
      foreach ($cards as $card) {
        echo "<div class='card {$card['class']}'>";
        echo "<h2>{$card['title']}</h2>";
        echo "<p>{$card['count']}</p>";
        echo "</div>";
      }

      // Verbindung schließen
      $db->close();
    ?>
  </div>
</body>
</html>
