document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('login-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');

    form.addEventListener('submit', function (e) {
        let hasError = false;
        emailError.textContent = '';
        passwordError.textContent = '';

        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        if (!email) {
            emailError.textContent = 'Email is required.';
            hasError = true;
        } else if (!validateEmail(email)) {
            emailError.textContent = 'Invalid email format.';
            hasError = true;
        }

        if (!password) {
            passwordError.textContent = 'Password is required.';
            hasError = true;
        }

        if (hasError) {
            e.preventDefault();
        }
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
