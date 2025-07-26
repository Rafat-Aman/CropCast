document.addEventListener("DOMContentLoaded", () => {
    fetch("agrodata.php?lat=23.81&lon=90.41") // Dhaka coords
        .then((res) => res.json())
        .then((data) => {
            const weather = document.getElementById("weatherData");
            if (data && data.main) {
                weather.innerText = `${data.weather[0].description}, ${data.main.temp}°K`;
            } else {
                weather.innerText = "Weather data unavailable.";
            }
        })
        .catch(() => {
            document.getElementById("weatherData").innerText = "Error loading weather.";
        });

    document.getElementById("logoutBtn").addEventListener("click", () => {
        window.location.href = "../login/login.html";
    });

    // Initialize Leaflet map
    const map = L.map('map').setView([23.8103, 90.4125], 8); // Dhaka

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    // Optional marker
    L.marker([23.8103, 90.4125])
        .addTo(map)
        .bindPopup('Your Farm Location')
        .openPopup();

});
