document.addEventListener("DOMContentLoaded", function () {
    fetch("weather_api.php")
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById("weather-data");
            container.innerHTML = `
                <p><strong>Temperature:</strong> ${data.temp}°C</p>
                <p><strong>Condition:</strong> ${data.condition}</p>
            `;
        })
        .catch(error => {
            document.getElementById("weather-data").innerText = "Error loading weather data.";
        });
});
