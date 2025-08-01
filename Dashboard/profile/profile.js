// profile.js
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const resp = await fetch('profile.php');
    if (resp.status === 401) {
      alert('Session expired. Please log in again.');
      return window.location.href = '../../login/login.html';
    }
    if (!resp.ok) {
      alert('Failed to load profile.');
      return;
    }
    const data = await resp.json();
    Object.keys(data).forEach(key => {
      const el = document.getElementById(key);
      if (el) el.value = data[key] ?? '';
    });
  } catch (err) {
    console.error(err);
    alert('Error loading profile.');
  }
});

const form = document.getElementById('profileForm');
const editBtn = document.getElementById('editProfileBtn');
let editing = false;

editBtn.addEventListener('click', async () => {
  editing = !editing;
  [...form.elements].forEach(el => {
    if (el.name) el.disabled = !editing;
  });

  if (editing) {
    editBtn.textContent = 'Save Changes';
  } else {
    editBtn.textContent = 'Edit Profile';
    try {
      const formData = new FormData(form);
      const resp = await fetch('profile.php', {
        method: 'POST',
        body: formData
      });
      const result = await resp.json();
      if (result.success) {
        alert('Profile updated!');
      } else {
        alert(result.message || 'Update failed.');
      }
    } catch (err) {
      console.error(err);
      alert('Error updating profile.');
    }
  }
});
