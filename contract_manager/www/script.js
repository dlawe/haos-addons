// Beispielhafte Vertragsdaten
const contracts = [
    { name: "Vertrag 1", type: "Alle Verträge", cost: 50, expirationDate: "2024-02-01", canceled: false },
    { name: "Vertrag 2", type: "Alle Verträge", cost: 30, expirationDate: "2024-03-15", canceled: false },
    { name: "Vertrag 3", type: "Bald auslaufende Verträge", cost: 20, expirationDate: "2024-01-10", canceled: false },
    { name: "Vertrag 4", type: "Verträge ohne Kündigung", cost: 40, expirationDate: "2024-04-20", canceled: false },
    { name: "Vertrag 5", type: "Verträge ohne Kündigung", cost: 70, expirationDate: "2024-07-01", canceled: false },
    { name: "Vertrag 6", type: "Alle Verträge", cost: 100, expirationDate: "2024-05-11", canceled: false },
    { name: "Vertrag 7", type: "Bald auslaufende Verträge", cost: 60, expirationDate: "2024-01-25", canceled: false },
    { name: "Vertrag 8", type: "Alle Verträge", cost: 50, expirationDate: "2024-06-01", canceled: true },
    { name: "Vertrag 9", type: "Verträge ohne Kündigung", cost: 20, expirationDate: "2024-05-05", canceled: false },
    { name: "Vertrag 10", type: "Alle Verträge", cost: 30, expirationDate: "2024-03-05", canceled: true },
];

// Filter-Logik
function displayContracts(type) {
    const listContainer = document.getElementById('contractsList');
    listContainer.innerHTML = ""; // Clear previous contracts
    
    const filteredContracts = contracts.filter(contract => contract.type === type);

    filteredContracts.forEach(contract => {
        const contractElement = document.createElement('div');
        contractElement.classList.add('contract-item');
        contractElement.innerHTML = `
            <h4>${contract.name}</h4>
            <p>Kosten: ${contract.cost} €</p>
            <p>Auslaufdatum: ${contract.expirationDate}</p>
            <p>Status: ${contract.canceled ? "Gekündigt" : "Aktiv"}</p>
        `;
        listContainer.appendChild(contractElement);
    });
}

// Event-Listener für die Kacheln
document.querySelector('.green').addEventListener('click', () => displayContracts("Alle Verträge"));
document.querySelector('.yellow').addEventListener('click', () => displayContracts("Bald auslaufende Verträge"));
document.querySelector('.orange').addEventListener('click', () => displayContracts("Verträge ohne Kündigung"));

// Standardansicht
displayContracts("Alle Verträge");

// Anzeige der aktuellen monatlichen Kosten
const monthlyCosts = contracts.reduce((total, contract) => total + contract.cost, 0);
document.getElementById('monthlyCosts').textContent = `${monthlyCosts} €`;

// Anzeige der Anzahl der Verträge
document.getElementById('totalContracts').textContent = contracts.length;
document.getElementById('expiringContracts').textContent = contracts.filter(c => new Date(c.expirationDate) <= new Date()).length;
document.getElementById('contractsToCancel').textContent = contracts.filter(c => !c.canceled && new Date(c.expirationDate) <= new Date()).length;
