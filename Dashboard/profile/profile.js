// Simulate user profile data fetch
document.addEventListener("DOMContentLoaded", function () {
    // Normally you would fetch this data from a backend
    const userProfile = {
        fullname: "Rafat Aman",
        email: "rafat@example.com",
        location: "Dhaka, Bangladesh"
    };

    document.getElementById("fullname").textContent = userProfile.fullname;
    document.getElementById("email").textContent = userProfile.email;
    document.getElementById("location").textContent = userProfile.location;
});

// Logout button logic
document.getElementById("logoutBtn").addEventListener("click", function () {
    window.location.href = "../login.html"; // or trigger actual logout logic
});
