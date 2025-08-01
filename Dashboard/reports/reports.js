document.addEventListener("DOMContentLoaded", function () {
    const reportList = document.getElementById("reportList");

    const reports = [
        "Report for North Plot - Aug 1, 2025",
        "Report for South Field - July 25, 2025",
        "Monthly Summary - July 2025"
    ];

    reportList.innerHTML = "";

    reports.forEach(report => {
        const li = document.createElement("li");
        li.textContent = report;
        reportList.appendChild(li);
    });

    document.getElementById("downloadBtn").addEventListener("click", function () {
        alert("Downloading reports as PDF... (simulation)");
        // Replace with real download logic or PDF generation
    });

    document.getElementById("logoutBtn").addEventListener("click", function () {
        window.location.href = "../login.html";
    });
});
