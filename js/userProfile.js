document.addEventListener('DOMContentLoaded', function() {
    const editButton = document.getElementById('editButton');
    const saveButton = document.getElementById('saveButton');
    const formInputs = document.querySelectorAll('#profileForm input:not([readonly])');
    const form = document.getElementById('profileForm');

    editButton.addEventListener('click', function() {
        if (editButton.textContent === 'Edit Profile') {
            editButton.textContent = 'Cancel';
            saveButton.style.display = 'inline-block';
            formInputs.forEach(input => input.disabled = false);
        } else {
            editButton.textContent = 'Edit Profile';
            saveButton.style.display = 'none';
            formInputs.forEach(input => {
                input.disabled = true;
                input.classList.remove('validation-error');
            });
            document.querySelectorAll('.error').forEach(div => {
                if (div.id.includes('_error')) div.textContent = '';
            });
            form.reset();
        }
    });

    form.addEventListener('submit', function(e) {
        let isValid = true;
        document.querySelectorAll('.error').forEach(div => div.textContent = '');
        document.querySelectorAll('.validation-error').forEach(input => input.classList.remove('validation-error'));

        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        const phoneNumber = document.getElementById('phone_number');

        const nameRegex = /^[A-Za-z]+(?:[\s-][A-Za-z]+)*$/;
        const phoneRegex = /^[0-9+\-\s]+$/;

        if (!firstName.value.trim()) {
            document.getElementById('first_name_error').textContent = 'First name is required';
            firstName.classList.add('validation-error');
            isValid = false;
        } else if (!nameRegex.test(firstName.value.trim())) {
            document.getElementById('first_name_error').textContent = 'First name must contain only letters';
            firstName.classList.add('validation-error');
            isValid = false;
        }

        if (!lastName.value.trim()) {
            document.getElementById('last_name_error').textContent = 'Last name is required';
            lastName.classList.add('validation-error');
            isValid = false;
        } else if (!nameRegex.test(lastName.value.trim())) {
            document.getElementById('last_name_error').textContent = 'Last name must contain only letters';
            lastName.classList.add('validation-error');
            isValid = false;
        }

        if (!phoneNumber.value.trim()) {
            document.getElementById('phone_number_error').textContent = 'Phone number is required';
            phoneNumber.classList.add('validation-error');
            isValid = false;
        } else if (!phoneRegex.test(phoneNumber.value.trim())) {
            document.getElementById('phone_number_error').textContent = 'Phone number must contain only digits, spaces, + or -';
            phoneNumber.classList.add('validation-error');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        } else {
            // Display success alert
            alert('Profile updated successfully!');
        }
    });

    document.querySelector('form[action=""]').addEventListener('submit', function(e) {
        if (!confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            e.preventDefault();
        }
    });
});