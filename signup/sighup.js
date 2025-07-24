document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('signup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Signup successful! You can now login.');
            window.location.href = '../login/login.html'; // redirect on success
        } else {
            alert(data.message); // show error message
        }
    })
    .catch(() => {
        alert('Something went wrong. Please try again.');
    });
});
