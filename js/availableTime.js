// Time selection functionality for charge point form
document.addEventListener('DOMContentLoaded', function() {
    // Constants for time options
    const TIME_OPTIONS = [
        '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', 
        '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', 
        '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', 
        '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'
    ];
    
    // Get the container elements
    const daysContainer = document.querySelector('.day-checkbox-container');
    const timePickersContainer = document.getElementById('timePickersContainer');
    
    // Days of the week for creating time picker containers
    const DAYS_OF_WEEK = [
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ];
    
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
    
    // Handle day checkbox changes
    function handleDayCheckboxChange(event) {
        const day = event.target.value;
        const isChecked = event.target.checked;
        const timePickerContainer = document.getElementById(`${day.toLowerCase()}-time-picker`);
        
        if (timePickerContainer) {
            timePickerContainer.style.display = isChecked ? 'block' : 'none';
        }
    }
    
    // Add event listeners to day checkboxes
    function addDayCheckboxListeners() {
        const dayCheckboxes = document.querySelectorAll('input[name="availableDays"]');
        dayCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', handleDayCheckboxChange);
        });
    }
    
    // Populate time selections from existing data (for edit mode)
    function populateTimeSelections(chargePointData) {
        if (!chargePointData || !chargePointData.availableDays) return;
        
        // For each available day
        chargePointData.availableDays.forEach(dayData => {
            // Check the day checkbox
            const dayCheckbox = document.querySelector(`input[name="availableDays"][value="${dayData.day}"]`);
            if (dayCheckbox) {
                dayCheckbox.checked = true;
                
                // Show the time picker
                const timePickerContainer = document.getElementById(`${dayData.day.toLowerCase()}-time-picker`);
                if (timePickerContainer) {
                    timePickerContainer.style.display = 'block';
                    
                    // Select the times
                    dayData.times.forEach(time => {
                        const timeButton = timePickerContainer.querySelector(`.time-button[data-time="${time}"]`);
                        if (timeButton) {
                            timeButton.classList.add('active');
                        }
                    });
                    
                    // Update the hidden input
                    updateSelectedTimes(dayData.day);
                }
            }
        });
    }
    
    // Initialize everything
    function init() {
        initializeTimePickers();
        addDayCheckboxListeners();
        
        // If editing, hook into the existing edit functionality
        const editButtons = document.querySelectorAll('.edit-charge-point');
        editButtons.forEach(btn => {
            const originalClick = btn.onclick;
            btn.onclick = function(e) {
                if (originalClick) {
                    originalClick.call(this, e);
                }
                
                // Get charge point data after original click handler
                setTimeout(() => {
                    const chargePointCard = this.closest('.charge-point-card');
                    if (chargePointCard) {
                        const chargePointData = JSON.parse(chargePointCard.dataset.details || '{}');
                        populateTimeSelections(chargePointData);
                    }
                }, 100);
            };
        });
        
        // Modify the form submission to include the selected days and times
        const saveChargePointBtn = document.getElementById('saveChargePointBtn');
        if (saveChargePointBtn) {
            const originalClick = saveChargePointBtn.onclick;
            saveChargePointBtn.onclick = function(e) {
                // Add days and times to the form before submission
                addDaysAndTimesToForm();
                
                if (originalClick) {
                    originalClick.call(this, e);
                }
            };
        }
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
    
    // Start everything
    init();
});