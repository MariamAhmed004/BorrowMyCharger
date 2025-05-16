
document.addEventListener('DOMContentLoaded', function() {
    // Global variables to track current state
    let currentPage = 1;
    let currentStatus = '';
    let currentBookings = [];
    let pollingInterval;
    let lastCheckTime = null;
    const POLLING_INTERVAL_MS = 5000; // Poll every 5 seconds

    // A simple debug function
    function debug(message, data) {
        if (window.console && window.console.log) {
            console.log(message, data);
        }
    }

    function loadBookings(page = 1, status = '') {
        currentPage = parseInt(page);
        currentStatus = status;
        
        showLoadingState();
        debug('Loading bookings', {page: page, status: status});
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'booking-history.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.error) {
                        document.getElementById('bookingTable').innerHTML = '<div class="alert alert-danger">' + result.error + '</div>';
                        return;
                    }

                    // Save the current bookings for comparison in future polls
                    currentBookings = result.bookings || [];
                    lastCheckTime = result.currentTime;
                    
                    renderBookingTable(result.bookings || []);
                    updatePagination(result.totalPages || 1, currentPage);
                    
                    console.log("Bookings loaded successfully:", result);
                } catch (e) {
                    console.error("Error parsing response:", e, xhr.responseText);
                    document.getElementById('bookingTable').innerHTML = '<div class="alert alert-danger">Error processing response. See console for details.</div>';
                }
            } else {
                document.getElementById('bookingTable').innerHTML = '<div class="alert alert-danger">Error loading bookings. Please try again.</div>';
            }
        };

        xhr.onerror = function() {
            document.getElementById('bookingTable').innerHTML = '<div class="alert alert-danger">Network error occurred. Please check your connection.</div>';
        };

        xhr.send(`page=${page}&status=${status}`);
    }

    function showLoadingState() {
        document.getElementById('bookingTable').innerHTML = `
            <div class="text-center p-3">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span class="ms-2">Loading bookings...</span>
            </div>
        `;
    }

    function renderBookingTable(bookings) {
        if (bookings.length === 0) {
            document.getElementById('bookingTable').innerHTML = '<div class="alert alert-info">No bookings found.</div>';
            return;
        }
        
        let bookingsHtml = `
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Charge Point</th>
                        <th>Booking Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        bookings.forEach(booking => {
            bookingsHtml += `<tr data-booking-id="${booking.booking_id}" data-status="${booking.booking_status_id}">
                <td>${booking.streetName} ${booking.house_number}</td>
                <td>${booking.booking_date.split(' ')[0]}</td>
                <td>${booking.booking_time}</td>
                <td><span class="badge ${getStatusBadgeClass(booking.booking_status_id)}">${getStatusText(booking.booking_status_id)}</span></td>
                <td><a href="booking-details.php?booking_id=${booking.booking_id}" class="btn btn-sm btn-info">View Details</a></td>
            </tr>`;
        });
        
        bookingsHtml += '</tbody></table>';
        document.getElementById('bookingTable').innerHTML = bookingsHtml;
    }

    function updatePagination(totalPages, currentPage) {
        const paginationEl = document.getElementById('pagination');
        const paginationList = paginationEl.querySelector('.pagination');
        
        if (totalPages <= 1) {
            paginationEl.style.display = 'none';
            return;
        }
        
        paginationEl.style.display = 'block';
        
        let paginationHtml = '';
        
        // Previous button
        paginationHtml += `
            <li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${parseInt(currentPage) - 1}" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            // Only show limited page numbers with ellipsis
            if (
                i === 1 || 
                i === totalPages || 
                (i >= currentPage - 1 && i <= currentPage + 1)
            ) {
                paginationHtml += `
                    <li class="page-item ${i === parseInt(currentPage) ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            } else if (
                (i === currentPage - 2 && currentPage > 3) || 
                (i === currentPage + 2 && currentPage < totalPages - 2)
            ) {
                paginationHtml += `
                    <li class="page-item disabled">
                        <a class="page-link" href="#">...</a>
                    </li>
                `;
            }
        }
        
        // Next button
        paginationHtml += `
            <li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${parseInt(currentPage) + 1}" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        `;
        
        paginationList.innerHTML = paginationHtml;
    }

    function getStatusText(statusId) {
        switch (parseInt(statusId)) {
            case 1: return 'Pending Approval';
            case 2: return 'Approved';
            case 3: return 'Rejected';
            default: return 'Unknown';
        }
    }

    function getStatusBadgeClass(statusId) {
        switch (parseInt(statusId)) {
            case 1: return 'bg-warning text-dark';
            case 2: return 'bg-success';
            case 3: return 'bg-danger';
            default: return 'bg-secondary';
        }
    }

    function getStatusClass(statusId) {
        switch (parseInt(statusId)) {
            case 1: return 'details-pending';
            case 2: return 'details-approved';
            case 3: return 'details-declined';
            default: return '';
        }
    }

    function checkForStatusUpdates() {
        if (!lastCheckTime || !document.getElementById('enableRealTimeUpdates').checked) {
            return;
        }
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'booking-history.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
            if (xhr.status === 200) {
                const result = JSON.parse(xhr.responseText);
                if (result.error) {
                    return;
                }

                if (result.hasUpdates) {
                    // Refresh the full booking data
                    loadBookings(currentPage, currentStatus);
                    
                    // Show notification
                    const alertElement = document.getElementById('statusUpdateAlert');
                    alertElement.style.display = 'block';
                    
                    // Hide notification after 5 seconds
                    setTimeout(() => {
                        alertElement.style.display = 'none';
                    }, 5000);
                }
                
                // Update last check time
                lastCheckTime = result.currentTime;
            }
        };

        xhr.send(`check_updates=true&last_check_time=${encodeURIComponent(lastCheckTime)}&status=${currentStatus}`);
    }

    function startPolling() {
        // Clear any existing interval
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        
        // Set new interval
        pollingInterval = setInterval(checkForStatusUpdates, POLLING_INTERVAL_MS);
    }

    function stopPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }

    // Real-time updates toggle
    document.getElementById('enableRealTimeUpdates').addEventListener('change', function() {
        if (this.checked) {
            startPolling();
        } else {
            stopPolling();
        }
    });

    // Load initial bookings
    loadBookings();
    
    // Start polling for updates
    startPolling();

    // Handle visibility change to save resources
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, pause polling
            stopPolling();
        } else {
            // Page is visible again, reload data and restart polling if enabled
            loadBookings(currentPage, currentStatus);
            if (document.getElementById('enableRealTimeUpdates').checked) {
                startPolling();
            }
        }
    });

    // Automatically load bookings when dropdown value changes
    document.getElementById('status').addEventListener('change', function() {
        const status = this.value;
        loadBookings(1, status); // Reset to page 1 on filter
    });

    // Pagination click
    document.getElementById('pagination').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Find the closest anchor element (page link)
        const pageLink = e.target.closest('.page-link');
        if (!pageLink) return;
        
        // Check if the parent li is disabled
        if (pageLink.parentElement.classList.contains('disabled')) return;
        
        const page = pageLink.getAttribute('data-page');
        if (page) {
            loadBookings(page, currentStatus);
            
            // Scroll back to the top of the table
            document.getElementById('bookingTable').scrollIntoView({ behavior: 'smooth' });
        }
    });
});
