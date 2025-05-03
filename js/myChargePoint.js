document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('chargePointModal');
    const addBtn = document.getElementById('addChargePointBtn');
    const closeBtn = modal?.querySelector('.close');
    const form = document.getElementById('chargePointForm');
    const formErrors = document.getElementById('formErrors');
    const editBtn = document.querySelector('.edit-button');
    const deleteBtn = document.querySelector('.delete-button');
    const daysCheckboxes = document.querySelectorAll('input[name="availableDays"]');
    const timePickersContainer = document.getElementById('timePickersContainer');
    
    let map = null;
    let marker = null;
    let isEditMode = false;
    let geocoder = null;
    let selectedTimesCache = {};
    
    const detailsElement = document.getElementById('chargePointDetails');
    if (detailsElement && detailsElement.style.display !== 'none') {
        initMap(document.getElementById('leetcodeMap'));
    }
    
    if (addBtn) {
        addBtn.addEventListener('click', openModal);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    window.addEventListener('click', function(event) {
        if (modal && event.target === modal) {
            closeModal();
        }
    });
    
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
    
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (validateForm()) {
                submitForm();
            }
        });
    }
    
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            isEditMode = true;
            openModal();
            setTimeout(() => loadChargePointData(), 100);
        });
    }
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this charge point?')) {
                deleteChargePoint();
            }
        });
    }
    
    daysCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTimePickers(true);
        });
    });
    
    if (timePickersContainer) {
        updateTimePickers(false);
    }

    function updateCoordinates() {
        const citySelect = document.getElementById('city');
        const blockField = document.getElementById('block');
        const roadField = document.getElementById('road');
        const latitudeField = document.getElementById('latitude');
        const longitudeField = document.getElementById('longitude');

        if (!citySelect || !blockField || !latitudeField || !longitudeField) {
            console.error('Required fields for coordinate calculation are missing');
            return;
        }

        const cityId = citySelect.value;
        const block = blockField.value.trim();
        const road = roadField ? roadField.value.trim() : '';

        if (!cityId || !block) {
            console.log('City or block field is empty. Cannot calculate coordinates.');
            return;
        }

        const cityName = citySelect.options[citySelect.selectedIndex].text;
        console.log(`Calculating coordinates for: City=${cityName} (ID=${cityId}), Block=${block}, Road=${road}`);

        geocodeAddress(cityName, block, road)
            .then(coordinates => {
                if (coordinates) {
                    latitudeField.value = coordinates.lat;
                    longitudeField.value = coordinates.lng;

                    if (map && marker) {
                        map.setView([coordinates.lat, coordinates.lng], 15);
                        marker.setLatLng([coordinates.lat, coordinates.lng]);
                    } else if (map) {
                        marker = L.marker([coordinates.lat, coordinates.lng], { draggable: true }).addTo(map);
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

    function geocodeAddress(city, block, road) {
        return new Promise((resolve, reject) => {
            if (typeof L !== 'undefined' && L.Control && L.Control.Geocoder) {
                if (!geocoder) {
                    geocoder = L.Control.Geocoder.nominatim();
                }
                
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
                        const fallbackCoords = getFallbackCoordinates(city, block);
                        resolve(fallbackCoords);
                    }
                });
            } else {
                const fallbackCoords = getFallbackCoordinates(city, block);
                resolve(fallbackCoords);
            }
        });
    }

    function getFallbackCoordinates(city, block) {
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
        
        const blockAdjustment = parseInt(block, 10) || 0;
        const latOffset = (blockAdjustment % 10) * 0.001;
        const lngOffset = (blockAdjustment % 20) * 0.001;
        
        const baseCoords = cityCoordinates[city] || cityCoordinates['Manama'];
        return {
            lat: baseCoords.lat + latOffset,
            lng: baseCoords.lng + lngOffset
        };
    }

    function openModal() {
        if (!modal) return;
        
        resetForm();
        modal.style.display = 'block';
        
        const submitBtn = form?.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = isEditMode ? 'Update' : 'Add';
        }
        
        selectedTimesCache = {};
        
        setTimeout(() => {
            const modalMap = document.getElementById('modalMap');
            if (modalMap && !map) {
                initMap(modalMap);
            }
        }, 300);
    }

    function closeModal() {
        if (!modal) return;
        
        modal.style.display = 'none';
        isEditMode = false;
        resetForm();
        selectedTimesCache = {};
    }

    function resetForm() {
        if (!form) return;

        if (!isEditMode) {
            form.reset();
            const idField = document.getElementById('chargePointId');
            const addressIdField = document.getElementById('addressId');
            const latitudeField = document.getElementById('latitude');
            const longitudeField = document.getElementById('longitude');

            if (idField) idField.value = '';
            if (addressIdField) addressIdField.value = '';
            if (latitudeField) latitudeField.value = '';
            if (longitudeField) longitudeField.value = '';
        }

        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(message => {
            message.style.display = 'none';
        });

        const inputs = form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('input-error');
        });

        if (formErrors) {
            formErrors.style.display = 'none';
            formErrors.textContent = '';
        }
    }

    function validateForm() {
        if (!form || !formErrors) return false;

        let isValid = true;
        formErrors.style.display = 'none';
        formErrors.textContent = '';

        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(message => {
            message.style.display = 'none';
        });

        const inputs = form.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.classList.remove('input-error');
        });

        const home = document.getElementById('home');
        if (home && (!home.value.trim() || isNaN(parseInt(home.value)))) {
            const homeError = document.getElementById('homeError');
            if (homeError) homeError.style.display = 'block';
            home.classList.add('input-error');
            isValid = false;
        }

        const block = document.getElementById('block');
        if (block && (!block.value.trim() || isNaN(parseInt(block.value)))) {
            const blockError = document.getElementById('blockError');
            if (blockError) blockError.style.display = 'block';
            block.classList.add('input-error');
            isValid = false;
        }

        const road = document.getElementById('road');
        if (road && (!road.value.trim() || isNaN(parseInt(road.value)))) {
            const roadError = document.getElementById('roadError');
            if (roadError) roadError.style.display = 'block';
            road.classList.add('input-error');
            isValid = false;
        }

        const city = document.getElementById('city');
        if (city && !city.value) {
            const cityError = document.getElementById('cityError');
            if (cityError) cityError.style.display = 'block';
            city.classList.add('input-error');
            isValid = false;
        }

        const cost = document.getElementById('cost');
        if (cost && (!cost.value.trim() || isNaN(parseFloat(cost.value)) || parseFloat(cost.value) <= 0)) {
            const costError = document.getElementById('costError');
            if (costError) costError.style.display = 'block';
            cost.classList.add('input-error');
            isValid = false;
        }

        const imageUpload = document.getElementById('imageUpload');
        const imageError = document.getElementById('imageError');
        if (!isEditMode && imageUpload && (!imageUpload.files || imageUpload.files.length === 0)) {
            if (imageError) imageError.style.display = 'block';
            imageUpload.classList.add('input-error');
            isValid = false;
        }

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

    function submitForm() {
        if (!form || !formErrors) return;

        const formData = new FormData(form);
        formData.append('action', isEditMode ? 'update' : 'add');

        const selectedDays = [];
        const times = {};
        
        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const day = checkbox.value;
                selectedDays.push(day);
                const timeButtons = document.querySelectorAll(`#timeContainer_${day} .time-button.active`);
                const dayTimes = [];
                
                timeButtons.forEach(button => {
                    dayTimes.push(button.getAttribute('data-time'));
                });
                
                times[day] = dayTimes;
            }
        });

        selectedDays.forEach(day => {
            formData.append('days[]', day);
        });

        for (const day in times) {
            times[day].forEach(time => {
                formData.append(`times[${day}][]`, time);
            });
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'my-charge-point.php', true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        alert(response.message);
                        window.location.reload();
                    } else {
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

    function loadChargePointData() {
        let chargePointId = editBtn?.getAttribute('data-charge-point-id');
        if (!chargePointId) {
            const detailsContainer = document.getElementById('chargePointDetails');
            if (detailsContainer) {
                chargePointId = detailsContainer.getAttribute('data-charge-point-id');
            }
        }
        
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

        xhr.send(`action=getChargePoint&chargePointId=${encodeURIComponent(chargePointId)}`);
    }

  function populateForm(data) {
    if (!data) return;

    console.log('Populating form with data:', data);
    selectedTimesCache = {};

    const idField = document.getElementById('chargePointId');
    if (idField) idField.value = data.charge_point_id || '';
    
    const addressIdField = document.getElementById('addressId');
    if (addressIdField) addressIdField.value = data.charge_point_address_id || '';

    const homeField = document.getElementById('home');
    if (homeField) homeField.value = data.house_number || data.home || data.houseNumber || '';

    const roadField = document.getElementById('road');
    if (roadField) roadField.value = data.road || data.roadNumber || '';

    const blockField = document.getElementById('block');
    if (blockField) blockField.value = data.block || data.blockNumber || '';

    const streetNameField = document.getElementById('streetName');
    if (streetNameField) streetNameField.value = data.streetName || data.streetame || data.street || '';

    const postcodeField = document.getElementById('postcode');
    if (postcodeField) postcodeField.value = data.postcode || data.postal_code || data.zipcode || '';

    const cityField = document.getElementById('city');
    if (cityField) {
        cityField.value = data.city_id || data.cityId || data.city || '';
    }

    const costField = document.getElementById('cost');
    if (costField) costField.value = data.price_per_kwh || data.cost || data.pricePerKwh || '';

    const latField = document.getElementById('latitude');
    const lngField = document.getElementById('longitude');
    const lat = data.latitude || data.lat;
    const lng = data.longitude || data.lng || data.long;

    if (lat && lng && latField && lngField) {
        latField.value = lat;
        lngField.value = lng;

        // Check if map and marker exist, then update
        if (map && marker) {
            map.setView([lat, lng], 15); // Update the map view
            marker.setLatLng([lat, lng]); // Update the marker position
        } else {
            initMap(document.getElementById('modalMap')); // Initialize map if not done
            setTimeout(() => {
                if (marker) {
                    marker.setLatLng([lat, lng]); // Update marker position after map initialization
                }
            }, 300);
        }
    }

    // Handle availability data
    let availabilityData = null;

    if (Array.isArray(data.availabilityDays)) {
        availabilityData = data.availabilityDays;
    } else if (Array.isArray(data.availability)) {
        availabilityData = data.availability;
    } else if (Array.isArray(data.days)) {
        availabilityData = data.days;
    } else if (typeof data.availability === 'object' && data.availability !== null) {
        availabilityData = Object.keys(data.availability).map(day => ({
            day_of_week: day,
            times: data.availability[day]
        }));
    }

    if (availabilityData && availabilityData.length > 0) {
        daysCheckboxes.forEach(checkbox => {
            checkbox.checked = false; // Reset all checkboxes
        });

        availabilityData.forEach(dayData => {
            const day = dayData.day_of_week || dayData.day || dayData.dayOfWeek;
            if (!day) return;

            const checkbox = document.querySelector(`input[name="availableDays"][value="${day}"]`);
            if (checkbox) {
                checkbox.checked = true; // Check the selected day

                const times = dayData.times || dayData.timeSlots || dayData.availableTimes;
                if (Array.isArray(times) && times.length > 0) {
                    selectedTimesCache[day] = times; // Cache the selected times for this day
                }
            }
        });
        updateTimePickers(true); // Update time pickers with preserved selections
    }

    console.log('Form populated successfully with charge point data');
}
 function deleteChargePoint() {
    // Add more detailed debugging
    console.log('Delete function called');
    
    // Try all possible ways to get the charge point ID
    let chargePointId = null;
    let idSource = '';
    
    // Method 1: From delete button
    const deleteBtn = document.querySelector('.delete-button');
    if (deleteBtn && deleteBtn.getAttribute('data-charge-point-id')) {
        chargePointId = deleteBtn.getAttribute('data-charge-point-id');
        idSource = 'delete button';
        console.log('Found ID from delete button:', chargePointId);
    }
    
    // Method 2: From charge point details container
    if (!chargePointId) {
        const detailsContainer = document.getElementById('chargePointDetails');
        if (detailsContainer && detailsContainer.getAttribute('data-charge-point-id')) {
            chargePointId = detailsContainer.getAttribute('data-charge-point-id');
            idSource = 'details container';
            console.log('Found ID from details container:', chargePointId);
        }
    }
    
    // Method 3: From hidden form field
    if (!chargePointId) {
        const hiddenField = document.getElementById('chargePointId') || 
                           document.querySelector('input[name="chargePointId"]') ||
                           document.querySelector('input[name="charge_point_id"]');
        if (hiddenField && hiddenField.value) {
            chargePointId = hiddenField.value;
            idSource = 'hidden field';
            console.log('Found ID from hidden field:', chargePointId);
        }
    }
    
    // Check if we found an ID
    if (!chargePointId) {
        console.error('No charge point ID found using any method');
        alert('Error: No charge point ID found. Please try again or contact support.');
        return;
    }
    
    console.log(`Attempting to delete charge point with ID: ${chargePointId} (source: ${idSource})`);
    
    // Log all relevant elements that might contain the ID
    console.log('All potential ID containers:');
    document.querySelectorAll('[data-charge-point-id]').forEach(el => {
        console.log(`Element ${el.tagName} has ID: ${el.getAttribute('data-charge-point-id')}`);
    });
    
    // Double confirm with user
    if (!confirm(`Are you sure you want to delete this charge point `)) {
        console.log('User cancelled deletion');
        return;
    }

    // Create form data to send
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('chargePointId', chargePointId);
    
    // Log the data we're sending
    console.log('Sending data:', {
        action: 'delete',
        chargePointId: chargePointId
    });

    // Send the delete request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'my-charge-point.php', true);
    
    xhr.onload = function() {
        console.log('Raw response:', xhr.responseText);
        if (xhr.status >= 200 && xhr.status < 400) {
            try {
                const response = JSON.parse(xhr.responseText);
                console.log('Parsed response:', response);
                
                if (response.success) {
                    alert(response.message || 'Charge point successfully deleted.');
                    window.location.reload();
                } else {
                    alert('Failed to delete charge point: ' + (response.message || 'Unknown error'));
                    console.error('Delete operation failed:', response.message);
                }
            } catch (e) {
                console.error('JSON parsing error:', e, 'Raw response:', xhr.responseText);
                alert('Error processing the response: ' + e.message);
            }
        } else {
            alert('Server error: ' + xhr.status);
            console.error('Server error:', xhr.status, xhr.statusText);
        }
    };

    xhr.onerror = function(e) {
        console.error('XHR error:', e);
        alert('Connection error. Please try again later.');
    };

    xhr.send(formData);
}


  function initMap(mapElement) {
    const mapContainer = mapElement || document.getElementById('leetcodeMap');
    if (!mapContainer || typeof L === 'undefined') {
        console.log('Map container not found or Leaflet not loaded');
        return;
    }

    // Default to Manama, Bahrain
    let initialLat = 26.2285;  
    let initialLng = 50.5860;
    let initialZoom = 10;

    // Get latitude and longitude from the fields
    const latField = document.getElementById('latitude');
    const lngField = document.getElementById('longitude');

    if (latField && latField.value && lngField && lngField.value) {
        initialLat = parseFloat(latField.value);
        initialLng = parseFloat(lngField.value);
        initialZoom = 15; // Zoom in if we have specific coordinates
    }

    map = L.map(mapContainer).setView([initialLat, initialLng], initialZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
    marker.on('dragend', function() {
        const position = marker.getLatLng();
        latField.value = position.lat;
        lngField.value = position.lng;
        reverseGeocode(position.lat, position.lng);
    });
}
    function updateTimePickers(preserveSelections) {
        if (!timePickersContainer) return;

        timePickersContainer.innerHTML = '';
        
        const timeSlots = [
            '00:00', '01:00', '02:00', '03:00', '04:00', '05:00',
            '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
            '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
            '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
        ];

        daysCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const day = checkbox.value;

                const dayContainer = document.createElement('div');
                dayContainer.className = 'time-picker-day-container';
                dayContainer.id = `timeContainer_${day}`;

                const dayLabel = document.createElement('h5');
                dayLabel.textContent = day;
                dayContainer.appendChild(dayLabel);

                const timeGrid = document.createElement('div');
                timeGrid.className = 'time-button-grid';

                timeSlots.forEach(time => {
                    const timeButton = document.createElement('button');
                    timeButton.type = 'button';
                    timeButton.className = 'time-button';
                    timeButton.setAttribute('data-time', time);
                    timeButton.textContent = time;

                    if (preserveSelections && selectedTimesCache[day] && 
                        Array.isArray(selectedTimesCache[day]) && 
                        selectedTimesCache[day].includes(time)) {
                        timeButton.classList.add('active');
                    }

                    timeButton.addEventListener('click', function() {
                        this.classList.toggle('active');
                        if (!selectedTimesCache[day]) {
                            selectedTimesCache[day] = [];
                        }

                        const timeValue = this.getAttribute('data-time');
                        if (this.classList.contains('active')) {
                            if (!selectedTimesCache[day].includes(timeValue)) {
                                selectedTimesCache[day].push(timeValue);
                            }
                        } else {
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
});