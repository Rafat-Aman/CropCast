// profile.js

document.addEventListener('DOMContentLoaded', async () => {
  try {
    const resp = await fetch('profile.php');
    if (resp.status === 401) {
      alert('Session expired. Please log in again.');
      return window.location.href = '../../login/login.html';
    }
    if (!resp.ok) {
      throw new Error('Network error');
    }
    const data = await resp.json();
    // populate each field
    Object.entries(data).forEach(([key, val]) => {
      const el = document.getElementById(key);
      if (el) el.textContent = val ?? '';
    });
  } catch (err) {
    console.error(err);
    alert('Unable to load profile.');
  }
});

// ensure logout link sends to logout
document.getElementById('logout-link').addEventListener('click', e => {
  e.preventDefault();
  window.location.href = '../../logout.php';
});
