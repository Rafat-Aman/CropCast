document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("settingsForm");

    // Simulated existing settings
    document.getElementById("notif").value = "on";
    document.getElementById("theme").value = "light";
    document.getElementById("language").value = "en";

    form.addEventListener("submit", function (e) {
        e.preventDefault();

        const settings = {
            notifications: document.getElementById("notif").value,
            theme: document.getElementById("theme").value,
            language: document.getElementById("language").value
        };

        console.log("Saved settings:", settings);
        alert("Settings saved successfully!");
        // Here you would normally POST to a server to save them
    });

    document.getElementById("logoutBtn").addEventListener("click", function () {
        window.location.href = "../login.php";
    });
});
