document.addEventListener("DOMContentLoaded", function () {
    // Select the form element
    const form = document.querySelector('form');

    // Add an event listener for the form submission
    form.addEventListener('submit', function (e) {
        clearErrors(); // Clear previous error messages
        let hasErrors = false; // Flag to track validation errors

        // Get input values and trim whitespace
        const username = document.getElementById('username').value.trim();
        const firstname = document.getElementById('firstname').value.trim();
        const lastname = document.getElementById('lastname').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const country = document.getElementById('country').value;
        const phone = document.getElementById('phone').value.trim();
        const userType = document.getElementById('user-type').value;

        // Validate username
        if (!/^[a-zA-Z0-9_-]{3,}$/.test(username)) {
            showError('username', 'Username must be at least 3 characters long and can include letters, numbers, underscores, and hyphens.');
            hasErrors = true; // Mark as having errors
        }
        // Validate firstname
        if (!/^[a-zA-Z]{2,}$/.test(firstname)) {
            showError('firstname', 'Firstname must be at least 2 letters.');
            hasErrors = true;
        }
        // Validate lastname
        if (!/^[a-zA-Z]{2,}$/.test(lastname)) {
            showError('lastname', 'Lastname must be at least 2 letters.');
            hasErrors = true;
        }
        // Validate email format
        if (!validateEmail(email)) {
            showError('email', 'Invalid email format.');
            hasErrors = true;
        }
      // Validate password strength
if (!validatePassword(password)) {
    showError('password', 'Password must be at least 6 characters long and include at least one uppercase letter and one digit.');
    hasErrors = true;
}
       // Validate phone number format
if (!/^\d+$/.test(phone)) {
    showError('phone', 'Phone number can only contain digits.');
    hasErrors = true;
} else if (phone.length < 7 || phone.length > 15) {
    showError('phone', 'Phone number must be between 7 and 15 digits long.');
    hasErrors = true;
}
        // Check if country is selected
        if (!country) {
            showError('country', 'Country must be selected.');
            hasErrors = true;
        }
        // Check if user type is selected
        if (!userType) {
            showError('user-type', 'User type must be selected.');
            hasErrors = true;
        }

        // Check for existing username or email if no validation errors
        if (!hasErrors) {
            e.preventDefault(); // Prevent form submission to wait for the AJAX call
            checkUserExists(username, email).then(response => {
                if (response.usernameExists) {
                    showError('username', 'The username already exists.'); // Show error for existing username
                }
                if (response.emailExists) {
                    showError('email', 'The email already exists.'); // Show error for existing email
                }

                // If there are no errors, submit the form
                if (!response.usernameExists && !response.emailExists) {
                    form.submit(); // Submit the form if no errors found
                }
            });
        } else {
            e.preventDefault(); // Stop form from submitting due to validation errors
        }
    });

    // Function to display error message for a specific input
    function showError(inputId, message) {
        const input = document.getElementById(inputId);
        const errorDiv = input.parentNode.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.innerText = message; // Set the error message
        }
    }

    // Function to clear all error messages
    function clearErrors() {
        document.querySelectorAll('.error-message').forEach(function(div) {
            div.innerText = ''; // Clear the text of each error message
        });
    }

    // Function to validate email format
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Regex for email validation
        return re.test(String(email).toLowerCase()); // Test the email against the regex
    }

// Function to validate password strength
function validatePassword(password) {
    const re = /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d@$!%*?&]{6,}$/; // Regex for easier password
    return re.test(password); // Test the password against the regex
}

    // Function to check if the username or email exists
    function checkUserExists(username, email) {
        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'check_user.php', true); // Open a POST request to check_user.php
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); // Set content type
            xhr.onload = function () {
                if (xhr.status === 200) {
                    resolve(JSON.parse(xhr.responseText)); // Resolve with the response data
                } else {
                    resolve({ usernameExists: false, emailExists: false }); // Assume no exists on error
                }
            };
            xhr.send(`username=${encodeURIComponent(username)}&email=${encodeURIComponent(email)}`); // Send username and email
        });
    }
});


   document.addEventListener("DOMContentLoaded", function () {
            const gccCountries = [
                { name: "Bahrain", code: "BH" },
                { name: "Kuwait", code: "KW" },
                { name: "Oman", code: "OM" },
                { name: "Qatar", code: "QA" },
                { name: "Saudi Arabia", code: "SA" },
                { name: "United Arab Emirates", code: "AE" }
            ];

            gccCountries.forEach(function(country) {
                document.getElementById('country').append(new Option(country.name, country.code));
            });
        });