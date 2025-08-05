// signup.js
document.getElementById('signupForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const fullname = document.getElementById('fullname').value.trim();
  const email    = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  const role     = document.getElementById('role').value;

  // 1) Client-side validation
  if (!fullname || !email || !password || !role) {
    return alert('All fields, including role, are required.');
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    return alert('Please enter a valid email address.');
  }
  if (password.length < 6) {
    return alert('Password must be at least 6 characters.');
  }

  // 2) Send to signup.php
  try {
    const formData = new FormData();
    formData.append('fullname', fullname);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('role', role);

    const resp = await fetch('signup.php', {
      method: 'POST',
      body: formData
    });
    const json = await resp.json();

    if (json.success) {
      alert('Account created! You can now log in.');
      window.location.href = '../login/login.html';
    } else {
      alert(json.message || 'Signup failed.');
    }
  } catch (err) {
    console.error(err);
    alert('An error occurred. Please try again.');
  }
});
