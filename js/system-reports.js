// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Load initial data for the first tab (bookings)
    loadBookingsData();
    
    // Set up event listeners for filters and buttons
    setupEventListeners();
    
    // Set up Bootstrap tab functionality
    setupBootstrapTabs();
    
        // Add event listeners for search fields
    const userSearchInput = document.getElementById('user-search');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', filterUsers);
    }

    const chargePointSearchInput = document.getElementById('charge-point-search');
    if (chargePointSearchInput) {
        chargePointSearchInput.addEventListener('input', filterChargePoints);
    }
});

/**
 * Filter users based on search input
 */
function filterUsers() {
    const searchValue = document.getElementById('user-search').value.toLowerCase();
    const usersTable = document.getElementById('users-table').querySelector('tbody');
    const rows = usersTable.querySelectorAll('tr');

    rows.forEach(row => {
        const nameCell = row.cells[1]; // Assuming the name is in the second column
        if (nameCell) {
            const text = nameCell.textContent.toLowerCase();
            row.style.display = text.startsWith(searchValue) ? '' : 'none'; // Check if starts with
        }
    });
}

/**
 * Filter charge points based on search input
 */
function filterChargePoints() {
    const searchValue = document.getElementById('charge-point-search').value.toLowerCase();
    const chargePointsTable = document.getElementById('charge-points-table').querySelector('tbody');
    const rows = chargePointsTable.querySelectorAll('tr');

    rows.forEach(row => {
        const cityCell = row.cells[3]; // Assuming the city is in the fourth column
        if (cityCell) {
            const text = cityCell.textContent.toLowerCase();
            row.style.display = text.startsWith(searchValue) ? '' : 'none'; // Check if starts with
        }
    });
}

/**
 * Set up Bootstrap tab events without jQuery
 */
function setupBootstrapTabs() {
    // Create a tab instance manually for modern Bootstrap
    const tabLinks = document.querySelectorAll('#reportTabs a');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Use Bootstrap's API to show the tab
            // For Bootstrap 4.x, we need to create a tab instance manually
            const targetId = this.getAttribute('href');
            
            // Remove active class from all tabs and show the selected one
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            document.querySelectorAll('#reportTabs a').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Add active class to current tab and content
            this.classList.add('active');
            document.querySelector(targetId).classList.add('show', 'active');
            
            // Load data for the selected tab
            if (targetId === '#bookings') {
                loadBookingsData();
            } else if (targetId === '#users') {
                loadUsersData();
            } else if (targetId === '#charge-points') {
                loadChargePointsData();
            }
        });
    });
}

/**
 * Set up event listeners for filters and buttons
 */
function setupEventListeners() {
    // Booking status filter
    const bookingStatusFilter = document.getElementById('booking-status-filter');
    if (bookingStatusFilter) {
        bookingStatusFilter.addEventListener('change', loadBookingsData);
    }
    
    // User filters
    const userRoleFilter = document.getElementById('user-role-filter');
    const userStatusFilter = document.getElementById('user-status-filter');
    
    if (userRoleFilter) {
        userRoleFilter.addEventListener('change', function() {
            // Reset status filter when role filter changes
            if (userStatusFilter && this.value !== '0') {
                userStatusFilter.value = '0';
            }
            loadUsersData();
        });
    }
    
    if (userStatusFilter) {
        userStatusFilter.addEventListener('change', function() {
            // Reset role filter when status filter changes
            if (userRoleFilter && this.value !== '0') {
                userRoleFilter.value = '0';
            }
            loadUsersData();
        });
    }
    
    // Run SQL query button
    const runQueryBtn = document.getElementById('run-query-btn');
    if (runQueryBtn) {
        runQueryBtn.addEventListener('click', runCustomQuery);
    }
}

/**
 * Load bookings data based on selected filter
 */
function loadBookingsData() {
    const bookingsTable = document.getElementById('bookings-table').querySelector('tbody');
    bookingsTable.innerHTML = '<tr><td colspan="8" class="text-center">Loading booking data...</td></tr>';
    
    const statusFilter = document.getElementById('booking-status-filter');
    const statusId = statusFilter ? statusFilter.value : '0';
    
    const formData = new FormData();
    formData.append('action', 'get_bookings');
    formData.append('status_id', statusId);
    
    fetch('system-reports.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            renderBookingsTable(data.data);
        } else {
            showError(bookingsTable, 'Failed to load booking data');
        }
    })
    .catch(error => {
        showError(bookingsTable, 'Error loading booking data: ' + error.message);
    });
}

/**
 * Render bookings data in the table
 * @param {Array} bookings Array of booking objects
 */
function renderBookingsTable(bookings) {
    const bookingsTable = document.getElementById('bookings-table').querySelector('tbody');
    
    if (bookings.length === 0) {
        bookingsTable.innerHTML = '<tr><td colspan="8" class="text-center">No bookings found</td></tr>';
        return;
    }
    
    let html = '';
    bookings.forEach(booking => {
        html += `
            <tr>
                <td>${booking.booking_id}</td>
                <td>${booking.booking_date}</td>
                <td>${booking.booking_time}</td>
                <td>${booking.first_name} ${booking.last_name}</td>
                <td>${booking.email}</td>
                <td>${booking.charge_point_id}</td>
                <td>${booking.streetName}, ${booking.city_name}, ${booking.postcode}</td>
                <td>${booking.booking_status_title}</td>
            </tr>
        `;
    });
    
    bookingsTable.innerHTML = html;
}

/**
 * Load users data based on selected filters
 */
function loadUsersData() {
    const usersTable = document.getElementById('users-table').querySelector('tbody');
    usersTable.innerHTML = '<tr><td colspan="7" class="text-center">Loading user data...</td></tr>';
    
    const roleFilter = document.getElementById('user-role-filter');
    const statusFilter = document.getElementById('user-status-filter');
    
    const roleId = roleFilter ? roleFilter.value : '0';
    const statusId = statusFilter ? statusFilter.value : '0';
    
    const formData = new FormData();
    formData.append('action', 'get_users');
    
    if (roleId !== '0') {
        formData.append('role_id', roleId);
    } else if (statusId !== '0') {
        formData.append('status_id', statusId);
    }
    
    fetch('system-reports.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            renderUsersTable(data.data);
        } else {
            showError(usersTable, 'Failed to load user data');
        }
    })
    .catch(error => {
        showError(usersTable, 'Error loading user data: ' + error.message);
    });
}

/**
 * Render users data in the table
 * @param {Array} users Array of user objects
 */
function renderUsersTable(users) {
    const usersTable = document.getElementById('users-table').querySelector('tbody');
    
    if (users.length === 0) {
        usersTable.innerHTML = '<tr><td colspan="7" class="text-center">No users found</td></tr>';
        return;
    }
    
    let html = '';
    users.forEach(user => {
        html += `
            <tr>
                <td>${user.user_id}</td>
                <td>${user.first_name} ${user.last_name}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td>${user.phone_number || 'N/A'}</td>
                <td>${user.role_title}</td>
                <td>${user.user_account_status_title}</td>
            </tr>
        `;
    });
    
    usersTable.innerHTML = html;
}

/**
 * Load charge points data
 */
function loadChargePointsData() {
    const chargePointsTable = document.getElementById('charge-points-table').querySelector('tbody');
    chargePointsTable.innerHTML = '<tr><td colspan="7" class="text-center">Loading charge point data...</td></tr>';
    
    const formData = new FormData();
    formData.append('action', 'get_charge_points');
    
    fetch('system-reports.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            renderChargePointsTable(data.data);
        } else {
            showError(chargePointsTable, 'Failed to load charge point data');
        }
    })
    .catch(error => {
        showError(chargePointsTable, 'Error loading charge point data: ' + error.message);
    });
}

/**
 * Render charge points data in the table
 * @param {Array} chargePoints Array of charge point objects
 */
function renderChargePointsTable(chargePoints) {
    const chargePointsTable = document.getElementById('charge-points-table').querySelector('tbody');
    
    if (chargePoints.length === 0) {
        chargePointsTable.innerHTML = '<tr><td colspan="7" class="text-center">No charge points found</td></tr>';
        return;
    }
    
    let html = '';
    chargePoints.forEach(cp => {
        html += `
            <tr>
                <td>${cp.charge_point_id}</td>
                <td>${cp.first_name} ${cp.last_name}</td>
                <td>${cp.streetName}, ${cp.postcode}</td>
                <td>${cp.city_name}</td>
                <td>£${parseFloat(cp.price_per_kwh).toFixed(2)}</td>
                <td>${cp.availability_status_title}</td>
                <td>${cp.email}<br>${cp.phone_number || 'N/A'}</td>
            </tr>
        `;
    });
    
    chargePointsTable.innerHTML = html;
}

/**
 * Run a custom SQL query
 */
function runCustomQuery() {
    const sqlQuery = document.getElementById('sql-query').value.trim();
    const resultArea = document.getElementById('query-result-area');
    
    if (!sqlQuery) {
        resultArea.innerHTML = '<div class="alert alert-warning">Please enter a SQL query</div>';
        return;
    }
    
    // Check if it's a SELECT query (simple client-side validation)
    if (!sqlQuery.toLowerCase().startsWith('select')) {
        resultArea.innerHTML = '<div class="alert alert-danger">Only SELECT queries are allowed</div>';
        return;
    }
    
    resultArea.innerHTML = '<div class="alert alert-info">Running query...</div>';
    
    const formData = new FormData();
    formData.append('action', 'run_query');
    formData.append('sql', sqlQuery);
    
    fetch('system-reports.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderQueryResults(data.results, resultArea);
        } else {
            resultArea.innerHTML = `<div class="alert alert-danger">Error: ${data.error}</div>`;
        }
    })
    .catch(error => {
        resultArea.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
    });
}

/**
 * Render custom query results
 * @param {Array} results Query results
 * @param {HTMLElement} resultArea DOM element to render results in
 */
function renderQueryResults(results, resultArea) {
    if (!results || results.length === 0) {
        resultArea.innerHTML = '<div class="alert alert-info">Query executed successfully, but returned no results</div>';
        return;
    }
    
    // Get column names from the first result
    const columns = Object.keys(results[0]);
    
    let tableHtml = '<div class="table-responsive"><table class="table table-striped table-bordered">';
    
    // Table header
    tableHtml += '<thead><tr>';
    columns.forEach(column => {
        tableHtml += `<th>${column}</th>`;
    });
    tableHtml += '</tr></thead>';
    
    // Table body
    tableHtml += '<tbody>';
    results.forEach(row => {
        tableHtml += '<tr>';
        columns.forEach(column => {
            tableHtml += `<td>${row[column] !== null ? row[column] : 'NULL'}</td>`;
        });
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody></table></div>';
    
    resultArea.innerHTML = `
        <div class="alert alert-success">Query executed successfully. ${results.length} rows returned.</div>
        ${tableHtml}
    `;
}

/**
 * Show error message in a table
 * @param {HTMLElement} tableBody Table body element
 * @param {string} message Error message
 */
function showError(tableBody, message) {
    tableBody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">${message}</td></tr>`;
}