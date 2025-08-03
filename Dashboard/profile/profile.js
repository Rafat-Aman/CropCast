// profile.js
console.log('✅ profile.js loaded');

const form  = document.getElementById('profileForm');
const btn   = document.getElementById('editBtn');
const picIn = document.getElementById('profile_picture');
const picEl = document.getElementById('profilePicPreview');
let editing = false;

function setDisabled(disabled) {
  [...form.elements].forEach(el => {
    if (!el.name) return;
    el.disabled = (el.name === 'farmerID') ? true : disabled;
  });
}

picIn.addEventListener('change', () => {
  if (picIn.files[0]) {
    const reader = new FileReader();
    reader.onload = e => picEl.src = e.target.result;
    reader.readAsDataURL(picIn.files[0]);
  }
});

async function fetchProfile() {
  const resp = await fetch('profile_fetch.php');
  const { success, data, message } = await resp.json();
  if (!success) {
    alert(message || 'Failed to fetch profile');
    if (resp.status === 401) window.location.href='../../login/login.html';
    return;
  }
  Object.entries(data).forEach(([k,v]) => {
    if (k === 'profile_picture' && v) picEl.src = v;
    else {
      const el = document.getElementById(k);
      if (el) el.value = v ?? '';
    }
  });
}

async function saveProfile() {
  const fd = new FormData();
  [...form.elements].forEach(el => {
    if (!el.name) return;
    if (el.type === 'file') {
      if (el.files[0]) fd.append(el.name, el.files[0]);
    } else {
      fd.append(el.name, el.value);
    }
  });
  fd.delete('farmerID');

  const resp = await fetch('profile_update.php', { method:'POST', body:fd });
  const { success, message } = await resp.json();
  if (!success) throw new Error(message || 'Save failed');
}

btn.addEventListener('click', async () => {
  editing = !editing;
  btn.textContent = editing ? 'Save' : 'Edit';
  setDisabled(!editing);
  if (!editing) {
    try {
      await saveProfile();
      alert('Profile updated!');
    } catch (e) {
      alert(e.message);
    }
  }
});

document.addEventListener('DOMContentLoaded', () =>
  fetchProfile().then(()=> setDisabled(true))
);
