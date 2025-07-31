document.addEventListener('DOMContentLoaded', function () {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const inputs = document.querySelectorAll('#profileForm input');

    editBtn.addEventListener('click', () => {
        inputs.forEach(input => input.disabled = false);
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
    });
});
