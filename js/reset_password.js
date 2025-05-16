document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const newPasswordInput = document.getElementById('new_password');
    const tokenInput = document.getElementById('token');
    const passwordRequirementsContainer = document.querySelector('.password-requirements');
    const tokenErrorMessage = document.getElementById('token-error-message');

    // Hide password requirements initially
    if (passwordRequirementsContainer) {
        passwordRequirementsContainer.style.display = 'none';
    }

    // Form submission validation
    form.addEventListener('submit', function (e) {
        let isValid = true;

        if (tokenInput && !validateTokenInput(true)) {
            isValid = false;
        }

        if (newPasswordInput && !validatePasswordInput()) {
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // Token validation function
    function validateTokenInput(showError) {
        const token = tokenInput.value.trim();
        const tokenRegex = /^\d{6}$/;

        if (!token) {
            if (showError) {
                tokenErrorMessage.textContent = 'Token is required.';
                tokenErrorMessage.style.display = 'block';
            }
            tokenInput.style.borderColor = 'red';
            return false;
        } else if (!tokenRegex.test(token)) {
            if (showError) {
                tokenErrorMessage.textContent = 'Token must be exactly 6 numeric digits.';
                tokenErrorMessage.style.display = 'block';
            }
            tokenInput.style.borderColor = 'red';
            return false;
        } else {
            tokenErrorMessage.textContent = '';
            tokenErrorMessage.style.display = 'none';
            tokenInput.style.borderColor = '';
            return true;
        }
    }

    // Password validation function
    function validatePasswordInput() {
        const password = newPasswordInput.value.trim();

        if (password && passwordRequirementsContainer) {
            passwordRequirementsContainer.style.display = 'block';
        }

        const requirements = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*]/.test(password), // Only these special characters are required
        };

        let allRequirementsMet = true;

        for (const [key, isMet] of Object.entries(requirements)) {
            const requirementElement = document.getElementById(key + '-req');
            if (requirementElement) {
                if (isMet) {
                    requirementElement.classList.add('requirement-met');
                    requirementElement.classList.remove('requirement-not-met');
                } else {
                    requirementElement.classList.add('requirement-not-met');
                    requirementElement.classList.remove('requirement-met');
                    allRequirementsMet = false;
                }
            }
        }

        newPasswordInput.style.borderColor = allRequirementsMet ? '' : 'red';
        return allRequirementsMet;
    }

    // Restrict token input to numeric digits only
    tokenInput.addEventListener('keypress', function (e) {
        if (e.key < '0' || e.key > '9') {
            tokenErrorMessage.textContent = 'Only numeric digits allowed.';
            tokenErrorMessage.style.display = 'block';
            tokenInput.style.borderColor = 'red';
            e.preventDefault();
        } else {
            tokenErrorMessage.textContent = '';
            tokenErrorMessage.style.display = 'none';
            tokenInput.style.borderColor = '';
        }
    });

    // Validate token on input
    tokenInput.addEventListener('input', function () {
        validateTokenInput(false);
    });

    // Clean pasted token input
    tokenInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const numericOnly = pastedText.replace(/[^\d]/g, '').substring(0, 6);
        this.value = numericOnly;
        validateTokenInput(false);
    });

    // Validate password on input
    newPasswordInput.addEventListener('input', function () {
        validatePasswordInput();
    });
});
