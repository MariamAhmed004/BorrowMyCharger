document.addEventListener('DOMContentLoaded', function () {
    const editButton = document.getElementById('editButton');
    const saveButton = document.getElementById('saveButton');
    const formInputs = document.querySelectorAll('#profileForm input:not([readonly])');
    const form = document.getElementById('profileForm');
    const deleteForm = document.querySelector('form button[name="delete_account"]').closest('form');

    editButton.addEventListener('click', function () {
        const isEditing = editButton.textContent === 'Edit Profile';
        editButton.textContent = isEditing ? 'Cancel' : 'Edit Profile';
        saveButton.classList.toggle('d-none', !isEditing);

        formInputs.forEach(input => {
            input.disabled = !isEditing;
            if (!isEditing) {
                input.classList.remove('is-invalid');
            }
        });

        if (!isEditing) {
            form.reset();
            document.querySelectorAll('.invalid-feedback').forEach(div => div.textContent = '');
        }
    });

    form.addEventListener('submit', function (e) {
        let isValid = true;

        // Clear previous errors
        document.querySelectorAll('.invalid-feedback').forEach(div => div.textContent = '');
        formInputs.forEach(input => input.classList.remove('is-invalid'));

        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        const phoneNumber = document.getElementById('phone_number');

        const nameRegex = /^[A-Za-z]+(?:[\s-][A-Za-z]+)*$/;
        const phoneRegex = /^[0-9+\-\s]+$/;

        if (!firstName.value.trim()) {
            showError(firstName, 'First name is required');
        } else if (!nameRegex.test(firstName.value.trim())) {
            showError(firstName, 'First name must contain only letters');
        }

        if (!lastName.value.trim()) {
            showError(lastName, 'Last name is required');
        } else if (!nameRegex.test(lastName.value.trim())) {
            showError(lastName, 'Last name must contain only letters');
        }

        if (!phoneNumber.value.trim()) {
            showError(phoneNumber, 'Phone number is required');
        } else if (!phoneRegex.test(phoneNumber.value.trim())) {
            showError(phoneNumber, 'Phone number must contain only digits, spaces, + or -');
        }

        function showError(input, message) {
            const errorDiv = document.getElementById(`${input.id}_error`);
            errorDiv.textContent = message;
            input.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        } else {
            alert('Profile updated successfully!');
        }
    });

    deleteForm.addEventListener('submit', function (e) {
        if (!confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            e.preventDefault();
        }
    });
});
