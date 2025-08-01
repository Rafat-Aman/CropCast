// Simulate weather data fetch
document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("weatherData").textContent = "25°C, Clear Sky";
    document.getElementById("soilData").textContent = "Moisture: 22%, pH: 6.5";

    // Leaflet Map Init
    var map = L.map('map').setView([23.6850, 90.3563], 7); // Bangladesh coordinates

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
});

