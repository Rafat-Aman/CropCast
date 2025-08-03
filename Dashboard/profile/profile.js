// profile.js

console.log('✅ profile.js loaded');

const form  = document.getElementById('profileForm');
const btn   = document.getElementById('editBtn');
let editing = false;

// helper: enable/disable all form controls (except farmerID)
function setDisabled(disabled) {
  console.log(`➤ setDisabled(${disabled})`);
  [...form.elements].forEach(el => {
    if (!el.name) return;              // skip anonymous
    if (el.name === 'farmerID') {
      el.disabled = true;              // always keep farmerID disabled
    } else {
      el.disabled = disabled;          // toggle disabled on everything else
    }
    console.log(`  • ${el.tagName}[name=${el.name}] disabled=${el.disabled}`);
  });
}

async function fetchProfile() {
  console.log('🔄 fetchProfile()');
  const resp = await fetch('profile_fetch.php');
  const json = await resp.json();
  if (!json.success) {
    alert(json.message || 'Failed to fetch profile');
    if (resp.status === 401) window.location.href = '../../login/login.html';
    return;
  }
  Object.entries(json.data).forEach(([key, val]) => {
    const el = document.getElementById(key);
    if (el) el.value = val ?? '';
  });
}

async function saveProfile() {
  console.log('💾 saveProfile()');
  console.log(`Form elements total: ${form.elements.length}`);
  [...form.elements].forEach((el,i) => {
    if (!el.name) return;
    console.log(`  [${i}] ${el.name}: value="${el.value}" disabled=${el.disabled}`);
  });

  const fd = new FormData();
  [...form.elements].forEach(el => {
    if (!el.name) return;
    fd.append(el.name, el.value);
  });
  fd.delete('farmerID');

  console.log('---- FormData contents ----');
  for (let [k,v] of fd.entries()) {
    console.log(`   ↳ ${k}: ${v}`);
  }
  console.log('---------------------------');

  const resp = await fetch('profile_update.php', {
    method: 'POST',
    body: fd
  });
  const result = await resp.json();
  if (!result.success) throw new Error(result.message||'Save failed');
}

btn.addEventListener('click', async () => {
  console.log('✏️ editBtn clicked, editing=', editing);
  if (!editing) {
    // enter edit mode
    editing = true;
    btn.textContent = 'Save';
    setDisabled(false);  // unlock all inputs
  } else {
    // save mode
    try {
      await saveProfile();
      alert('Profile updated!');
      // exit edit mode
      editing = false;
      btn.textContent = 'Edit';
      setDisabled(true);  // lock all inputs back
    } catch (err) {
      console.error('❌ saveProfile error', err);
      alert(err.message);
    }
  }
});

document.addEventListener('DOMContentLoaded', () => {
  console.log('🔅 DOMContentLoaded');
  fetchProfile()
    .then(() => setDisabled(true))  // lock form initially
    .catch(err => {
      console.error(err);
      alert('Could not load profile');
    });
});
