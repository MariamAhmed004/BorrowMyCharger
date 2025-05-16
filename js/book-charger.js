

// DOM elements
const bookingForm = document.getElementById('bookingForm');
const bookButton = document.getElementById('book-button');
const timeOptionsContainer = document.getElementById('time-options');
const selectedTimeInput = document.getElementById('selected-time');
const selectedDateInput = document.getElementById('selected-date');

// Modal elements
const successModal = document.getElementById('bookingSuccessModal');
const errorModal = document.getElementById('bookingErrorModal');
const viewBookingsBtn = document.getElementById('viewBookingsBtn');
const browsemoreBtn = document.getElementById('browsemoreBtn');
const tryAgainBtn = document.getElementById('tryAgainBtn');

/**
 * Shows time options based on the selected day
 */
function showTimeOptions() {
    const selectedDay = document.getElementById('availability-date').value;
    
    // Get the corresponding date for the selected day
    const selectedDate = selectedDay ? weekDates[selectedDay].display : '';
    
    // Update the selected date hidden input
    selectedDateInput.value = selectedDate;

    // Reset the button to disabled and clear the selected time
    bookButton.disabled = true;
    selectedTimeInput.value = '';
    
    // Clear previous time options
    timeOptionsContainer.innerHTML = '';
    
    // Check if the selected day has any available times
    if (selectedDay && availableTimes[selectedDay] && availableTimes[selectedDay].length > 0) {
        // Populate available times for the selected day as buttons
        availableTimes[selectedDay].forEach(time => {
            const timeButton = document.createElement('div');
            timeButton.className = 'time-option';
            
            // Format the time to be more readable (e.g., "14:00:00" to "2:00 PM")
            const timeObj = new Date(`1970-01-01T${time}`);
            const formattedTime = timeObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            timeButton.textContent = formattedTime;
            timeButton.dataset.time = time;
            timeButton.dataset.formattedTime = formattedTime;
            
            // Add click event to select time
            timeButton.addEventListener('click', function() {
                // Remove selected class from all time options
                document.querySelectorAll('.time-option').forEach(el => {
                    el.classList.remove('selected');
                });
                
                // Add selected class to the clicked option
                this.classList.add('selected');
                
                // Store the selected time
                selectedTimeInput.value = this.dataset.time;
                
                // Enable the book button
                bookButton.disabled = false;
            });
            
            timeOptionsContainer.appendChild(timeButton);
        });
    } else if (selectedDay) {
        // Show a message if no times are available
        const noTimesMessage = document.createElement('div');
        noTimesMessage.className = 'alert alert-warning';
        noTimesMessage.textContent = 'No available time slots for this day.';
        timeOptionsContainer.appendChild(noTimesMessage);
    }
}

/**
 * Initialize the booking form functionality
 */
function initBookingForm() {
    if (bookButton) {
        bookButton.addEventListener('click', handleBookingSubmission);
    }
}

/**
 * Handles the booking form submission
 */
function handleBookingSubmission() {
    const userId = bookingForm.querySelector('[name="user_id"]').value;
    const chargePointId = bookingForm.querySelector('[name="charge_point_id"]').value;
    const selectedDate = selectedDateInput.value;
    const selectedTime = selectedTimeInput.value;

    if (!selectedDate || !selectedTime) {
        alert('Please select both date and time.');
        return;
    }

    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('charge_point_id', chargePointId);
    formData.append('selected_date', selectedDate);
    formData.append('selected_time', selectedTime);

    fetch('book-charger.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessModal(selectedDate, selectedTime);
        } else {
            showErrorModal(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorModal('There was an error processing your booking. Please try again.');
    });
}

/**
 * Shows the success modal with booking details
 * @param {string} selectedDate - The selected booking date
 * @param {string} selectedTime - The selected booking time
 */
function showSuccessModal(selectedDate, selectedTime) {
    const selectedTimeEl = document.querySelector('.time-option.selected');
    const formattedTime = selectedTimeEl ? selectedTimeEl.dataset.formattedTime : '';
    
    document.getElementById('modal-date').textContent = selectedDate;
    document.getElementById('modal-time').textContent = formattedTime;
    document.getElementById('modal-location').textContent = 
        `${chargerLocation.houseNumber}, ${chargerLocation.streetName}, ${chargerLocation.city}`;
    
    // Show the modal using Bootstrap's modal
    const bsSuccessModal = new bootstrap.Modal(successModal);
    bsSuccessModal.show();
}

/**
 * Shows the error modal with a custom message
 * @param {string} message - The error message to display
 */
function showErrorModal(message) {
    document.getElementById('error-message').textContent = message || 
        'This time slot has been booked by another user. Please select another time.';
    
    // Show the modal using Bootstrap's modal
    const bsErrorModal = new bootstrap.Modal(errorModal);
    bsErrorModal.show();
}

/**
 * Initialize the Leaflet Map
 */
function initMap() {
    const { lat, lng, address } = chargerLocation;

    const map = L.map('map').setView([lat, lng], 15);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Add marker for the charging point
    const marker = L.marker([lat, lng]).addTo(map)
        .bindPopup("<b>Charge Point</b><br>" + address)
        .openPopup();
}

/**
 * Set up modal button event listeners
 */
function setupModalEvents() {
    // Success modal events
    viewBookingsBtn.addEventListener('click', function() {
        window.location.href = 'booking-history.php';
    });
    
    browsemoreBtn.addEventListener('click', function() {
        window.location.href = 'browse-charger.php';
    });
    
    // Error modal events
    tryAgainBtn.addEventListener('click', function() {
        window.location.reload();
    });
}

// When DOM is fully loaded, initialize everything
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    initBookingForm();
    setupModalEvents();
});