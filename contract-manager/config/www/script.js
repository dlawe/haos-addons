function showTab(tabName) {
  // Alle Tab-Inhalte ausblenden
  let tabs = document.querySelectorAll('.tab-content');
  tabs.forEach(tab => {
    tab.style.display = 'none';
  });

  // Den ausgewählten Tab anzeigen
  let selectedTab = document.getElementById(tabName);
  selectedTab.style.display = 'block';
}

// Beim Laden der Seite den "Übersicht"-Tab anzeigen
window.onload = function() {
  showTab('overview');
}
