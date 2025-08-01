document.addEventListener("DOMContentLoaded", function () {
    const fieldSelect = document.getElementById("field");
    const soilOutput = document.getElementById("soilOutput");

    const soilData = {
        north: "Moisture: 25% | pH: 6.8 | Nitrogen: Adequate",
        south: "Moisture: 18% | pH: 6.3 | Nitrogen: Low"
    };

    function updateSoilInfo(field) {
        soilOutput.innerHTML = `<p>${soilData[field]}</p>`;
    }

    // Load default field
    updateSoilInfo(fieldSelect.value);

    // On change
    fieldSelect.addEventListener("change", function () {
        updateSoilInfo(this.value);
    });

    // Logout logic
    document.getElementById("logoutBtn").addEventListener("click", function () {
        window.location.href = "../login.html";
    });
});
