document.addEventListener("DOMContentLoaded", function () {
    // Form element
    const form = document.getElementById("contact-form");
    
    // Modal elements
    const modal = document.getElementById("messageModal");
    const modalMessage = document.getElementById("modalMessage");
    const closeModal = document.getElementById("closeModal");
    
    // Form validation and submission
    form.addEventListener("submit", function (event) {
        // Prevent default form submission
        event.preventDefault();
        
        // Clear previous error messages
        document.getElementById("name-error").innerHTML = "";
        document.getElementById("email-error").innerHTML = "";
        document.getElementById("message-error").innerHTML = "";
        
        // Collect form values
        const name = document.querySelector('input[name="name"]').value.trim();
        const email = document.querySelector('input[name="email"]').value.trim();
        const message = document.querySelector('textarea[name="message"]').value.trim();
        
        // Validation flags
        let valid = true;
        
        // Name validation
        if (name === "") {
            valid = false;
            document.getElementById("name-error").innerHTML = "Name is required.";
            document.getElementById("name-error").style.display = "block";
        } else if (!validateName(name)) {
            valid = false;
            document.getElementById("name-error").innerHTML = "Name must be in the format 'Firstname Lastname'.";
            document.getElementById("name-error").style.display = "block";
        } else if (containsNumbers(name)) {
            valid = false;
            document.getElementById("name-error").innerHTML = "Name cannot contain numbers.";
            document.getElementById("name-error").style.display = "block";
        } else {
            document.getElementById("name-error").style.display = "none"; // Hide if valid
        }
        
        // Email validation
        if (email === "") {
            valid = false;
            document.getElementById("email-error").innerHTML = "Email is required.";
            document.getElementById("email-error").style.display = "block";
        } else if (!validateEmail(email)) {
            valid = false;
            document.getElementById("email-error").innerHTML = "Please enter a valid email address.";
            document.getElementById("email-error").style.display = "block";
        } else {
            document.getElementById("email-error").style.display = "none"; // Hide if valid
        }
        
        // Message validation
        if (message === "") {
            valid = false;
            document.getElementById("message-error").innerHTML = "Message is required.";
            document.getElementById("message-error").style.display = "block";
        } else if (message.length < 10) {
            valid = false;
            document.getElementById("message-error").innerHTML = "Message must be at least 10 characters long.";
            document.getElementById("message-error").style.display = "block";
        } else {
            document.getElementById("message-error").style.display = "none"; // Hide if valid
        }
        
        // If valid, submit form via AJAX
        if (valid) {
            const formData = new FormData(form);
            
            // Show loading indicator in modal
            modalMessage.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Sending your message...</p></div>';
            modal.style.display = "block";
            
            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Update modal content based on success/failure
                if (data.success) {
                    modalMessage.innerHTML = `
                        <div class="text-center">
                            <i class="fa fa-check-circle fa-3x" style="color: #4CAF50; margin-bottom: 15px;"></i>
                            <p>${data.message}</p>
                        </div>
                    `;
                    // Reset the form
                    form.reset();
                } else {
                    modalMessage.innerHTML = `
                        <div class="text-center">
                            <i class="fa fa-times-circle fa-3x" style="color: #F44336; margin-bottom: 15px;"></i>
                            <p>${data.message}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                // Handle network errors
                modalMessage.innerHTML = `
                    <div class="text-center">
                        <i class="fa fa-exclamation-triangle fa-3x" style="color: #FF9800; margin-bottom: 15px;"></i>
                        <p>Network error. Please check your connection and try again.</p>
                    </div>
                `;
            });
        }
    });
    
    // Modal close button functionality
    closeModal.onclick = function() {
        modal.style.display = "none";
    };
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
    
    // Validation helper functions
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }
    
    function validateName(name) {
        const namePattern = /^[A-Za-z]+ [A-Za-z]+$/; // Matches "Firstname Lastname" format
        return namePattern.test(name);
    }
    
    function containsNumbers(str) {
        return /\d/.test(str); // Check if the string contains any numbers
    }
});


 // Add event listener for the button in the modal
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            document.getElementById('messageModal').style.display = 'none';
        });
    });