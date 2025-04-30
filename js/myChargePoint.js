/**
 * MyChargePoint JavaScript - Vanilla JS implementation (No jQuery)
 * 
 * This script handles the charge point management UI functionality including:
 * - Modal dialogs
 * - Form submission
 * - Validation
 * - Maps
 * - AJAX requests
 */

// Initialize when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // DOM Element References
    const modal = document.getElementById('chargePointModal');
    const addBtn = document.getElementById('addChargePointBtn');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('chargePointForm');
    const formErrors = document.getElementById('formErrors');
    const editBtn = document.querySelector('.edit-button');
    const deleteBtn = document.querySelector('.delete-button');
    const daysCheckboxes = document.querySelectorAll('input[name="availableDays"]');
    const timePickersContainer = document.getElementById('timePickersContainer');
    
    // Map reference
    let map = null;
    let marker = null;
    let isEditMode = false;
    
    // Store selected times for each day to preserve them when toggling days
    let selectedTimesCache = {};
    
    // Initialize the map if details are shown
    if (document.getElementById('chargePointDetails').style.display !== 'none') {
        initMap();
    }
    
    // Event Listeners
    if (addBtn) {
        addBtn.addEventListener('click', openModal);
    }
    
    closeBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Add event listeners for city and block field changes
    document.getElementById('city').addEventListener('change', updateCoordinates);
    document.getElementById('block').addEventListener('change', updateCoordinates);
    
    // Handle form submission
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (validateForm()) {
            submitForm();
        }
    });
    
    // Edit button click handler
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            isEditMode = true;
            openModal(); // Open modal first so user sees something is happening
            setTimeout(() => loadChargePointData(), 100); // Short delay to ensure modal is open
        });
    }
    
    // Delete button click handler
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this charge point?')) {
                deleteChargePoint();
            }
        });
    }
    
    // Handle day checkbox changes
    daysCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // When a day is selected or deselected, update the time pickers
            updateTimePickers(true); // Pass true to preserve existing selections
        });
    });
    
    // Initial setup
    updateTimePickers(false);
    
    /**
     * Open the modal dialog
     */
    function openModal() {
        resetForm();
        modal.style.display = 'block';
        
        // Update form button text based on mode
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.textContent = isEditMode ? 'Update' : 'Add';
        
        // Initialize the cache for selected times
        selectedTimesCache = {};
    }
    
    /**
     * Close the modal dialog
     */
    function closeModal() {
        modal.style.display = 'none';
        isEditMode = false;
        resetForm();
        // Clear the cache when closing the modal
        selectedTimesCache = {};
    }
    
    /**
     * Reset the form fields and errors
     */
    function resetForm() {
        if (!isEditMode) {
            form.reset();
            document.getElementById('chargePointId').value = '';
            document.getElementById('addressId').value = '';
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
        }
        
        // Hide all error messages
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(message => {
            message.style.display = 'none';
        });
        
        // Remove input error highlighting
        const inputs = form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('input-error');
        });
        
        formErrors.style.display = 'none';
        formErrors.textContent = '';
    }
    
    /**
     * Validate the form inputs
     * @return {boolean} True if form is valid
     */
    function validateForm() {
        let isValid = true;
        formErrors.style.display = 'none';
        formErrors.textContent = '';
        
        // Reset error states
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(message => {
            message.style.display = 'none';
        });
        
        const inputs = form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('input-error');
        });
        
        // Validate home number
        const home = document.getElementById('home');
        if (!home.value.trim() || isNaN(parseInt(home.value))) {
            document.getElementById('homeError').style.display = 'block';
            home.classList.add('input-error');
            isValid = false;
        }
        
        // Validate block
        const block = document.getElementById('block');
        if (!block.value.trim() || isNaN(parseInt(block.value))) {
            document.getElementById('blockError').style.display = 'block';
            block.classList.add('input-error');
            isValid = false;
        }
        
        // Validate road
        const road = document.getElementById('road');
        if (!road.value.trim() || isNaN(parseInt(road.value))) {
            document.getElementById('roadError').style.display = 'block';
            road.classList.add('input-error');
            isValid = false;
        }
        
        // Validate city
        const city = document.getElementById('city');
        if (!city.value) {
            document.getElementById('cityError').style.display = 'block';
            city.classList.add('input-error');
            isValid = false;
        }
        
        // Validate cost
        const cost = document.getElementById('cost');
        if (!cost.value.trim() || isNaN(parseFloat(cost.value)) || parseFloat(cost.value) <= 0) {
            document.getElementById('costError').style.display = 'block';
            cost.classList.add('input-error');
            isValid = false;
        }
        
        // Check if image is required (only for new charge points)
        const imageUpload = document.getElementById('imageUpload');
        if (!isEditMode && (!imageUpload.files || imageUpload.files.length === 0)) {
            document.getElementById('imageError').style.display = 'block';
            imageUpload.classList.add('input-error');
            isValid = false;
        }
        
        // Validate days selection
        let anyDaySelected = false;
        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                anyDaySelected = true;
            }
        });
        
        if (!anyDaySelected) {
            formErrors.textContent = 'Please select at least one day of availability';
            formErrors.style.display = 'block';
            isValid = false;
        }
        
        // Validate time selections for each selected day
        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const day = checkbox.value;
                const timeContainer = document.getElementById(`timeContainer_${day}`);
                
                if (timeContainer) {
                    const selectedTimes = timeContainer.querySelectorAll('.time-button.active');
                    if (selectedTimes.length === 0) {
                        if (formErrors.textContent) {
                            formErrors.textContent += `\nPlease select at least one time slot for ${day}`;
                        } else {
                            formErrors.textContent = `Please select at least one time slot for ${day}`;
                            formErrors.style.display = 'block';
                        }
                        isValid = false;
                    }
                }
            }
        });
        
        return isValid;
    }
    
    /**
     * Submit the form data via AJAX
     */
    function submitForm() {
        // Prepare form data
        const formData = new FormData(form);
        
        // Add action based on edit mode
        formData.append('action', isEditMode ? 'update' : 'add');
        
        // Collect day and time selections
        const selectedDays = [];
        const times = {};
        
        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const day = checkbox.value;
                selectedDays.push(day);
                
                // Collect selected times for this day
                const timeButtons = document.querySelectorAll(`#timeContainer_${day} .time-button.active`);
                const dayTimes = [];
                
                timeButtons.forEach(button => {
                    dayTimes.push(button.getAttribute('data-time'));
                });
                
                times[day] = dayTimes;
            }
        });
        
        // Add days and times data to form
        selectedDays.forEach(day => {
            formData.append('days[]', day);
        });
        
        // Add times data as a nested array
        for (const day in times) {
            times[day].forEach(time => {
                formData.append(`times[${day}][]`, time);
            });
        }
        
        // Create and send the request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'my-charge-point.php', true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Success - close modal and refresh the page
                        alert(response.message);
                        window.location.reload();
                    } else {
                        // Error - display message
                        formErrors.textContent = response.message || 'An error occurred while saving the charge point';
                        formErrors.style.display = 'block';
                    }
                } catch (e) {
                    formErrors.textContent = 'An error occurred while processing the response';
                    formErrors.style.display = 'block';
                }
            } else {
                formErrors.textContent = 'Server error: ' + xhr.status;
                formErrors.style.display = 'block';
            }
        };
        
        xhr.onerror = function() {
            formErrors.textContent = 'Connection error. Please try again later.';
            formErrors.style.display = 'block';
        };
        
        xhr.send(formData);
    }
    
    /**
     * Load charge point data for editing
     */
    function loadChargePointData() {
        // First try to get the chargePointId from the button's data attribute
        let chargePointId = editBtn.getAttribute('data-charge-point-id');
        
        // If not found, try to get it from the details container
        if (!chargePointId) {
            chargePointId = document.getElementById('chargePointDetails').getAttribute('data-charge-point-id');
        }
        
        // If still not found, look for hidden input that might contain it
        if (!chargePointId) {
            const hiddenIdField = document.querySelector('input[name="charge_point_id"]');
            if (hiddenIdField) {
                chargePointId = hiddenIdField.value;
            }
        }
        
        if (!chargePointId) {
            alert('No charge point ID found. Please try again or contact support.');
            return;
        }
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'my-charge-point.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success && response.chargePoint) {
                        populateForm(response.chargePoint);
                    } else {
                        alert('Failed to load charge point data: ' + (response.message || 'Unknown error'));
                    }
                } catch (e) {
                    console.error('JSON parsing error:', e);
                    alert('Error processing the response: ' + e.message);
                }
            } else {
                alert('Server error: ' + xhr.status);
            }
        };
        
        xhr.onerror = function() {
            alert('Connection error. Please try again later.');
        };
        
        // Send the chargePointId parameter to the request
        xhr.send(`action=getChargePoint&chargePointId=${chargePointId}`);
    }
    
    /**
     * Populate form with charge point data
     * @param {Object} data The charge point data
     */
    function populateForm(data) {
        console.log('Populating form with data:', data);
        
        // Clear the time selection cache
        selectedTimesCache = {};
        
        // Set basic fields
        const idField = document.getElementById('chargePointId');
        if (idField) idField.value = data.charge_point_id || '';
        
        const addressIdField = document.getElementById('addressId');
        if (addressIdField) addressIdField.value = data.charge_point_address_id || '';
        
        // Handle different property name variations that might come from the server
        const homeField = document.getElementById('home');
        if (homeField) homeField.value = data.house_number || data.home || data.houseNumber || '';
        
        const roadField = document.getElementById('road');
        if (roadField) roadField.value = data.road || data.roadNumber || '';
        
        const blockField = document.getElementById('block');
        if (blockField) blockField.value = data.block || data.blockNumber || '';
        
        const cityField = document.getElementById('city');
        if (cityField) {
            // Handle different property name variations
            cityField.value = data.city_id || data.cityId || data.city || '';
            
            // If the city is provided as a string but the select expects an ID
            if (cityField.value === '' && data.city_name) {
                // Try to find the option with matching text
                Array.from(cityField.options).forEach(option => {
                    if (option.textContent === data.city_name) {
                        cityField.value = option.value;
                    }
                });
            }
        }
        
        const costField = document.getElementById('cost');
        if (costField) costField.value = data.price_per_kwh || data.cost || data.pricePerKwh || '';
        
        // Set coordinates if available (handle multiple possible property names)
        const latField = document.getElementById('latitude');
        const lngField = document.getElementById('longitude');
        
        const lat = data.latitude || data.lat;
        const lng = data.longitude || data.lng || data.long;
        
        if (lat && lng && latField && lngField) {
            latField.value = lat;
            lngField.value = lng;
        } else {
            // Generate coordinates based on city and block
            setTimeout(updateCoordinates, 100); // Small delay to ensure fields are set first
        }
        
        // Set availability days and times
        // First, determine the property name that contains the availability data
        let availabilityData = null;
        
        if (Array.isArray(data.availabilityDays)) {
            availabilityData = data.availabilityDays;
        } else if (Array.isArray(data.availability)) {
            availabilityData = data.availability;
        } else if (Array.isArray(data.days)) {
            availabilityData = data.days;
        } else if (typeof data.availability === 'object' && data.availability !== null) {
            // Convert object to array if needed
            availabilityData = Object.keys(data.availability).map(day => ({
                day_of_week: day,
                times: data.availability[day]
            }));
        }
        
        if (availabilityData && availabilityData.length > 0) {
            // Uncheck all days first
            daysCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check selected days and cache time selections
            availabilityData.forEach(dayData => {
                // Handle different property name variations
                const day = dayData.day_of_week || dayData.day || dayData.dayOfWeek;
                if (!day) return;
                
                const checkbox = document.querySelector(`input[name="availableDays"][value="${day}"]`);
                
                if (checkbox) {
                    checkbox.checked = true;
                    
                    // Cache this day's time selections
                    // Handle different property name variations
                    const times = dayData.times || dayData.timeSlots || dayData.availableTimes;
                    
                    if (Array.isArray(times) && times.length > 0) {
                        selectedTimesCache[day] = times;
                    }
                }
            });
            
            // Update time pickers with preserved selections
            updateTimePickers(true);
        }
        
        // Log success message
        console.log('Form populated successfully with charge point data');
    }
    
    /**
     * Delete the charge point
     */
    function deleteChargePoint() {
        // Try multiple places to find the chargePointId
        let chargePointId = null;
        
        // First check if delete button has the ID
        if (deleteBtn.hasAttribute('data-charge-point-id')) {
            chargePointId = deleteBtn.getAttribute('data-charge-point-id');
        } 
        
        // If not found, try the details container
        if (!chargePointId) {
            const detailsElement = document.getElementById('chargePointDetails');
            if (detailsElement && detailsElement.hasAttribute('data-charge-point-id')) {
                chargePointId = detailsElement.getAttribute('data-charge-point-id');
            }
        }
        
        // Try hidden form field as another option
        if (!chargePointId) {
            const hiddenField = document.querySelector('input[name="charge_point_id"], #chargePointId');
            if (hiddenField && hiddenField.value) {
                chargePointId = hiddenField.value;
            }
        }
        
        // If we still don't have an ID, look through any data attributes that might contain it
        if (!chargePointId) {
            const possibleElements = document.querySelectorAll('[data-id], [data-charge-point-id], [data-chargepoint-id]');
            for (const element of possibleElements) {
                if (element.hasAttribute('data-id')) {
                    chargePointId = element.getAttribute('data-id');
                    break;
                } else if (element.hasAttribute('data-charge-point-id')) {
                    chargePointId = element.getAttribute('data-charge-point-id');
                    break;
                } else if (element.hasAttribute('data-chargepoint-id')) {
                    chargePointId = element.getAttribute('data-chargepoint-id');
                    break;
                }
            }
        }
        
        if (!chargePointId) {
            alert('No charge point ID found. Please try again or contact support.');
            console.error('Delete operation failed: No charge point ID was found in the DOM');
            return;
        }
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'my-charge-point.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        alert(response.message);
                        // Redirect to the listing page
                        window.location.href = response.redirect || 'my-charge-point.php';
                    } else {
                        alert(response.message || 'Failed to delete charge point');
                        console.error('Delete operation failed:', response);
                    }
                } catch (e) {
                    alert('Error processing the response');
                    console.error('JSON parsing error:', e, xhr.responseText);
                }
            } else {
                alert('Server error: ' + xhr.status);
                console.error('Server error during delete:', xhr.status, xhr.responseText);
            }
        };
        
        xhr.onerror = function() {
            alert('Connection error. Please try again later.');
            console.error('Network error during delete operation');
        };
        
        console.log('Sending delete request for charge point ID:', chargePointId);
        xhr.send(`action=delete&chargePointId=${chargePointId}`);
    }
    
    /**
     * Update the time pickers based on selected days
     * @param {boolean} preserveSelections Whether to preserve existing time selections
     */
    function updateTimePickers(preserveSelections = false) {
        // Before updating, save current selections to cache if preserveSelections is true
        if (preserveSelections) {
            daysCheckboxes.forEach(checkbox => {
                const day = checkbox.value;
                const timeContainer = document.getElementById(`timeContainer_${day}`);
                
                if (timeContainer) {
                    const selectedButtons = timeContainer.querySelectorAll('.time-button.active');
                    if (selectedButtons.length > 0) {
                        // Save the selected times for this day
                        if (!selectedTimesCache[day]) {
                            selectedTimesCache[day] = [];
                        }
                        
                        selectedButtons.forEach(button => {
                            const time = button.getAttribute('data-time');
                            if (!selectedTimesCache[day].includes(time)) {
                                selectedTimesCache[day].push(time);
                            }
                        });
                    }
                }
            });
        }
        
        // Clear existing time pickers
        timePickersContainer.innerHTML = '';
        
        // Generate time options - half hour increments
        const timeOptions = [];
        for (let hour = 0; hour < 24; hour++) {
            const hourText = hour.toString().padStart(2, '0');
            timeOptions.push(`${hourText}:00`);
            timeOptions.push(`${hourText}:30`);
        }
        
        // Create time pickers for each selected day
        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const day = checkbox.value;
                
                // Create container for this day
                const dayContainer = document.createElement('div');
                dayContainer.id = `timeContainer_${day}`;
                dayContainer.className = 'time-picker-container';
                
                // Add day header
                const dayHeader = document.createElement('h5');
                dayHeader.textContent = `${day} Available Times`;
                dayContainer.appendChild(dayHeader);
                
                // Add time grid
                const timeGrid = document.createElement('div');
                timeGrid.className = 'time-grid';
                
                // Add time buttons
                timeOptions.forEach(time => {
                    const timeButton = document.createElement('div');
                    timeButton.className = 'time-button';
                    timeButton.setAttribute('data-time', time);
                    timeButton.textContent = time;
                    
                    // If preserving selections and this time was previously selected for this day,
                    // add the active class
                    if (preserveSelections && 
                        selectedTimesCache[day] && 
                        selectedTimesCache[day].includes(time)) {
                        timeButton.classList.add('active');
                    }
                    
                    // Add click event to toggle selection
                    timeButton.addEventListener('click', function() {
                        this.classList.toggle('active');
                        
                        // Update cache when time is selected/deselected
                        const timeValue = this.getAttribute('data-time');
                        if (!selectedTimesCache[day]) {
                            selectedTimesCache[day] = [];
                        }
                        
                        if (this.classList.contains('active')) {
                            // Add to cache if not already there
                            if (!selectedTimesCache[day].includes(timeValue)) {
                                selectedTimesCache[day].push(timeValue);
                            }
                        } else {
                            // Remove from cache
                            const index = selectedTimesCache[day].indexOf(timeValue);
                            if (index !== -1) {
                                selectedTimesCache[day].splice(index, 1);
                            }
                        }
                    });
                    
                    timeGrid.appendChild(timeButton);
                });
                
                dayContainer.appendChild(timeGrid);
                timePickersContainer.appendChild(dayContainer);
            }
        });
    }
    
    /**
     * Initialize the map
     */
    function initMap() {
        const mapElement = document.getElementById('leetcodeMap');
        if (!mapElement) return;
        
        try {
            // Get coordinates from HTML attributes or default to Bahrain
            const latElement = document.querySelector('#chargePointDetails [data-latitude]');
            const lngElement = document.querySelector('#chargePointDetails [data-longitude]');
            
            // Default to Bahrain coordinates
            let latitude = 26.0667;
            let longitude = 50.5577;
            
            if (latElement && lngElement) {
                latitude = parseFloat(latElement.getAttribute('data-latitude')) || latitude;
                longitude = parseFloat(lngElement.getAttribute('data-longitude')) || longitude;
            }
            
            // Initialize map
            map = L.map('leetcodeMap').setView([latitude, longitude], 15);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            
            // Add marker for the charge point
            marker = L.marker([latitude, longitude]).addTo(map);
            
            // If coordinates are stored in hidden fields, use those
            const latField = document.getElementById('latitude');
            const lngField = document.getElementById('longitude');
            
            if (latField && latField.value && lngField && lngField.value) {
                const lat = parseFloat(latField.value);
                const lng = parseFloat(lngField.value);
                
                if (!isNaN(lat) && !isNaN(lng)) {
                    map.setView([lat, lng], 15);
                    marker.setLatLng([lat, lng]);
                }
            }
            
            // Allow map clicks to set location in add/edit mode
            map.on('click', function(e) {
                if (modal.style.display === 'block') {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    
                    // Update the form fields
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lng;
                    
                    // Update marker position
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng]).addTo(map);
                    }
                }
            });
        } catch (e) {
            console.error("Map initialization error:", e);
        }
    }
    
    /**
     * Update coordinates based on city and block selection
     * This function is automatically called when city or block fields change
     */
    function updateCoordinates() {
        const citySelect = document.getElementById('city');
        const blockInput = document.getElementById('block');
        
        const cityId = citySelect.value;
        const block = blockInput.value;
        
        // Only proceed if both city and block are selected/entered
        if (!cityId || !block) {
            return;
        }
        
        // Define coordinate mappings for cities and blocks in Bahrain
        // These are example coordinates - you should replace with actual coordinates
        const cityCoordinates = {
            // Example format: cityId: { baseLatitude, baseLongitude, blockOffsets: { blockNumber: [latOffset, lngOffset] } }
            1: { // Manama
                baseLatitude: 26.2285,
                baseLongitude: 50.5860,
                blockOffsets: {
                    301: [0.005, 0.003],
                    302: [0.008, 0.005],
                    303: [0.004, 0.007],
                    304: [0.002, 0.004],
                    305: [0.006, 0.002],
                    // Add more blocks as needed
                }
            },
            2: { // Riffa
                baseLatitude: 26.1345,
                baseLongitude: 50.5547,
                blockOffsets: {
                    901: [0.005, 0.008],
                    902: [0.007, 0.004],
                    903: [0.003, 0.006],
                    904: [0.009, 0.002],
                    905: [0.004, 0.005],
                    // Add more blocks as needed
                }
            },
            3: { // Muharraq
                baseLatitude: 26.2572,
                baseLongitude: 50.6145,
                blockOffsets: {
                    201: [0.004, 0.002],
                    202: [0.006, 0.003],
                    203: [0.002, 0.005],
                    204: [0.003, 0.007],
                    205: [0.005, 0.004],
                    // Add more blocks as needed
                }
            },
            // Add more cities as needed
        };
        
        // Calculate coordinates based on city and block
        const cityData = cityCoordinates[cityId];
        if (cityData) {
            let latitude = cityData.baseLatitude;
            let longitude = cityData.baseLongitude;
            
            // Apply block offset if available
            if (cityData.blockOffsets && cityData.blockOffsets[block]) {
                latitude += cityData.blockOffsets[block][0];
                longitude += cityData.blockOffsets[block][1];
            } else {
                // If specific block not found, generate a small random offset
                // This ensures different coordinates for different blocks in the same city
                const blockNum = parseInt(block);
                if (!isNaN(blockNum)) {
                    // Use block number to generate deterministic offset
                    const latOffset = (blockNum % 10) * 0.001;
                    const lngOffset = ((blockNum % 15) + 5) * 0.001;
                    
                    latitude += latOffset;
                    longitude += lngOffset;
                }
            }
            
            // Update form fields
            document.getElementById('latitude').value = latitude.toFixed(6);
            document.getElementById('longitude').value = longitude.toFixed(6);
            
            // Update marker on map if map is already initialized
            if (map && marker) {
                marker.setLatLng([latitude, longitude]);
                map.setView([latitude, longitude], 15);
            }
        }
    }
});