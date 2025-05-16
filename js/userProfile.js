document.addEventListener('DOMContentLoaded', function () {

    // Function to show a Bootstrap alert that disappears after 5 seconds
    function showBootstrapAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alertContainer');
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        // Automatically hide the alert after 5 seconds
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                alert.classList.add('hide');
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    }

    // Get references to buttons and form elements
    const editButton = document.getElementById('editButton');
    const saveButton = document.getElementById('saveButton');
    const formInputs = document.querySelectorAll('#profileForm input:not([readonly])');
    const form = document.getElementById('profileForm');
    const deleteForm = document.querySelector('form button[name="delete_account"]').closest('form');

    // Toggle edit mode when "Edit Profile" button is clicked
    editButton.addEventListener('click', function () {
        const isEditing = editButton.textContent === 'Edit Profile';
        editButton.textContent = isEditing ? 'Cancel' : 'Edit Profile';
        saveButton.classList.toggle('d-none', !isEditing);

        // Enable or disable form inputs
        formInputs.forEach(input => {
            input.disabled = !isEditing;
            if (!isEditing) {
                input.classList.remove('is-invalid');
            }
        });

        // Reset form and clear validation messages when cancelling edit
        if (!isEditing) {
            form.reset();
            document.querySelectorAll('.invalid-feedback').forEach(div => div.textContent = '');
        }
    });

    // Handle form submission and validation
    form.addEventListener('submit', function (e) {
        let isValid = true;

        // Clear previous validation errors
        document.querySelectorAll('.invalid-feedback').forEach(div => div.textContent = '');
        formInputs.forEach(input => input.classList.remove('is-invalid'));

        // Get input values
        const firstName = document.getElementById('first_name');
        const lastName = document.getElementById('last_name');
        const phoneNumber = document.getElementById('phone_number');

        // Validation patterns
        const nameRegex = /^[A-Za-z]+(?:[\s-][A-Za-z]+)*$/;
        const phoneRegex = /^\d{8,}$/; // Only digits, at least 8

        // Validate first name
        if (!firstName.value.trim()) {
            showError(firstName, 'First name is required');
        } else if (!nameRegex.test(firstName.value.trim())) {
            showError(firstName, 'First name must contain only letters');
        }

        // Validate last name
        if (!lastName.value.trim()) {
            showError(lastName, 'Last name is required');
        } else if (!nameRegex.test(lastName.value.trim())) {
            showError(lastName, 'Last name must contain only letters');
        }

        // Validate phone number
        if (!phoneNumber.value.trim()) {
            showError(phoneNumber, 'Phone number is required');
        } else if (!phoneRegex.test(phoneNumber.value.trim())) {
            showError(phoneNumber, 'Phone number must be at least 8 digits and contain only numbers');
        }

        // Function to display validation error
        function showError(input, message) {
            const errorDiv = document.getElementById(`${input.id}_error`);
            errorDiv.textContent = message;
            input.classList.add('is-invalid');
            isValid = false;
        }

  
if (!isValid) {
        e.preventDefault();
    } else {
        e.preventDefault(); // Temporarily prevent submission
        showBootstrapAlert('Profile updated successfully!', 'success');

        // Delay form submission by 1.5 seconds to show the alert
        setTimeout(() => {
            form.submit(); // Submit the form programmatically
        }, 1500);

        // Hide save button and reset edit mode
        saveButton.classList.add('d-none');
        editButton.textContent = 'Edit Profile';
    }


    });

    // Confirm before deleting account
    deleteForm.addEventListener('submit', function (e) {
        if (!confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
            e.preventDefault();
        }
    });
});
