document.addEventListener("DOMContentLoaded", function () {
    const weatherOutput = document.getElementById("weatherOutput");
    const locationSelect = document.getElementById("location");

    function updateWeather(location) {
        let data;

        switch (location) {
            case "dhaka":
                data = "🌤️ Dhaka: 32°C, Humidity 70%, Wind 10 km/h";
                break;
            case "rajshahi":
                data = "☀️ Rajshahi: 35°C, Humidity 50%, Wind 5 km/h";
                break;
            case "sylhet":
                data = "🌧️ Sylhet: 29°C, Humidity 80%, Wind 12 km/h";
                break;
            default:
                data = "Weather data not available.";
        }

        weatherOutput.innerHTML = `<p>${data}</p>`;
    }

    // Initial load
    updateWeather(locationSelect.value);

    // Update on change
    locationSelect.addEventListener("change", function () {
        updateWeather(this.value);
    });

    // Logout logic
    document.getElementById("logoutBtn").addEventListener("click", function () {
        window.location.href = "../login.html";
    });
});
