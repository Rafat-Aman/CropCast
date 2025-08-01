document.addEventListener("DOMContentLoaded", function () {
    const fieldList = [
        { name: "North Plot", size: "2 acres" },
        { name: "South Field", size: "3.5 acres" }
    ];

    const fieldData = document.getElementById("fieldData");
    fieldData.innerHTML = "";

    fieldList.forEach(field => {
        const li = document.createElement("li");
        li.textContent = `${field.name} — ${field.size}`;
        fieldData.appendChild(li);
    });

    document.getElementById("addFieldBtn").addEventListener("click", function () {
        const name = prompt("Enter field name:");
        const size = prompt("Enter field size (e.g. 2 acres):");

        if (name && size) {
            const li = document.createElement("li");
            li.textContent = `${name} — ${size}`;
            fieldData.appendChild(li);
        }
    });
});

// Logout logic
document.getElementById("logoutBtn").addEventListener("click", function () {
    window.location.href = "../login.html";
});
