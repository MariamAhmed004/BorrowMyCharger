document.addEventListener('DOMContentLoaded', function() {
    // ================= TIME SELECTION FUNCTIONALITY =================
    // Constants for time options
    const TIME_OPTIONS = [
        '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', 
        '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', 
        '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', 
        '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
    ];
    
    // Days of the week for creating time picker containers
    const DAYS_OF_WEEK = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ];
    
    // Get the container elements
    const daysContainer = document.querySelector('.day-checkbox-container');
    const timePickersContainer = document.getElementById('timePickersContainer');
    
    // Initialize time pickers for each day
    function initializeTimePickers() {
        // Create a time picker for each day
        DAYS_OF_WEEK.forEach(day => {
            // Create container for this day's time picker
            const timePickerDiv = document.createElement('div');
            timePickerDiv.id = `${day.toLowerCase()}-time-picker`;
            timePickerDiv.className = 'time-picker-container';
            timePickerDiv.style.display = 'none';
            
            // Add heading
            const heading = document.createElement('h5');
            heading.textContent = `${day} Times`;
            timePickerDiv.appendChild(heading);
            
            // Create time selection grid
            const timeGrid = document.createElement('div');
            timeGrid.className = 'time-grid';
            
            // Add time buttons
            TIME_OPTIONS.forEach(time => {
                const timeBtn = document.createElement('button');
                timeBtn.type = 'button';
                timeBtn.className = 'time-button';
                timeBtn.textContent = time;
                timeBtn.dataset.time = time;
                timeBtn.dataset.day = day;
                
                // Add click event
                timeBtn.addEventListener('click', function() {
                    this.classList.toggle('active');
                    updateSelectedTimes(day);
                });
                
                timeGrid.appendChild(timeBtn);
            });
            
            timePickerDiv.appendChild(timeGrid);
            
            // Add hidden input field to store selected times
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `selected_times[${day}]`;
            hiddenInput.id = `selected_times_${day.toLowerCase()}`;
            timePickerDiv.appendChild(hiddenInput);
            
            // Add to the container
            timePickersContainer.appendChild(timePickerDiv);
        });
    }
    
    // Update hidden inputs with selected times
    function updateSelectedTimes(day) {
        const dayContainer = document.getElementById(`${day.toLowerCase()}-time-picker`);
        const selectedButtons = dayContainer.querySelectorAll('.time-button.active');
        const selectedTimes = Array.from(selectedButtons).map(btn => btn.dataset.time);
        
        document.getElementById(`selected_times_${day.toLowerCase()}`).value = selectedTimes.join(',');
    }
    
    // Handle day checkbox changes - FIXED: This was not properly showing/hiding the time pickers
    function handleDayCheckboxChange(event) {
        const day = event.target.value;
        const isChecked = event.target.checked;
        const timePickerContainer = document.getElementById(`${day.toLowerCase()}-time-picker`);
        
        if (timePickerContainer) {
            // Make sure to show/hide the time picker container based on checkbox state
            timePickerContainer.style.display = isChecked ? 'block' : 'none';
            
            // If unchecking, clear the selections
            if (!isChecked) {
                // Clear active states from time buttons
                timePickerContainer.querySelectorAll('.time-button.active').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Clear hidden input value
                const hiddenInput = document.getElementById(`selected_times_${day.toLowerCase()}`);
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            }
        }
    }
    
    // Add event listeners to day checkboxes
    function addDayCheckboxListeners() {
        const dayCheckboxes = document.querySelectorAll('input[name="availableDays"]');
        dayCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', handleDayCheckboxChange);
        });
    }
    
    function populateTimeSelections(chargePointData) {
        // Reset all selections first
        document.querySelectorAll('input[name="availableDays"]').forEach(checkbox => {
            checkbox.checked = false;
            const day = checkbox.value;
            const timePickerContainer = document.getElementById(`${day.toLowerCase()}-time-picker`);
            if (timePickerContainer) {
                timePickerContainer.style.display = 'none';
                // Clear active states from time buttons
                timePickerContainer.querySelectorAll('.time-button.active').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Clear hidden input value
                const hiddenInput = document.getElementById(`selected_times_${day.toLowerCase()}`);
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            }
        });
        
        // If no data or no availability, exit early
        if (!chargePointData || !chargePointData.availability || Object.keys(chargePointData.availability).length === 0) {
            console.log('No availability data to populate', chargePointData);
            return;
        }
        
        console.log('Populating with availability data:', chargePointData.availability);
        
        // For each day in the availability data
        Object.keys(chargePointData.availability).forEach(day => {
            // Check the day checkbox
            const dayCheckbox = document.querySelector(`input[name="availableDays"][value="${day}"]`);
            if (dayCheckbox) {
                dayCheckbox.checked = true;
                
                // Show the time picker
                const timePickerContainer = document.getElementById(`${day.toLowerCase()}-time-picker`);
                if (timePickerContainer) {
                    timePickerContainer.style.display = 'block';
                    
                    // Get times for this day
                    const times = chargePointData.availability[day];
                    
                    // Select the times
                    if (Array.isArray(times)) {
                        times.forEach(time => {
                            const timeButton = timePickerContainer.querySelector(`.time-button[data-time="${time}"]`);
                            if (timeButton) {
                                timeButton.classList.add('active');
                            } else {
                                console.warn(`Time button for ${time} not found`);
                            }
                        });
                        
                        // Update the hidden input
                        updateSelectedTimes(day);
                    } else {
                        console.warn(`Times for ${day} is not an array:`, times);
                    }
                } else {
                    console.warn(`Time picker container for ${day} not found`);
                }
            } else {
                console.warn(`Checkbox for day ${day} not found`);
            }
        });
    }
    
    // Add the selected days and times to the form
    function addDaysAndTimesToForm() {
        const form = document.getElementById('chargePointForm');
        const dayCheckboxes = document.querySelectorAll('input[name="availableDays"]:checked');
        
        dayCheckboxes.forEach(checkbox => {
            const day = checkbox.value;
            const selectedTimesInput = document.getElementById(`selected_times_${day.toLowerCase()}`);
            
            if (selectedTimesInput && selectedTimesInput.value) {
                // Create a new input for each day
                const dayInput = document.createElement('input');
                dayInput.type = 'hidden';
                dayInput.name = `selected_days[]`;
                dayInput.value = day;
                form.appendChild(dayInput);
                
                // Create inputs for each selected time
                const times = selectedTimesInput.value.split(',');
                times.forEach(time => {
                    const timeInput = document.createElement('input');
                    timeInput.type = 'hidden';
                    timeInput.name = `day_times[${day}][]`;
                    timeInput.value = time;
                    form.appendChild(timeInput);
                });
            }
        });
    }
    
    // ================= MAPS & CHARGE POINT FUNCTIONALITY =================
    // Configuration
    const DEFAULT_LOCATION = { 
        lat: 26.2285,  // Bahrain coordinates
        lng: 50.5860 
    };
    let modalMap, modalMarker, mainMap, mainMarker;

    // DOM Elements
    const modal = new bootstrap.Modal(document.getElementById('chargePointModal'));
    const addChargePointBtn = document.getElementById('addChargePointBtn');
    const chargePointForm = document.getElementById('chargePointForm');
    const modalMapContainer = document.getElementById('mapContainer');
    const mainMapContainer = document.getElementById('mainMapContainer');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const saveChargePointBtn = document.getElementById('saveChargePointBtn');
    const editButtons = document.querySelectorAll('.edit-charge-point');
    const deleteButtons = document.querySelectorAll('.delete-charge-point');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const chargePointPictureInput = document.getElementById('charge_point_picture');

    // Initialize map in the modal
    function initModalMap(initialLocation = DEFAULT_LOCATION) {
        // Clear previous map container
        modalMapContainer.innerHTML = '';
        
        // Create a new div for the Leaflet map
        const mapDiv = document.createElement('div');
        mapDiv.style.height = '400px'; // Set height for the map
        modalMapContainer.appendChild(mapDiv);
        
        // Create map
        modalMap = L.map(mapDiv).setView([initialLocation.lat, initialLocation.lng], 10);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(modalMap);
        
        // Create marker
        modalMarker = L.marker([initialLocation.lat, initialLocation.lng], {
            draggable: true
        }).addTo(modalMap);
        
        // Add event listeners for marker
        modalMarker.on('dragend', updateMarkerLocation);
        modalMap.on('click', function(event) {
            modalMarker.setLatLng(event.latlng);
            updateMarkerLocation({ latlng: event.latlng });
        });
        
        // Update initial location
        updateMarkerLocation({ latlng: modalMarker.getLatLng() });
    }

    // Initialize map on the main page
    function initMainMap(location = DEFAULT_LOCATION) {
        if (!mainMapContainer) {
            return;
        }
        
        // Create map
        mainMap = L.map(mainMapContainer).setView([location.lat, location.lng], 14);
        
        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(mainMap);
        
        // Create marker
        mainMarker = L.marker([location.lat, location.lng]).addTo(mainMap);
    }

    // Update inputs based on marker location
    function updateMarkerLocation(event) {
        const position = event.latlng || modalMarker.getLatLng();
        latitudeInput.value = position.lat;
        longitudeInput.value = position.lng;
    }

    // Handle image preview
    function handleImagePreview(file) {
        imagePreviewContainer.innerHTML = '';
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.classList.add('img-fluid', 'mt-2');
                imagePreviewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    }

    if (chargePointPictureInput) {
        chargePointPictureInput.addEventListener('change', (e) => {
            handleImagePreview(e.target.files[0]);
        });
    }

    // Modify populateFormFields to transform data structure
    function populateFormFields(chargePointData) {
        // Reset form and remove any existing error classes
        chargePointForm.reset();
        chargePointForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        imagePreviewContainer.innerHTML = '';

        document.getElementById('chargePointId').value = chargePointData.charge_point_id;
        document.getElementById('streetName').value = chargePointData.streetName;
        document.getElementById('city_id').value = chargePointData.city_id;
        document.getElementById('postcode').value = chargePointData.postcode;
        document.getElementById('house_number').value = chargePointData.house_number;
        document.getElementById('road').value = chargePointData.road;
        document.getElementById('block').value = chargePointData.block;
        document.getElementById('price_per_kwh').value = chargePointData.price_per_kwh;
        document.getElementById('existing_picture_url').value = chargePointData.charge_point_picture_url;
        
        // Preview existing image
        if (chargePointData.charge_point_picture_url) {
            const img = document.createElement('img');
            img.src = chargePointData.charge_point_picture_url;
            img.classList.add('img-fluid', 'mt-2');
            imagePreviewContainer.appendChild(img);
        }

        // Set map location
        const mapLocation = { 
            lat: parseFloat(chargePointData.latitude), 
            lng: parseFloat(chargePointData.longitude) 
        };
        initModalMap(mapLocation);
        
        // Process the charge point data to ensure availability is in the right format
        const processedData = processChargePointData(chargePointData);
        
        // Then populate the time selections
        populateTimeSelections(processedData);
    }

    // Populate existing selections when editing a charge point
    function populateAvailabilityDays(availabilityDays) {
        const dayCheckboxes = daysContainer.querySelectorAll('input[name="availableDays"]');
        
        // Reset all checkboxes and time buttons
        dayCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            const day = checkbox.value;
            const timePickerContainer = document.getElementById(`${day.toLowerCase()}-time-picker`);
            timePickerContainer.style.display = 'none';
            timePickerContainer.querySelectorAll('.time-button.active').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`selected_times_${day.toLowerCase()}`).value = '';
        });

        // Populate checkboxes and time buttons
        availabilityDays.forEach(dayData => {
            const checkbox = daysContainer.querySelector(`input[value="${dayData.day_of_week}"]`);
            if (checkbox) {
                checkbox.checked = true;
                const timePickerContainer = document.getElementById(`${dayData.day_of_week.toLowerCase()}-time-picker`);
                timePickerContainer.style.display = 'block';

                // Select the appropriate time buttons
                dayData.times.forEach(time => {
                    const timeButton = timePickerContainer.querySelector(`.time-button[data-time="${time.available_time}"]`);
                    if (timeButton) {
                        timeButton.classList.add('active');
                    }
                });

                // Update hidden input
                updateSelectedTimes(dayData.day_of_week);
            }
        });
    }

    function createTimeInput(day, timeValue) {
        const timeInput = document.createElement('input');
        timeInput.type = 'time';
        timeInput.name = `${day}_time[]`;
        timeInput.value = timeValue;

        const removeBtn = document.createElement('button');
        removeBtn.textContent = 'Remove';
        removeBtn.className = 'btn btn-danger btn-sm ms-2';
        removeBtn.onclick = function() {
            timeInput.parentElement.remove(); // Remove the time input
        };

        const timeDiv = document.createElement('div');
        timeDiv.className = 'time-input-container';
        timeDiv.appendChild(timeInput);
        timeDiv.appendChild(removeBtn);

        return timeDiv;
    }

    function processChargePointData(data) {
        // Create a copy of the original data
        const processedData = {...data};
        
        // If availability data is already in the right format, just ensure it's an object
        if (typeof processedData.availability === 'object' && processedData.availability !== null) {
            return processedData;
        }
        
        // Initialize availability object if it doesn't exist
        processedData.availability = {};
        
        // Convert availability_days to the expected format
        if (Array.isArray(data.availability_days)) {
            data.availability_days.forEach(dayData => {
                if (dayData && dayData.day_of_week) {
                    // Initialize array for this day if it doesn't exist
                    if (!processedData.availability[dayData.day_of_week]) {
                        processedData.availability[dayData.day_of_week] = [];
                    }
                    
                    // Add times to the day's array
                    if (Array.isArray(dayData.times)) {
                        dayData.times.forEach(time => {
                            if (time && time.available_time) {
                                processedData.availability[dayData.day_of_week].push(time.available_time);
                            }
                        });
                    }
                }
            });
        }
        
        return processedData;
    }
    
        /**
     * Fetch homeowners without charge points
     */
   function fetchHomeOwnersWithoutChargePoints() {
    // Show loading indicator
    showAlert('Loading', 'Fetching homeowners data...', 'info');
    
    // Create traditional XHR object
    var xhr = new XMLHttpRequest();
    
    // Configure request
    xhr.open('GET', 'admin-actions.php?action=getHomeOwnersWithoutChargePoints', true);
    
    // Set up event handlers
    xhr.onreadystatechange = function() {
        // Check if request is complete
        if (xhr.readyState === 4) {
            // Remove loading alert
            const loadingAlert = document.querySelector('.alert.alert-info');
            if (loadingAlert) loadingAlert.remove();
            
            if (xhr.status === 200) {
                // Try to parse response as JSON
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        showHomeOwnersModal(data.homeowners);
                    } else {
                        showAlert('Error', data.message || 'Failed to fetch homeowners', 'error');
                    }
                } catch (e) {
                    console.error("Server returned non-JSON response:", xhr.responseText);
                    showAlert('Error', 'Server returned invalid JSON. Check PHP errors.', 'error');
                }
            } else {
                console.error('Server returned status:', xhr.status);
                showAlert('Error', 'Failed to connect to server: ' + xhr.statusText, 'error');
            }
        }
    };
    
    // Handle network errors
    xhr.onerror = function() {
        console.error('Network error occurred');
        showAlert('Error', 'Network error occurred', 'error');
    };
    
    // Send the request
    xhr.send();
}
    
  function showHomeOwnersModal(homeowners) {
    // Remove existing modal if any
    let existingModal = document.getElementById('homeownersModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal structure
    const modal = document.createElement('div');
    modal.id = 'homeownersModal';
    modal.className = 'modal fade';
    modal.tabIndex = '-1';
    modal.setAttribute('aria-labelledby', 'homeownersModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    let modalContent = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="homeownersModalLabel">Select a Homeowner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
    `;
    
    if (homeowners.length === 0) {
        modalContent += `<p class="text-center">No homeowners without charge points found.</p>`;
    } else {
        modalContent += `<div class="list-group">`;
        homeowners.forEach(homeowner => {
            modalContent += `
                <button type="button" class="list-group-item list-group-item-action select-homeowner" 
                        data-user-id="${homeowner.user_id}" 
                        data-user-name="${homeowner.first_name} ${homeowner.last_name}">
                    <strong>${homeowner.first_name} ${homeowner.last_name}</strong>
                    <br>
                    <small>Email: ${homeowner.email} | Phone: ${homeowner.phone_number}</small>
                </button>
            `;
        });
        modalContent += `</div>`;
    }
    
    modalContent += `
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = modalContent;
    document.body.appendChild(modal);
    
    // Initialize Bootstrap modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Add event listeners to homeowner buttons
    document.querySelectorAll('.select-homeowner').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            selectHomeOwner(userId, userName);
        });
    });
}
 function selectHomeOwner(userId, userName) {
    // Validate input
    if (!userId || userId.trim() === '') {
        showAlert('Error', 'Invalid user ID', 'error');
        return;
    }
    
    console.log('Selected homeowner:', userName, 'with ID:', userId);
    
    // Close the homeowners modal
    const homeownersModal = document.getElementById('homeownersModal');
    const bsHomeownersModal = bootstrap.Modal.getInstance(homeownersModal);
    bsHomeownersModal.hide();

    // Wait a bit for the modal to close properly
    setTimeout(() => {
        // Ensure the charge point form exists
        let chargePointForm = document.getElementById('chargePointForm');
        if (!chargePointForm) {
            showAlert('Error', 'Charge point form not found', 'error');
            return;
        }

        // Reset the form
        chargePointForm.reset();

        // Set the selected user ID in a hidden field
        let selectedUserIdField = document.getElementById('selected_user_id');
        if (!selectedUserIdField) {
            selectedUserIdField = document.createElement('input');
            selectedUserIdField.type = 'hidden';
            selectedUserIdField.id = 'selected_user_id';
            selectedUserIdField.name = 'selected_user_id';
            chargePointForm.appendChild(selectedUserIdField);
        }
        selectedUserIdField.value = userId;
        
        // Log to confirm user ID is set
        console.log('User ID set in form:', selectedUserIdField.value);

        // Update the modal title
        let modalTitle = document.getElementById('modalTitle');
        if (modalTitle) {
            modalTitle.textContent = `Add Charge Point for ${userName}`;
        }

        // Show the charge point modal
        const chargePointModal = document.getElementById('chargePointModal');
        if (chargePointModal) {
            const bsChargePointModal = new bootstrap.Modal(chargePointModal);
            bsChargePointModal.show();
        } else {
            showAlert('Error', 'Charge point modal not found', 'error');
        }

        // Remove existing event listeners to avoid duplicates
        chargePointForm.removeEventListener('submit', handleFormSubmission);
        
        // Add event listener for form submission
        chargePointForm.addEventListener('submit', handleFormSubmission);
    }, 300); // Small delay to allow the first modal to close properly
}
    /**
     * Show an alert message to the user
     */
  function showAlert(title, message, type) {
    // Remove existing alerts of the same type
    const existingAlerts = document.querySelectorAll(`.alert.alert-${type === 'error' ? 'danger' : type}`);
    existingAlerts.forEach(alert => alert.remove());
    
    // Create a Bootstrap alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    
    alertDiv.innerHTML = `
        <strong>${title}</strong>: ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert alert at the top of the container
    const container = document.querySelector('.container.mt-5');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
    } else {
        // Fallback if container not found
        document.body.insertBefore(alertDiv, document.body.firstChild);
    }
    
    // Auto remove after 5 seconds (except for info alerts)
    if (type !== 'info') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
}
  // Open modal
function openModal(chargePointData = null) {
    const modalTitle = document.getElementById('modalTitle');
    const chargePointModal = document.getElementById('chargePointModal');
    const modal = chargePointModal ? new bootstrap.Modal(chargePointModal) : null;
    const chargePointForm = document.getElementById('chargePointForm');

    if (chargePointData) {
        // Edit mode
        modalTitle.textContent = 'Edit Charge Point';
        if (typeof populateFormFields === 'function') {
            populateFormFields(chargePointData);
        }
        if (modal) modal.show();
    } else {
        // Add mode
        modalTitle.textContent = 'Add Charge Point';
        fetchHomeOwnersWithoutChargePoints(); // Call to fetch homeowners
        if (chargePointForm) chargePointForm.reset();
        if (typeof initModalMap === 'function') {
            initModalMap();
        }
    }
}

/**
 * Handle form submission using traditional AJAX (XMLHttpRequest)
 */
function handleFormSubmission(e) {
    e.preventDefault();
    console.log('Form submission started');
    
    // Add days and times to the form before submission if that function exists
    if (typeof addDaysAndTimesToForm === 'function') {
        addDaysAndTimesToForm();
    }
    
    // Reset error states
    const chargePointForm = document.getElementById('chargePointForm');
    chargePointForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    
    // Show loading indicator
    showAlert('Processing', 'Submitting form...', 'info');
    
    // Get form data
    const formData = new FormData(chargePointForm);
    const isEditing = formData.get('charge_point_id') ? true : false;
    formData.append('action', isEditing ? 'update' : 'addChargePointForUser');
    
    // Get the selected user ID from the hidden field
    const selectedUserIdField = document.getElementById('selected_user_id');
    if (!isEditing && (!selectedUserIdField || !selectedUserIdField.value)) {
        showAlert('Error', 'No homeowner selected. Please select a homeowner first.', 'error');
        return;
    }
    if(!isEditing){
    // Make sure user_ID is properly set before proceeding
    console.log('Selected user ID:', selectedUserIdField.value);
}
    if(!isEditing){
    // Using the correct parameter name as expected by PHP backend
    formData.append('user_ID', selectedUserIdField.value);
}
  
    
    // Create traditional XHR object
    var xhr = new XMLHttpRequest();
    
    // Configure request
    xhr.open('POST', 'admin-actions.php', true);
    
    // Set up event handlers
    xhr.onreadystatechange = function() {
        // Check if request is complete
        if (xhr.readyState === 4) {
            // Remove loading alert
            const loadingAlert = document.querySelector('.alert.alert-info');
            if (loadingAlert) loadingAlert.remove();
            
            if (xhr.status === 200) {
                // Try to parse response as JSON
                try {
                    console.log('Response text:', xhr.responseText);
                    var result = JSON.parse(xhr.responseText);
                    console.log('Server response:', result);
                    
                    if (result.success) {
                        // Close modal
                        const chargePointModal = document.getElementById('chargePointModal');
                        if (chargePointModal) {
                            const bsModal = bootstrap.Modal.getInstance(chargePointModal);
                            if (bsModal) {
                                bsModal.hide();
                            }
                        }
                        
                        // Show success message
                        showAlert('Success', 'Charge point saved successfully.', 'success');
                        
                        // Reload page after a slight delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Display validation errors if any
                        if (result.errors) {
                            Object.keys(result.errors).forEach(field => {
                                const errorEl = document.getElementById(`${field}-error`);
                                if (errorEl) {
                                    errorEl.textContent = result.errors[field];
                                    const inputEl = document.getElementById(field);
                                    if (inputEl) {
                                        inputEl.classList.add('is-invalid');
                                    }
                                }
                            });
                        }
                        
                        // Display general error message if any
                        if (result.message) {
                            showAlert('Error', result.message, 'error');
                        }
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Server returned non-JSON response:', xhr.responseText);
                    showAlert('Error', 'Server returned invalid JSON. Check PHP errors.', 'error');
                }
            } else {
                console.error('Server returned status:', xhr.status);
                showAlert('Error', 'Server error: ' + xhr.statusText, 'error');
            }
        }
    };
    
    // Handle network errors
    xhr.onerror = function() {
        console.error('Network error occurred');
        showAlert('Error', 'Network error occurred', 'error');
    };
    
    // Send the form data
    xhr.send(formData);
}
    // Delete charge point
    async function handleDeleteChargePoint(chargePointId) {
        if (!confirm('Are you sure you want to delete this charge point?')) return;

        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('charge_point_id', chargePointId);

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                // Remove the charge point from UI or reload
                location.reload();
            } else {
                alert(result.message || 'Failed to delete charge point');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('An error occurred while deleting the charge point.');
        }
    }

    // Initialize main page map if there's a charge point
    function initializeMainPageMap() {
        const chargePointCard = document.querySelector('.charge-point-card');
        if (chargePointCard && mainMapContainer) {
            const chargePointData = JSON.parse(chargePointCard.dataset.details);
            const location = {
                lat: parseFloat(chargePointData.latitude),
                lng: parseFloat(chargePointData.longitude)
            };
            initMainMap(location);
        }
    }

    // ================= EVENT LISTENERS & INITIALIZATION =================
    // Add charge point button
    if (addChargePointBtn) {
        addChargePointBtn.addEventListener('click', () => {
            openModal();
        });
    }

    // Event listener for edit buttons
    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const chargePointCard = btn.closest('.charge-point-card');
            let chargePointData = JSON.parse(chargePointCard.dataset.details);
            openModal(chargePointData);
        });
    });

    // Delete buttons event listeners
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const chargePointCard = btn.closest('.charge-point-card');
            const chargePointId = chargePointCard.dataset.chargePointId;
            handleDeleteChargePoint(chargePointId);
        });
    });

    // Save button event listener
    if (saveChargePointBtn) {
        saveChargePointBtn.addEventListener('click', handleFormSubmission);
    }

    // Load Leaflet CSS and JS
    function loadLeafletResources() {
        return new Promise((resolve, reject) => {
            // Check if Leaflet is already loaded
            if (window.L) {
                resolve();
                return;
            }

            // Load Leaflet CSS
            const cssLink = document.createElement('link');
            cssLink.rel = 'stylesheet';
            cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            cssLink.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            cssLink.crossOrigin = '';
            document.head.appendChild(cssLink);

            // Load Leaflet JS
            const script = document.createElement('script');
            script.src = 'leaflet/leaflet.js';
            script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
            script.crossOrigin = '';
            script.async = true;
            script.onload = () => resolve();
            script.onerror = () => reject(new Error('Leaflet script failed to load'));
            document.head.appendChild(script);
        });
    }

    // Initialize everything
    function init() {
        // Initialize time pickers
        initializeTimePickers();

        // Add day checkbox listeners
        addDayCheckboxListeners();
        
        // Load Leaflet and initialize main map
        loadLeafletResources().then(() => {
            console.log('Leaflet API ready');
            initializeMainPageMap();
        }).catch(error => {
            console.error('Failed to load Leaflet:', error);
            alert('Failed to load map. Please try again later.');
        });
    }

    // Start everything
    init();
});