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
    const closeBtn = modal?.querySelector('.close');
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
    let geocoder = null;
    
    // Store selected times for each day to preserve them when toggling days
    let selectedTimesCache = {};
    
    // Initialize the map if element exists and details are shown
    const detailsElement = document.getElementById('chargePointDetails');
    if (detailsElement && detailsElement.style.display !== 'none') {
        initMap(document.getElementById('leetcodeMap')); // Updated to use the correct ID
    }
    
    // Event Listeners - only add if elements exist
    if (addBtn) {
        addBtn.addEventListener('click', openModal);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (modal && event.target === modal) {
            closeModal();
        }
    });
    
    // Add event listeners for city and block field changes
    const cityField = document.getElementById('city');
    const blockField = document.getElementById('block');
    const roadField = document.getElementById('road');
    
    if (cityField) {
        cityField.addEventListener('change', updateCoordinates);
    }
    
    if (blockField) {
        blockField.addEventListener('change', updateCoordinates);
    }
    
    if (roadField) {
        roadField.addEventListener('change', updateCoordinates);
    }
    
    // Handle form submission
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateForm()) {
                submitForm();
            }
        });
    }
    
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
    
    // Initial setup if time pickers container exists
    if (timePickersContainer) {
        updateTimePickers(false);
    }
    
    /**
     * Calculate and update the coordinates based on city, block, and road selections
     * This function uses a predefined mapping of Bahrain locations to coordinates
     */
    function updateCoordinates() {
        // Get the form field values
        const citySelect = document.getElementById('city');
        const blockField = document.getElementById('block');
        const roadField = document.getElementById('road');
        const latitudeField = document.getElementById('latitude');
        const longitudeField = document.getElementById('longitude');
        
        // If any of the required fields are missing, return
        if (!citySelect || !blockField || !latitudeField || !longitudeField) {
            console.error('Required fields for coordinate calculation are missing');
            return;
        }
        
        // Get values from fields
        const cityId = citySelect.value;
        const block = blockField.value.trim();
        const road = roadField ? roadField.value.trim() : '';
        
        // If city or block is empty, don't continue
        if (!cityId || !block) {
            console.log('City or block field is empty. Cannot calculate coordinates.');
            return;
        }
        
        // Get the city name from the selected option
        const cityName = citySelect.options[citySelect.selectedIndex].text;
        
        console.log(`Calculating coordinates for: City=${cityName} (ID=${cityId}), Block=${block}, Road=${road}`);
        
        // Use the geocoding service to get coordinates
        geocodeAddress(cityName, block, road)
            .then(coordinates => {
                if (coordinates) {
                    // Update form fields
                    latitudeField.value = coordinates.lat;
                    longitudeField.value = coordinates.lng;
                    
                    // Update map if it exists
                    if (map && marker) {
                        map.setView([coordinates.lat, coordinates.lng], 15);
                        marker.setLatLng([coordinates.lat, coordinates.lng]);
                    } else if (map) {
                        // If map exists but marker doesn't, create one
                        marker = L.marker([coordinates.lat, coordinates.lng], {
                            draggable: true
                        }).addTo(map);
                        
                        // Handle marker drag end event
                        marker.on('dragend', function(event) {
                            const position = marker.getLatLng();
                            latitudeField.value = position.lat;
                            longitudeField.value = position.lng;
                        });
                    }
                } else {
                    console.error('Failed to get coordinates for the selected location');
                }
            })
            .catch(error => {
                console.error('Error calculating coordinates:', error);
            });
    }
    
    /**
     * Geocode the address based on city, block, and road
     * @param {string} city - City name
     * @param {string} block - Block number
     * @param {string} road - Road number (optional)
     * @returns {Promise} Promise that resolves to coordinates object {lat, lng}
     */
    function geocodeAddress(city, block, road) {
        return new Promise((resolve, reject) => {
            // If we're using a third-party geocoding service like Leaflet's Control.Geocoder
            if (typeof L !== 'undefined' && L.Control && L.Control.Geocoder) {
                if (!geocoder) {
                    geocoder = L.Control.Geocoder.nominatim();
                }
                
                // Format the address string
                let addressString = `Block ${block}`;
                if (road) addressString += `, Road ${road}`;
                addressString += `, ${city}, Bahrain`;
                
                geocoder.geocode(addressString, (results) => {
                    if (results && results.length > 0) {
                        resolve({
                            lat: results[0].center.lat,
                            lng: results[0].center.lng
                        });
                    } else {
                        // If geocoding fails, use Bahrain region coordinates based on city
                        const fallbackCoords = getFallbackCoordinates(city, block);
                        resolve(fallbackCoords);
                    }
                });
            } else {
                // Use fallback coordinates based on city and block
                const fallbackCoords = getFallbackCoordinates(city, block);
                resolve(fallbackCoords);
            }
        });
    }
    
    /**
     * Get fallback coordinates for Bahrain cities
     * @param {string} city - City name
     * @param {string} block - Block number
     * @returns {Object} Coordinates object {lat, lng}
     */
    function getFallbackCoordinates(city, block) {
        // Bahrain city coordinates (approximate centers)
        const cityCoordinates = {
            'Manama': { lat: 26.2285, lng: 50.5860 },
            'Riffa': { lat: 26.1305, lng: 50.5550 },
            'Muharraq': { lat: 26.2572, lng: 50.6119 },
            'Hamad Town': { lat: 26.1169, lng: 50.4961 },
            'Isa Town': { lat: 26.1736, lng: 50.5479 },
            'Sitra': { lat: 26.1553, lng: 50.6198 },
            'Budaiya': { lat: 26.2172, lng: 50.4488 },
            'Juffair': { lat: 26.2116, lng: 50.6046 },
            'Seef': { lat: 26.2456, lng: 50.5628 },
            'Adliya': { lat: 26.2106, lng: 50.5836 }
        };
        
        // Block adjustments - use these to slightly adjust the coordinates based on block number
        // This is a simplistic approach - in a real-world scenario, you'd want more accurate mapping
        const blockAdjustment = parseInt(block, 10) || 0;
        const latOffset = (blockAdjustment % 10) * 0.001;
        const lngOffset = (blockAdjustment % 20) * 0.001;
        
        // Get base coordinates for the city or default to Manama
        const baseCoords = cityCoordinates[city] || cityCoordinates['Manama'];
        
        // Apply small offsets based on block number to get a more specific location
        return {
            lat: baseCoords.lat + latOffset,
            lng: baseCoords.lng + lngOffset
        };
    }
    
    /**
     * Reverse geocode coordinates to address
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     */
    function reverseGeocode(lat, lng) {
        // If we're using a third-party geocoding service
        if (typeof L !== 'undefined' && L.Control && L.Control.Geocoder) {
            if (!geocoder) {
                geocoder = L.Control.Geocoder.nominatim();
            }
            
            geocoder.reverse({lat: lat, lng: lng}, map.options.crs.scale(map.getZoom()), results => {
                if (results && results.length > 0) {
                    const address = results[0].properties.address;
                    
                    // Try to extract block, road, and city information
                    if (address) {
                        // Update form fields if they exist
                        const cityField = document.getElementById('city');
                        const blockField = document.getElementById('block');
                        const roadField = document.getElementById('road');
                        
                        // Extract and set block
                        if (blockField && address.suburb) {
                            // Try to extract block number from suburb or address
                            const blockMatch = address.suburb.match(/\b\d+\b/) || 
                                              address.road?.match(/Block\s+(\d+)/i);
                            if (blockMatch) {
                                blockField.value = blockMatch[0];
                            }
                        }
                        
                        // Extract and set road
                        if (roadField && address.road) {
                            // Try to extract road number
                            const roadMatch = address.road.match(/Road\s+(\d+)/i) ||
                                             address.road.match(/\b\d+\b/);
                            if (roadMatch) {
                                roadField.value = roadMatch[1] || roadMatch[0];
                            }
                        }
                        
                        // Set city if found
                        if (cityField && address.city) {
                            // Find matching city option
                            Array.from(cityField.options).forEach(option => {
                                if (option.textContent.toLowerCase() === address.city.toLowerCase()) {
                                    cityField.value = option.value;
                                }
                            });
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Open the modal dialog
     */
    function openModal() {
        if (!modal) return;
        
        resetForm();
        modal.style.display = 'block';
        
        // Update form button text based on mode
        const submitBtn = form?.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = isEditMode ? 'Update' : 'Add';
        }
        
        // Initialize the cache for selected times
        selectedTimesCache = {};
        
        // Initialize map in modal if needed
        setTimeout(() => {
            const modalMap = document.getElementById('modalMap');
            if (modalMap && !map) {
                initMap(modalMap);
            }
        }, 300);
    }
    
    /**
     * Close the modal dialog
     */
    function closeModal() {
        if (!modal) return;
        
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
        if (!form) return;
        
        if (!isEditMode) {
            form.reset();
            
            // Only reset hidden fields if they exist
            const idField = document.getElementById('chargePointId');
            const addressIdField = document.getElementById('addressId');
            const latitudeField = document.getElementById('latitude');
            const longitudeField = document.getElementById('longitude');
            
            if (idField) idField.value = '';
            if (addressIdField) addressIdField.value = '';
            if (latitudeField) latitudeField.value = '';
            if (longitudeField) longitudeField.value = '';
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
        
        if (formErrors) {
            formErrors.style.display = 'none';
            formErrors.textContent = '';
        }
    }
    
    /**
     * Validate the form inputs
     * @return {boolean} True if form is valid
     */
    function validateForm() {
        if (!form || !formErrors) return false;
        
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
        if (home && (!home.value.trim() || isNaN(parseInt(home.value)))) {
            const homeError = document.getElementById('homeError');
            if (homeError) homeError.style.display = 'block';
            home.classList.add('input-error');
            isValid = false;
        }
        
        // Validate block
        const block = document.getElementById('block');
        if (block && (!block.value.trim() || isNaN(parseInt(block.value)))) {
            const blockError = document.getElementById('blockError');
            if (blockError) blockError.style.display = 'block';
            block.classList.add('input-error');
            isValid = false;
        }
        
        // Validate road
        const road = document.getElementById('road');
        if (road && (!road.value.trim() || isNaN(parseInt(road.value)))) {
            const roadError = document.getElementById('roadError');
            if (roadError) roadError.style.display = 'block';
            road.classList.add('input-error');
            isValid = false;
        }
        
        // Validate city
        const city = document.getElementById('city');
        if (city && !city.value) {
            const cityError = document.getElementById('cityError');
            if (cityError) cityError.style.display = 'block';
            city.classList.add('input-error');
            isValid = false;
        }
        
        // Validate cost
        const cost = document.getElementById('cost');
        if (cost && (!cost.value.trim() || isNaN(parseFloat(cost.value)) || parseFloat(cost.value) <= 0)) {
            const costError = document.getElementById('costError');
            if (costError) costError.style.display = 'block';
            cost.classList.add('input-error');
            isValid = false;
        }
        
        // Check if image is required (only for new charge points)
        const imageUpload = document.getElementById('imageUpload');
        const imageError = document.getElementById('imageError');
        if (!isEditMode && imageUpload && (!imageUpload.files || imageUpload.files.length === 0)) {
            if (imageError) imageError.style.display = 'block';
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
        if (!form || !formErrors) return;
        
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
                    console.error('JSON parsing error:', e);
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
        let chargePointId = editBtn?.getAttribute('data-charge-point-id');
        
        // If not found, try to get it from the details container
        if (!chargePointId) {
            const detailsContainer = document.getElementById('chargePointDetails');
            if (detailsContainer) {
                chargePointId = detailsContainer.getAttribute('data-charge-point-id');
            }
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
        xhr.send(`action=getChargePoint&chargePointId=${encodeURIComponent(chargePointId)}`);
    }
    
    /**
     * Populate form with charge point data
     * @param {Object} data The charge point data
     */
    function populateForm(data) {
        if (!data) return;
        
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
        
        // Handle street name field
        const streetNameField = document.getElementById('streetName');
        if (streetNameField) streetNameField.value = data.streetName || data.streetame || data.street || '';
        
        // Handle postcode field
        const postcodeField = document.getElementById('postcode');
        if (postcodeField) postcodeField.value = data.postcode || data.postal_code || data.zipcode || '';
        
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
            
            // Update map if it exists
            if (map && marker) {
                map.setView([lat, lng], 15);
                marker.setLatLng([lat, lng]);
            }
        } else {
            // Generate coordinates based on city and block
            setTimeout(updateCoordinates, 300); // Small delay to ensure fields are set first
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
    if (deleteBtn && deleteBtn.hasAttribute('data-charge-point-id')) {
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
    
    // Log the ID we're going to delete - helps with debugging
    console.log('Attempting to delete charge point with ID:', chargePointId);
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'my-charge-point.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
   const requestPayload = `action=delete&chargePointId=${encodeURIComponent(chargePointId)}`;
    console.log('Delete request payload:', requestPayload);
    
    xhr.onload = function() {
        // Log the full response for debugging
        console.log('Server response:', xhr.responseText);
        
        if (xhr.status >= 200 && xhr.status < 400) {
            try {
                const response = JSON.parse(xhr.responseText);
                
                if (response.success) {
                    alert(response.message || 'Charge point successfully deleted.');
                    
                    // Redirect to listing page or reload
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    // Log error details
                    console.error('Delete failed with error:', response.message);
                    alert('Failed to delete charge point: ' + (response.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('JSON parsing error:', e, 'Raw response:', xhr.responseText);
                alert('Error processing the response: ' + e.message);
            }
        } else {
            console.error('HTTP error status:', xhr.status, xhr.statusText);
            alert('Server error: ' + xhr.status);
        }
    };
    
    xhr.onerror = function(e) {
        console.error('XHR error:', e);
        alert('Connection error. Please try again later.');
    };
    
    // Send the delete request with the chargePointId
    xhr.send(requestPayload);
}
   /**
 * Initialize and set up the map
 * @param {HTMLElement} mapElement - Optional map container element
 */
function initMap(mapElement) {
    // Use provided element or try to find one
    const mapContainer = mapElement || document.getElementById('leetcodeMap'); // Updated to use the correct ID

    if (!mapContainer || typeof L === 'undefined') {
        console.log('Map container not found or Leaflet not loaded');
        return;
    }

    // Get initial coordinates from form fields if available
    let initialLat = 26.2285;  // Default to Manama, Bahrain
    let initialLng = 50.5860;
    let initialZoom = 10;

    const latField = document.getElementById('latitude');
    const lngField = document.getElementById('longitude');

    if (latField && latField.value && lngField && lngField.value) {
        initialLat = parseFloat(latField.value);
        initialLng = parseFloat(lngField.value);
        initialZoom = 15;
    }

    // Initialize map if it doesn't exist yet
    if (!map) {
        map = L.map(mapContainer).setView([initialLat, initialLng], initialZoom);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add marker if coordinates are available
        marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        // Handle marker drag end event
        marker.on('dragend', function() {
            const position = marker.getLatLng();
            if (latField) latField.value = position.lat;
            if (lngField) lngField.value = position.lng;
            reverseGeocode(position.lat, position.lng);
        });

        // Add click handler to set marker position
        map.on('click', function(e) {
            const position = e.latlng;
            marker.setLatLng(position);
            if (latField) latField.value = position.lat;
            if (lngField) lngField.value = position.lng;
            reverseGeocode(position.lat, position.lng);
        });

        // Add geocoder control if available
        if (L.Control && L.Control.Geocoder) {
            geocoder = L.Control.Geocoder.nominatim();
            L.Control.geocoder({
                geocoder: geocoder,
                defaultMarkGeocode: false
            })
            .on('markgeocode', function(e) {
                const position = e.geocode.center;
                marker.setLatLng(position);
                if (latField) latField.value = position.lat;
                if (lngField) lngField.value = position.lng;
                map.setView(position, 15);
            })
            .addTo(map);
        }
    } else {
        // If map already exists, just update the view
        map.setView([initialLat, initialLng], initialZoom);
        if (marker) {
            marker.setLatLng([initialLat, initialLng]);
        } else {
            marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
            marker.on('dragend', function() {
                const position = marker.getLatLng();
                if (latField) latField.value = position.lat;
                if (lngField) lngField.value = position.lng;
                reverseGeocode(position.lat, position.lng);
            });
        }

        // Invalidate size (useful when map container was hidden)
        setTimeout(() => map.invalidateSize(), 300);
    }
}
    /**
     * Update time pickers based on selected days
     * @param {boolean} preserveSelections - Whether to preserve existing time selections
     */
    function updateTimePickers(preserveSelections) {
        if (!timePickersContainer) return;
        
        // Clear existing time containers
        timePickersContainer.innerHTML = '';
        
        const timeSlots = [
            '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
            '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
            '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
            '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
        ];
        
        // Generate time pickers for each selected day
        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const day = checkbox.value;
                
                // Create container for this day's time buttons
                const dayContainer = document.createElement('div');
                dayContainer.className = 'time-picker-day-container';
                dayContainer.id = `timeContainer_${day}`;
                
                // Add day label
                const dayLabel = document.createElement('h5');
                dayLabel.textContent = day;
                dayContainer.appendChild(dayLabel);
                
                // Create time button grid
                const timeGrid = document.createElement('div');
                timeGrid.className = 'time-button-grid';
                
                // Add time buttons
                timeSlots.forEach(time => {
                    const timeButton = document.createElement('button');
                    timeButton.type = 'button';
                    timeButton.className = 'time-button';
                    timeButton.setAttribute('data-time', time);
                    timeButton.textContent = time;
                    
                    // If preserving selections and we have cached times for this day
                    if (preserveSelections && selectedTimesCache[day] && 
                        Array.isArray(selectedTimesCache[day]) && 
                        selectedTimesCache[day].includes(time)) {
                        timeButton.classList.add('active');
                    }
                    
                    // Add click handler for time selection
                    timeButton.addEventListener('click', function() {
                        // Toggle active state
                        this.classList.toggle('active');
                        
                        // Update cached selection for this day
                        if (!selectedTimesCache[day]) {
                            selectedTimesCache[day] = [];
                        }
                        
                        const timeValue = this.getAttribute('data-time');
                        
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
                
                // Add time grid to day container
                dayContainer.appendChild(timeGrid);
                
                // Add day container to main container
                timePickersContainer.appendChild(dayContainer);
            }
        });
    }
});