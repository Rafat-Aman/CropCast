document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('signup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Response:', data); // debug
        if (data.success) {
            alert('Signup successful! You can now login.');
            window.location.href = '../login/login.html';
        } else {
            alert(data.message || 'Something went wrong.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Something went wrong. Try again later.');
    });
});
