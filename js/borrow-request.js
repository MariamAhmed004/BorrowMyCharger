
    // AJAX Polling Configuration
    const POLLING_INTERVAL = 5000; // 5 seconds
    let pollingTimer;
    let knownBookingIds = [];
    
    // Collect initial booking IDs when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Collect existing booking IDs
        const rows = document.querySelectorAll('#booking-requests-body tr');
        rows.forEach(row => {
            if (row.dataset.id) {
                knownBookingIds.push(row.dataset.id);
            }
        });
        
        // Set up AJAX form submission for approve/reject buttons
        setupFormSubmission();
        
        // Start polling for new booking requests
        startPolling();
    });
    
    // Function to handle AJAX form submission
    function setupFormSubmission() {
        const forms = document.querySelectorAll('.booking-action-form');
        
        forms.forEach(form => {
            // First remove any existing event listeners to prevent duplicates
            const newForm = form.cloneNode(true);
            form.parentNode.replaceChild(newForm, form);
            
            // Add event listener to the new form
            newForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show loading indicator
                document.getElementById('loading-indicator').style.display = 'block';
                
                // Get form values
                const bookingId = this.querySelector('input[name="booking_id"]').value;
                const action = e.submitter.value; // Get the value from the button that was clicked
                
                // Create form data for submission
                const formData = new FormData();
                formData.append('booking_id', bookingId);
                formData.append('action', action);
                
                // Send AJAX request
                fetch('borrow-request.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Display message
                    const messageElement = document.getElementById('ajax-message');
                    messageElement.textContent = data.message;
                    messageElement.className = data.success ? 'alert alert-success' : 'alert alert-danger';
                    messageElement.style.display = 'block';
                    
                    // Hide message after 5 seconds
                    setTimeout(() => {
                        messageElement.style.display = 'none';
                    }, 5000);
                    
                    if (data.success) {
                        // Update the booking row status
                        updateBookingRowStatus(bookingId, action);
                    }
                })
                .catch(error => {
                    // Show error message
                    const messageElement = document.getElementById('ajax-message');
                    messageElement.textContent = 'An error occurred: ' + error.message;
                    messageElement.className = 'alert alert-danger';
                    messageElement.style.display = 'block';
                    
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Hide loading indicator
                    document.getElementById('loading-indicator').style.display = 'none';
                });
            });
        });
    }
    
    // Function to update a booking row's status after approval/rejection
    function updateBookingRowStatus(bookingId, action) {
        const row = document.getElementById('booking-row-' + bookingId);
        if (row) {
            // Update the status badge
            const statusCell = row.querySelector('td:nth-child(7)');
            const actionCell = row.querySelector('td:nth-child(9)');
            
            if (statusCell && actionCell) {
                const badge = statusCell.querySelector('.badge');
                if (badge) {
                    if (action === 'approve') {
                        badge.className = 'badge bg-success';
                        badge.textContent = 'Approved';
                    } else if (action === 'reject') {
                        badge.className = 'badge bg-danger';
                        badge.textContent = 'Rejected';
                    }
                }
                
                // Replace action buttons with "Processed" text
                actionCell.innerHTML = '<span class="text-muted">Processed</span>';
            }
        }
    }
    
    // Function to fetch booking requests
    function fetchBookingRequests() {
        fetch('borrow-request.php?action=get_requests', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                processNewBookings(data.bookingRequests);
            }
        })
        .catch(error => {
            console.error('Polling error:', error);
        });
    }
    
    // Function to process new bookings from the AJAX response
    function processNewBookings(bookings) {
        if (!bookings || bookings.length === 0) return;
        
        let hasNewBookings = false;
        
        // Check for new bookings that aren't in our known IDs list
        bookings.forEach(booking => {
            const bookingId = booking.booking_id.toString();
            // Only add if not already in our known list
            if (!knownBookingIds.includes(bookingId)) {
                addNewBookingRow(booking);
                knownBookingIds.push(bookingId);
                hasNewBookings = true;
            }
        });
        
        // If we found new bookings, show a notification
        if (hasNewBookings) {
            const messageElement = document.getElementById('ajax-message');
            messageElement.textContent = 'New booking request(s) received!';
            messageElement.className = 'alert alert-info';
            messageElement.style.display = 'block';
            
            // Hide message after 5 seconds
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 5000);
            
            // Remove no-requests message if it exists
            const noRequestsMessage = document.getElementById('no-requests-message');
            if (noRequestsMessage) {
                noRequestsMessage.style.display = 'none';
                
                // Make sure table is visible
                const container = document.getElementById('booking-requests-container');
                if (!container.querySelector('table')) {
                    // Create table if it doesn't exist
                    container.innerHTML = `
                        <div class="table-responsive">
                            <table id="booking-requests-table" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Charge Point</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="booking-requests-body">
                                </tbody>
                            </table>
                        </div>
                    `;
                }
            }
        }
    }
    
    // Function to add a new booking row to the table
    function addNewBookingRow(booking) {
        const tableBody = document.getElementById('booking-requests-body');
        if (!tableBody) return;
        
        // Make sure the booking ID isn't already in the table
        const existingRow = document.getElementById('booking-row-' + booking.booking_id);
        if (existingRow) {
            return; // Skip if row already exists
        }
        
        // Format date and time
        const bookingDate = new Date(booking.booking_date);
        const formattedDate = bookingDate.getDate() + ' ' + 
                             bookingDate.toLocaleString('default', { month: 'short' }) + ' ' + 
                             bookingDate.getFullYear();
                             
        // Format time (assumes booking_time is in HH:MM:SS format)
        let formattedTime = booking.booking_time;
        if (booking.booking_time.includes(':')) {
            const timeParts = booking.booking_time.split(':');
            formattedTime = timeParts[0] + ':' + timeParts[1];
        }
        
        // Create new row
        const row = document.createElement('tr');
        row.id = 'booking-row-' + booking.booking_id;
        row.dataset.id = booking.booking_id;
        
        // Set row content
        row.innerHTML = `
            <td>${booking.booking_id}</td>
            <td>${booking.first_name} ${booking.last_name}</td>
            <td>${formattedDate}</td>
            <td>${formattedTime}</td>
            <td>${booking.streetName}, ${booking.postcode}</td>
            <td>BD${parseFloat(booking.price_per_kwh).toFixed(2)}/kWh</td>
            <td>
                <span class="badge ${booking.booking_status_id == 1 ? 'bg-warning' : 
                                     (booking.booking_status_id == 2 ? 'bg-success' : 'bg-danger')}">
                    ${booking.booking_status_title}
                </span>
            </td>
            <td><a href="borrow-request-details.php?booking_id=${booking.booking_id}" class="btn btn-info">View Details</a></td>
            <td>
                ${booking.booking_status_id == 1 ? `
                    <form class="d-inline booking-action-form">
                        <input type="hidden" name="booking_id" value="${booking.booking_id}">
                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success approve-btn">Approve</button>
                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger reject-btn">Reject</button>
                    </form>
                ` : '<span class="text-muted">Processed</span>'}
            </td>
        `;
        
        // Add row to table at the beginning (newest first)
        if (tableBody.firstChild) {
            tableBody.insertBefore(row, tableBody.firstChild);
        } else {
            tableBody.appendChild(row);
        }
        
        // Setup event listeners for the new form
        setupFormSubmission();
    }
    
    // Function to start polling
    function startPolling() {
        // Clear any existing timers
        if (pollingTimer) {
            clearInterval(pollingTimer);
        }
        
        // Start polling at regular intervals
        pollingTimer = setInterval(fetchBookingRequests, POLLING_INTERVAL);
    }
    
    // Stop polling when the page is unloaded
    window.addEventListener('beforeunload', function() {
        if (pollingTimer) {
            clearInterval(pollingTimer);
        }
    });
