document.addEventListener("DOMContentLoaded", () => {
    // Weather
    fetch("dashboard.php")
        .then((res) => res.json())
        .then((data) => {
            const weather = document.getElementById("weatherData");
            if (data.success) {
                weather.innerText = `${data.temp}°C, ${data.condition}`;
            } else {
                weather.innerText = "Weather data unavailable.";
            }
        });

    // Soil
    fetch("soil.php")
        .then((res) => res.json())
        .then((data) => {
            const soil = document.getElementById("soilData");
            if (data && data.moisture !== undefined) {
                const moisture = (data.moisture * 100).toFixed(2);
                soil.innerText = `Soil Moisture: ${moisture}%\nTemp: ${data.t0}°C\nPH: ${data.ph}`;
            } else {
                soil.innerText = "Soil data not available.";
            }
        });

    // Map
    const map = L.map("map").setView([23.8103, 90.4125], 8);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
    L.marker([23.8103, 90.4125])
        .addTo(map)
        .bindPopup("Your Farm Location")
        .openPopup();

    // Logout
    document.getElementById("logoutBtn").addEventListener("click", () => {
        window.location.href = "../login/login.html";
    });

    // Profile menu click (for now just alert)
    document.getElementById("menu-profile").addEventListener("click", () => {
        alert("Profile page coming soon...");
        // You can load profile.html here or swap content via JS
    });
});
