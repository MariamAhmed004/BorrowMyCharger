document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form');
    const recaptchaCheckbox = document.querySelector('.g-recaptcha');

    // Add live validation for each input field
    document.getElementById('username').addEventListener('input', function () {
        const username = this.value.trim();
        if (!validateUsername(username)) {
            showError('username', 'Username must be at least 3 characters and cannot contain spaces.');
        } else {
            clearError('username');
        }
    });

    document.getElementById('firstname').addEventListener('input', function () {
        const firstname = this.value.trim();
        if (!validateName(firstname)) {
            showError('firstname', 'First name must be at least 2 letters and cannot contain numbers.');
        } else {
            clearError('firstname');
        }
    });

    document.getElementById('lastname').addEventListener('input', function () {
        const lastname = this.value.trim();
        if (!validateName(lastname)) {
            showError('lastname', 'Last name must be at least 2 letters and cannot contain numbers.');
        } else {
            clearError('lastname');
        }
    });

    document.getElementById('email').addEventListener('input', function () {
        const email = this.value.trim();
        if (!validateEmail(email)) {
            showError('email', 'Invalid email format.');
        } else {
            clearError('email');
        }
    });

    document.getElementById('password').addEventListener('input', function () {
        const password = this.value.trim();
        clearError('password'); // Clear previous errors

        // Combined password validation
        const errors = [];
        if (password.length < 8) {
            errors.push('at least 8 characters');
        }
        if (!/[A-Z]/.test(password)) {
            errors.push('at least 1 uppercase letter');
        }
        if (!/[a-z]/.test(password)) {
            errors.push('at least 1 lowercase letter');
        }
        if (!/\d/.test(password)) {
            errors.push('at least 1 digit');
        }
        if (!/[!@#$%^&*]/.test(password)) {
            errors.push('at least 1 special character');
        }

        if (errors.length > 0) {
            showError('password', 'Must include: ' + errors.join(', ') + '.');
        }
    });

    document.getElementById('phone').addEventListener('input', function () {
        const phone = this.value.trim();
        if (!/^\d{0,8}$/.test(phone)) {
            showError('phone', 'Phone number must be exactly 8 digits and numeric.');
        } else if (phone.length === 8) {
            clearError('phone');
        }
    });

    // Form submission handling
    form.addEventListener('submit', function (e) {
        clearErrors();
        let hasErrors = false;

        const username = document.getElementById('username').value.trim();
        const firstname = document.getElementById('firstname').value.trim();
        const lastname = document.getElementById('lastname').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const userType = document.getElementById('user-type').value;

        // Validate user type
        if (!userType) {
            showError('user-type', 'User type must be selected.');
            hasErrors = true;
        }


        if (!hasErrors) {
            e.preventDefault();
            checkUserExists(username, email).then(response => {
                if (response.usernameExists) {
                    showError('username', 'The username already exists.');
                    hasErrors = true;
                }

                if (response.emailExists) {
                    showError('email', 'The email already exists.');
                    hasErrors = true;
                }

                if (!hasErrors) {
                    form.submit();
                }
            });
        } else {
            e.preventDefault();
        }
    });

    function validateUsername(username) {
        return /^[a-zA-Z0-9_-]{3,}$/.test(username) && !username.includes(' ');
    }

    function validateName(name) {
        return /^[a-zA-Z]{2,}$/.test(name);
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    function showError(inputId, message) {
        const errorDiv = document.getElementById(inputId + '-error');
        if (errorDiv) {
            errorDiv.innerText = message;
        }
    }

    function clearError(inputId) {
        const errorDiv = document.getElementById(inputId + '-error');
        if (errorDiv) {
            errorDiv.innerText = '';
        }
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(function (div) {
            div.innerText = '';
        });
    }

    function checkUserExists(username, email) {
        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_user.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    resolve(JSON.parse(xhr.responseText));
                } else {
                    resolve({ usernameExists: false, emailExists: false });
                }
            };
            xhr.send(`username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}`);
        });
    }
});

// === ReCAPTCHA Callback Functions ===
window.enableRegisterButton = function () {
    const registerButton = document.getElementById('register-button');
    if (registerButton) {
        registerButton.disabled = false;
    }
};

window.disableRegisterButton = function () {
    const registerButton = document.getElementById('register-button');
    if (registerButton) {
        registerButton.disabled = true;
    }
};