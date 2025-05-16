
    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch all forms with the class 'needs-validation'
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over each form and prevent submission if fields are invalid
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
        
        // Initialize map
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        
        // Default coordinates for new charge point (centered on Bahrain)
        const lat = 26.0275; // Latitude for Bahrain
        const lng = 50.5505; // Longitude for Bahrain
        
        // Create map
        const map = L.map('map').setView([lat, lng], 12);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 20
        }).addTo(map);
        
        // Create marker at initial position (can be null for new charge point)
        let marker = null;
        
        // Update marker and coordinates when clicking on the map
        map.on('click', function(e) {
            // Remove existing marker if exists
            if (marker) {
                map.removeLayer(marker);
            }
            
            // Create new marker at clicked position
            marker = L.marker(e.latlng).addTo(map);
            
            // Update form fields
            latInput.value = e.latlng.lat.toFixed(6);
            lngInput.value = e.latlng.lng.toFixed(6);
        });
        
        // Availability time slot handling
        const timeSlotInputs = document.querySelectorAll('.time-slot-input');
        const availabilityStatusSelect = document.getElementById('availability_status_id');
        
        // Helper function to validate hour format
        function isValidHourFormat(input) {
            // Check if the input is in the format of comma-separated numbers (0-23)
            const hourPattern = /^(\d{1,2}(,\s*)?)*$/;
            if (!hourPattern.test(input)) {
                return false;
            }
            
            // Check that each number is a valid hour (0-23)
            const hours = input.split(',');
            for (let hour of hours) {
                hour = hour.trim();
                if (hour === '') continue;
                
                const hourNum = parseInt(hour, 10);
                if (isNaN(hourNum) || hourNum < 0 || hourNum > 23) {
                    return false;
                }
            }
            
            return true;
        }
        
        // Add business hours (9-17) to a day
        function addBusinessHours(dayElement) {
            dayElement.value = '9,10,11,12,13,14,15,16,17';
            validateTimeSlots();
        }
        
        // Add all hours (0-23) to a day
        function addAllHours(dayElement) {
            dayElement.value = '0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23';
            validateTimeSlots();
        }
        
        // Add event listeners for business hours buttons
        document.querySelectorAll('.add-business-hours').forEach(button => {
            button.addEventListener('click', function() {
                const day = this.getAttribute('data-day');
                const input = document.getElementById('availability_' + day);
                addBusinessHours(input);
            });
        });
        
        // Add event listeners for all hours buttons
        document.querySelectorAll('.add-all-hours').forEach(button => {
            button.addEventListener('click', function() {
                const day = this.getAttribute('data-day');
                const input = document.getElementById('availability_' + day);
                addAllHours(input);
            });
        });
        
        // Clear time slots for a specific day
        document.querySelectorAll('.clear-time-slots').forEach(button => {
            button.addEventListener('click', function() {
                const day = this.getAttribute('data-day');
                const input = document.getElementById('availability_' + day);
                input.value = '';
                validateTimeSlots();
            });
        });
        
        // Apply business hours to all days
        document.querySelector('.apply-business-hours').addEventListener('click', function() {
            timeSlotInputs.forEach(input => {
                addBusinessHours(input);
            });
        });
        
        // Apply all hours to all days
        document.querySelector('.apply-all-hours').addEventListener('click', function() {
            timeSlotInputs.forEach(input => {
                addAllHours(input);
            });
        });
        
        // Apply business hours to weekdays only
        document.querySelector('.apply-weekdays').addEventListener('click', function() {
            const weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            
            timeSlotInputs.forEach(input => {
                const day = input.id.replace('availability_', '');
                if (weekdays.includes(day)) {
                    addBusinessHours(input);
                }
            });
        });
        
        // Clear all time slots
        document.querySelector('.clear-all-slots').addEventListener('click', function() {
            timeSlotInputs.forEach(input => {
                input.value = '';
            });
            validateTimeSlots();
        });
        
        // Validate time slots and update availability status if needed
        function validateTimeSlots() {
            let hasValidTimeSlots = false;
            
            timeSlotInputs.forEach(input => {
                const value = input.value.trim();
                if (value) {
                    if (isValidHourFormat(value)) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                        hasValidTimeSlots = true;
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    }
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.remove('is-valid');
                }
            });
            
            // If no valid time slots, set availability status to unavailable
            // Find the "unavailable" option in the select element
            if (!hasValidTimeSlots) {
                const unavailableOption = Array.from(availabilityStatusSelect.options).find(option => 
                    option.textContent.toLowerCase().includes('unavailable'));
                
                if (unavailableOption) {
                    availabilityStatusSelect.value = unavailableOption.value;
                }
            }
        }
        
        // Initial validation
        validateTimeSlots();
        
        // Add validation on time slot input change
        timeSlotInputs.forEach(input => {
            input.addEventListener('change', function() {
                validateTimeSlots();
            });
            
            input.addEventListener('input', function() {
                // Simple inline validation while typing
                const value = this.value.trim();
                if (value === '' || isValidHourFormat(value)) {
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.add('is-invalid');
                }
            });
        });
        
        // Add validation on form submission
        document.querySelector('form').addEventListener('submit', function(event) {
            validateTimeSlots();
            
            // Check if any time slot input is invalid
            const invalidInputs = document.querySelectorAll('.time-slot-input.is-invalid');
            if (invalidInputs.length > 0) {
                event.preventDefault();
                event.stopPropagation();
                
                // Scroll to the first invalid input
                invalidInputs[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Show alert
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                errorAlert.role = 'alert';
                errorAlert.innerHTML = `
                    <strong>Invalid hour format!</strong> Please use comma-separated hours (0-23).
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                document.querySelector('.card-body').insertBefore(errorAlert, document.querySelector('.card-body').firstChild);
            }
        });
    });

