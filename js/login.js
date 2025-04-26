// Wait until the page is fully loaded
$(document).ready(function() {
    // Attach submit event handler to the login form
    $('#login-form').on('submit', function(e) {
        // Clear any previous error messages
        $('#email-error').text('');
        $('#password-error').text('');

        // Get values from input fields
        let email = $('#email').val().trim();
        let password = $('#password').val().trim();
        let hasError = false; // Flag to check if there are validation errors

        // Check if email is empty
        if (!email) {
            $('#email-error').text('Email is required.');
            hasError = true;
        }
        // Check if email format is invalid
        else if (!validateEmail(email)) {
            $('#email-error').text('Invalid email format.');
            hasError = true;
        }
        // Check if password is empty
        if (!password) {
            $('#password-error').text('Password is required.');
            hasError = true;
        }

        // If there are any errors, prevent the form from being submitted
        if (hasError) {
            e.preventDefault();
        }
    });

    // Helper function to validate correct email format using regular expression
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});