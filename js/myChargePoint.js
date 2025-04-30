document.addEventListener("DOMContentLoaded", function() {
    // DOM Elements
    const modal = document.getElementById("chargePointModal");
    const addBtn = document.getElementById("addChargePointBtn");
    const closeBtn = document.getElementsByClassName("close")[0];
    const chargePointDetails = document.getElementById("chargePointDetails");
    const detailsParagraph = document.getElementById("details");
    const chargeImage = document.getElementById("chargeImage");
    const addChargePointSection = document.getElementById("addChargePointSection");
    const mapDiv = document.getElementById("leetcodeMap");
    const availabilityTypeSelect = document.getElementById("availabilityType");
    const daysOfWeekContainer = document.getElementById("daysOfWeekContainer");
    const timePickersContainer = document.getElementById("timePickersContainer");
    const dayCheckboxes = document.querySelectorAll('input[name="availableDays"]');
    const chargePointForm = document.getElementById("chargePointForm");
    const editButton = document.querySelector(".edit-button");
    const deleteButton = document.querySelector(".delete-button");
    
    // Variables
    let map = null;
    let chargePointData = null; // Store charge point data for editing
    let isEditMode = false;
    
    // Initialize
    fetchBahrainCities();
    
    // Event Listeners
    availabilityTypeSelect.addEventListener("change", handleAvailabilityTypeChange);
    dayCheckboxes.forEach(checkbox => checkbox.addEventListener("change", updateTimePickers));
    addBtn.onclick = () => openModal();
    closeBtn.onclick = () => closeModal();
    window.onclick = (event) => { if (event.target === modal) closeModal(); };
    chargePointForm.onsubmit = handleFormSubmit;
    editButton.addEventListener("click", handleEdit);
    deleteButton.addEventListener("click", handleDelete);
    
    // Functions
    function handleAvailabilityTypeChange() {
        if (this.value === "scheduled") {
            daysOfWeekContainer.style.display = "block";
        } else {
            daysOfWeekContainer.style.display = "none";
            timePickersContainer.innerHTML = "";
        }
    }
    
    function updateTimePickers() {
        timePickersContainer.innerHTML = "";
        
        dayCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                const day = checkbox.value;
                createTimePickerForDay(day);
            }
        });
    }
    
    function createTimePickerForDay(day) {
        const timePickerDiv = document.createElement("div");
        timePickerDiv.className = "time-picker-container";
        timePickerDiv.style.display = "block";
        
        const dayHeader = document.createElement("h5");
        dayHeader.textContent = day;
        timePickerDiv.appendChild(dayHeader);
        
        // Create time selector container
        const timeSelectionContainer = document.createElement("div");
        timeSelectionContainer.className = "time-selection-container";
        
        const timeLabel = document.createElement("div");
        timeLabel.className = "time-label";
        timeLabel.textContent = "Select Times:";
        timeSelectionContainer.appendChild(timeLabel);
        
        const timeGrid = document.createElement("div");
        timeGrid.className = "time-grid";
        timeGrid.dataset.day = day;
        
        // Create time buttons for each 30-minute interval
        for (let hour = 0; hour < 24; hour++) {
            for (let minute of ["00", "30"]) {
                const time = `${hour < 10 ? "0" + hour : hour}:${minute}`;
                
                const timeBtn = createTimeButton(time, day);
                timeGrid.appendChild(timeBtn);
            }
        }
        
        timeSelectionContainer.appendChild(timeGrid);
        timePickerDiv.appendChild(timeSelectionContainer);
        timePickersContainer.appendChild(timePickerDiv);

        // Set default selections if in edit mode
        if (isEditMode && chargePointData && chargePointData.schedules) {
            const daySchedule = chargePointData.schedules.find(s => s.day === day);
            if (daySchedule && daySchedule.times) {
                daySchedule.times.forEach(time => {
                    selectTimeButton(timeGrid, time);
                });
            }
        }
    }
    
    function createTimeButton(time, day) {
        const timeBtn = document.createElement("button");
        timeBtn.type = "button";
        timeBtn.className = "time-button";
        timeBtn.textContent = time;
        timeBtn.dataset.time = time;
        timeBtn.dataset.day = day;
        
        timeBtn.addEventListener("click", function() {
            // Toggle active class on this button
            this.classList.toggle("active");
        });
        
        return timeBtn;
    }
    
    function selectTimeButton(grid, time) {
        const buttons = grid.querySelectorAll(".time-button");
        buttons.forEach(btn => {
            if (btn.dataset.time === time) {
                btn.classList.add("active");
            }
        });
    }
    
    function fetchBahrainCities() {
        const bahrainCities = [
            "Manama", "Riffa", "Muharraq", "Hamad Town", "Isa Town", "Sitra",
            "Jidhafs", "Budaiya", "A'ali", "Al Hidd", "Zallaq", "Jasra",
            "Diraz", "Barbar", "Tubli", "Saar", "Sanabis", "Adliya",
            "Seef", "Samaheej", "Karbabad", "Al Dair", "Busaiteen", 
            "Galali", "Amwaj Islands", "Juffair", "Al Janabiyah", "Shakhoora"
        ];
        
        bahrainCities.sort();
        
        const citySelect = document.getElementById("city");
        bahrainCities.forEach(city => {
            const option = document.createElement("option");
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
        });
    }
    
    function openModal() {
        modal.style.display = "block";
        
        if (availabilityTypeSelect.value === "scheduled") {
            updateTimePickers();
        }
    }
    
    function closeModal() {
        modal.style.display = "none";
        
        if (isEditMode) {
            isEditMode = false;
            // Reset form title
            document.querySelector(".modal-title").textContent = "Add Charge Point Details";
            
            // Reset form submit button text
            document.querySelector(".add-button").textContent = "Add";
        }
    }
    
    function handleFormSubmit(event) {
        event.preventDefault();
        
        // Get form values
        const formData = {
            home: document.getElementById("home").value,
            availabilityType: document.getElementById("availabilityType").value,
            road: document.getElementById("road").value,
            block: document.getElementById("block").value,
            city: document.getElementById("city").value,
            cost: document.getElementById("cost").value,
            imageUpload: document.getElementById("imageUpload").files[0]
        };
        
        // Create schedules array if availability type is scheduled
        const schedules = [];
        if (formData.availabilityType === "scheduled") {
            dayCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const day = checkbox.value;
                    const selectedTimeBtns = document.querySelectorAll(`.time-grid[data-day="${day}"] .time-button.active`);
                    
                    if (selectedTimeBtns.length > 0) {
                        const times = Array.from(selectedTimeBtns).map(btn => btn.dataset.time);
                        schedules.push({
                            day: day,
                            times: times
                        });
                    }
                }
            });
        }
        
        // Parse availability info
        let availabilityInfo = "";
        if (formData.availabilityType === "always") {
            availabilityInfo = "Always Available";
        } else if (formData.availabilityType === "onrequest") {
            availabilityInfo = "Available On Request";
        } else if (formData.availabilityType === "scheduled") {
            const selectedDays = [];
            let scheduleDetails = "";
            
            schedules.forEach(schedule => {
                selectedDays.push(schedule.day);
                scheduleDetails += `<br>${schedule.day}: ${schedule.times.join(", ")}`;
            });
            
            if (selectedDays.length > 0) {
                availabilityInfo = "Available on: " + selectedDays.join(", ") + scheduleDetails;
            } else {
                availabilityInfo = "Scheduled (No days selected)";
            }
        }
        
        // Store charge point data for editing
        chargePointData = {
            home: formData.home,
            availabilityType: formData.availabilityType,
            road: formData.road,
            block: formData.block,
            city: formData.city,
            cost: formData.cost,
            availabilityInfo: availabilityInfo,
            schedules: schedules
        };
        
        // Handle image upload
        if (formData.imageUpload || isEditMode) {
            if (formData.imageUpload) {
                // New image uploaded
                const reader = new FileReader();
                reader.onload = function(e) {
                    chargePointData.imageSrc = e.target.result;
                    displayChargePointDetails();
                };
                reader.readAsDataURL(formData.imageUpload);
            } else if (isEditMode && chargePointData.imageSrc) {
                // Use existing image in edit mode
                displayChargePointDetails();
            }
        } else {
            alert("Please upload an image for your charge point.");
        }
    }
    
    function displayChargePointDetails() {
        // Update details paragraph
        detailsParagraph.innerHTML = `
            <strong>Home:</strong> ${chargePointData.home}<br>
            <strong>Road:</strong> ${chargePointData.road}<br>
            <strong>Block:</strong> ${chargePointData.block}<br>
            <strong>City:</strong> ${chargePointData.city}<br>
            <strong>Charge Cost:</strong> ${chargePointData.cost} BHD / kWh<br>
            <strong>Availability:</strong> ${chargePointData.availabilityInfo}<br>
        `;
        
        // Update image
        chargeImage.src = chargePointData.imageSrc;
        chargeImage.style.display = "block";
        
        // Show charge point details and hide add section
        addChargePointSection.style.display = "none";
        chargePointDetails.style.display = "block";
        
        // Close modal
        closeModal();
        
        // Initialize map
        setTimeout(initializeMap, 300);
        
        // Show success message
        alert(isEditMode ? "Charge Point Updated!" : "Charge Point Added!");
        
        // Reset edit mode
        isEditMode = false;
        document.querySelector(".modal-title").textContent = "Add Charge Point Details";
        document.querySelector(".add-button").textContent = "Add";
    }
    
    function initializeMap() {
        if (map) {
            map.remove();
            map = null;
        }
        
        map = L.map('leetcodeMap').setView([26.0667, 50.5577], 9);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        map.invalidateSize();
        
        const address = `${chargePointData.road}, ${chargePointData.city}, Bahrain`;
        console.log("Geocoding address:", address);
        
        const geocodeUrl = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;
        
        fetch(geocodeUrl)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    
                    map.setView([lat, lon], 15);
                    L.marker([lat, lon]).addTo(map)
                        .bindPopup(`<b>Location:</b> ${address}`)
                        .openPopup();
                    
                    chargePointData.lat = lat;
                    chargePointData.lon = lon;
                    
                    detailsParagraph.innerHTML += `
                        <strong>Latitude:</strong> ${lat}<br>
                        <strong>Longitude:</strong> ${lon}<br>
                    `;
                } else {
                    alert("Location not found. Using default map view.");
                }
            })
            .catch(error => {
                console.error("Geocoding error:", error);
                alert("Error retrieving location data. Using default map view.");
            });
    }
    
    function handleEdit() {
        if (!chargePointData) {
            alert("No charge point data available for editing.");
            return;
        }
        
        isEditMode = true;
        
        // Update modal title
        document.querySelector(".modal-title").textContent = "Edit Charge Point Details";
        
        // Update submit button
        document.querySelector(".add-button").textContent = "Update";
        
        // Fill form with existing data
        document.getElementById("home").value = chargePointData.home;
        document.getElementById("road").value = chargePointData.road;
        document.getElementById("block").value = chargePointData.block;
        document.getElementById("city").value = chargePointData.city;
        document.getElementById("cost").value = chargePointData.cost;
        
        // Set availability type
        document.getElementById("availabilityType").value = chargePointData.availabilityType;
        
        // Reset checkboxes
        dayCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Set day checkboxes and time selections
        if (chargePointData.availabilityType === "scheduled") {
            daysOfWeekContainer.style.display = "block";
            
            if (chargePointData.schedules && chargePointData.schedules.length > 0) {
                chargePointData.schedules.forEach(schedule => {
                    const checkbox = document.querySelector(`input[name="availableDays"][value="${schedule.day}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                
                // Update time pickers
                updateTimePickers();
            }
        } else {
            daysOfWeekContainer.style.display = "none";
            timePickersContainer.innerHTML = "";
        }
        
        // Open modal
        openModal();
    }
    
    function handleDelete() {
        if (confirm("Are you sure you want to delete this charge point?")) {
            // Reset form
            chargePointForm.reset();
            
            // Hide charge point details and show add section
            chargePointDetails.style.display = "none";
            addChargePointSection.style.display = "block";
            
            // Reset charge point data
            chargePointData = null;
            
            // Remove map
            if (map) {
                map.remove();
                map = null;
            }
            
            alert("Charge Point Deleted!");
        }
    }
});