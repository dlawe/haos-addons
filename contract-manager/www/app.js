<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vertragsmanager Dashboard</title>
  <link rel="stylesheet" href="style.css"> <!-- Dein CSS-Stylesheet -->
</head>
<body>
  <div class="container">
    <!-- Header -->
    <header class="header">
      <h1 class="title">Vertragsmanager</h1>
      <nav class="nav">
        <button class="btn nav-btn">Übersicht</button>
        <button class="btn nav-btn">Laufende Verträge</button>
        <button class="btn nav-btn">Ablaufende Verträge</button>
        <button class="btn nav-btn" id="addContractBtn">Vertrag Hinzufügen</button>
      </nav>
    </header>

    <!-- Dashboard -->
    <div class="dashboard">
      <!-- Schnellübersicht -->
      <section class="quick-overview">
        <h2>Übersicht</h2>
        <div class="overview-cards">
          <div class="card">
            <h3>Aktuelle Verträge</h3>
            <p>3 Verträge aktiv</p>
          </div>
          <div class="card">
            <h3>Ablaufende Verträge</h3>
            <p>2 Verträge laufen bald aus</p>
          </div>
          <div class="card">
            <h3>Verträge fällig in 30 Tagen</h3>
            <p>1 Vertrag bald fällig</p>
          </div>
        </div>
      </section>

      <!-- Laufende Verträge -->
      <section class="contract-section">
        <h2>Laufende Verträge</h2>
        <div class="contract-list" id="activeContracts">
          <!-- Vertragskarten werden hier dynamisch geladen -->
        </div>
      </section>

      <!-- Ablaufende Verträge -->
      <section class="contract-section">
        <h2>Ablaufende Verträge</h2>
        <div class="contract-list" id="expiringContracts">
          <!-- Vertragskarten werden hier dynamisch geladen -->
        </div>
      </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
      <p>&copy; 2024 Vertragsmanager. Alle Rechte vorbehalten.</p>
    </footer>

    <!-- Modal für Vertrag Details -->
    <div id="contractModal" class="modal">
      <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Vertragsdetails</h3>
        <p id="contractDetails">Hier erscheinen die Details des Vertrags...</p>
      </div>
    </div>

    <!-- Vertrag hinzufügen Formular -->
    <div id="addContractForm" class="modal">
      <div class="modal-content">
        <span class="close-btn" onclick="closeAddContractForm()">&times;</span>
        <h3>Vertrag Hinzufügen</h3>
        <form id="contractForm">
          <label for="contractName">Vertragsname:</label>
          <input type="text" id="contractName" name="contractName" required>

          <label for="contractStart">Startdatum:</label>
          <input type="date" id="contractStart" name="contractStart" required>

          <label for="contractEnd">Enddatum:</label>
          <input type="date" id="contractEnd" name="contractEnd" required>

          <label for="contractType">Vertragstyp:</label>
          <select id="contractType" name="contractType">
            <option value="mobil">Mobilfunk</option>
            <option value="internet">Internet</option>
            <option value="versicherung">Versicherung</option>
          </select>

          <button type="submit" class="btn">Vertrag hinzufügen</button>
        </form>
      </div>
    </div>
  </div>

  <script src="app.js"></script> <!-- Dein JavaScript -->
</body>
</html>
