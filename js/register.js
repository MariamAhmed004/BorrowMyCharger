document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector('form');

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

        // Username validation
        if (username.includes(' ')) {
            showError('username', 'Username cannot contain spaces.');
            hasErrors = true;
        } else if (!/^[a-zA-Z0-9_-]{3,}$/.test(username)) {
            showError('username', 'Username must be at least 3 characters and include only letters, numbers, underscores, or hyphens.');
            hasErrors = true;
        }

        // First name validation
        if (!/^[a-zA-Z]{2,}$/.test(firstname)) {
            if (/\d/.test(firstname)) {
                showError('firstname', 'First name cannot contain numbers.');
            } else {
                showError('firstname', 'First name must be at least 2 letters.');
            }
            hasErrors = true;
        }

        // Last name validation
        if (!/^[a-zA-Z]{2,}$/.test(lastname)) {
            if (/\d/.test(lastname)) {
                showError('lastname', 'Last name cannot contain numbers.');
            } else {
                showError('lastname', 'Last name must be at least 2 letters.');
            }
            hasErrors = true;
        }

        // Email validation
        if (!validateEmail(email)) {
            showError('email', 'Invalid email format.');
            hasErrors = true;
        }

        // Password validation
        if (!validatePassword(password)) {
            showError('password', 'Password must be at least 6 characters, include one uppercase letter and one digit.');
            hasErrors = true;
        }

        // Phone validation
        if (!/^\d+$/.test(phone)) {
            showError('phone', 'Phone number can only contain digits.');
            hasErrors = true;
        } else if (phone.length < 7 || phone.length > 15) {
            showError('phone', 'Phone number must be between 7 and 15 digits.');
            hasErrors = true;
        }

        // User type validation
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

    function showError(inputId, message) {
        const input = document.getElementById(inputId);
        const errorDiv = input.parentNode.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.innerText = message;
        }
    }

    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(function (div) {
            div.innerText = '';
        });
    }

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    function validatePassword(password) {
        const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/;
        return re.test(password);
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
