document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for action buttons
    document.querySelectorAll('.suspend-link, .unsuspend-link, .approve-link').forEach(link => {
        link.addEventListener('click', handleStatusAction);
    });
    
    document.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', handleDeleteAction);
    });

    // Add event listeners for filters
    document.getElementById('nameFilter').addEventListener('change', applyFilters);
    document.getElementById('roleFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
    
    // Set initial filter values from URL parameters
    setInitialFilterValues();
    
    // Update pagination links with current filters
    updatePaginationLinks();
});

function setInitialFilterValues() {
    const urlParams = new URLSearchParams(window.location.search);
    
    const nameFilter = urlParams.get('name');
    const roleFilter = urlParams.get('role');
    const statusFilter = urlParams.get('status');
    
    if (nameFilter) {
        document.getElementById('nameFilter').value = nameFilter;
    }
    
    if (roleFilter) {
        document.getElementById('roleFilter').value = roleFilter;
    }
    
    if (statusFilter) {
        document.getElementById('statusFilter').value = statusFilter;
    }
}

function updatePaginationLinks() {
    // Get current filter values
    const nameFilter = document.getElementById('nameFilter').value;
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    console.log("Updating pagination with filters:", { nameFilter, roleFilter, statusFilter });
    
    // Update all pagination links to include current filters
    document.querySelectorAll('.pagination .page-link').forEach(link => {
        if (!link.closest('.page-item').classList.contains('disabled') && 
            !link.closest('.page-item').classList.contains('active')) {
            
            const href = new URL(link.getAttribute('href'), window.location.origin + window.location.pathname);
            
            // Preserve the page parameter from the original link
            const page = new URLSearchParams(href.search).get('page');
            
            // Clear existing parameters and set new ones
            href.search = '';
            const params = new URLSearchParams();
            
            if (page) params.set('page', page);
            // Always include filters in params, even if empty
            params.set('name', nameFilter);
            params.set('role', roleFilter);
            params.set('status', statusFilter);
            
            href.search = params.toString();
            link.setAttribute('href', href.search);
            
            // Add click handler for AJAX pagination
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadProfilesWithAjax(link.getAttribute('href'));
            });
            
            console.log("Updated link:", link.getAttribute('href'));
        }
    });
}

function applyFilters() {
    // Get filter values
    const nameFilter = document.getElementById('nameFilter').value;
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    // Create URL with filters
    const url = new URL(window.location.pathname, window.location.origin);
    const params = new URLSearchParams();
    
    // Always start at page 1 when applying new filters
    params.set('page', '1');
    
    // Always include filters in params, even if empty
    params.set('name', nameFilter);
    params.set('role', roleFilter);
    params.set('status', statusFilter);
    
    url.search = params.toString();
    
    console.log("Applying filters: ", { nameFilter, roleFilter, statusFilter });
    
    // Use AJAX to load filtered results instead of navigating
    loadProfilesWithAjax(url.search);
    
    // Update browser URL without refreshing
    window.history.pushState({}, '', url.toString());
}

function loadProfilesWithAjax(queryString) {
    // Create XHR
    const xhr = new XMLHttpRequest();
    xhr.open('GET', window.location.pathname + queryString, true);
    
    // Set custom header to indicate AJAX request
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            // Create a temporary container for the response HTML
            const tempContainer = document.createElement('div');
            tempContainer.innerHTML = xhr.responseText;
            
            // Extract the table content from the response
            const newTableContainer = tempContainer.querySelector('#tableContainer');
            if (newTableContainer) {
                document.getElementById('tableContainer').innerHTML = newTableContainer.innerHTML;
            }
            
            // Update pagination if present
            const newPagination = tempContainer.querySelector('nav[aria-label="User profiles pagination"]');
            const currentPagination = document.querySelector('nav[aria-label="User profiles pagination"]');
            
            if (currentPagination) {
                if (newPagination) {
                    currentPagination.innerHTML = newPagination.innerHTML;
                    currentPagination.style.display = '';
                } else {
                    currentPagination.style.display = 'none';
                }
            }
            
            // Update records info text
            const newRecordsInfo = tempContainer.querySelector('.text-muted');
            if (newRecordsInfo && newRecordsInfo.textContent.includes('Showing')) {
                const currentRecordsInfo = document.querySelector('.text-muted');
                if (currentRecordsInfo) {
                    currentRecordsInfo.textContent = newRecordsInfo.textContent;
                }
            }
            
            // Check for no results message
            const noResultsMsg = document.getElementById('noResultsMessage');
            const hasResults = document.querySelectorAll('#profilesTable tbody tr:not([colspan])').length > 0;
            if (noResultsMsg) {
                noResultsMsg.style.display = hasResults ? 'none' : 'block';
            }
            
            // Re-attach event listeners for the new content
            attachEventListeners();
            
            // Update pagination links with current filters
            updatePaginationLinks();
        } else {
            console.error('Error loading filtered results:', xhr.statusText);
        }
    };
    
    xhr.onerror = function() {
        console.error('Network error occurred');
    };
    
    xhr.send();
}

function attachEventListeners() {
    // Re-attach event listeners for action buttons
    document.querySelectorAll('.suspend-link, .unsuspend-link, .approve-link').forEach(link => {
        link.addEventListener('click', handleStatusAction);
    });
    
    document.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', handleDeleteAction);
    });
}

function handleStatusAction(event) {
    event.preventDefault();
    const link = event.currentTarget;
    const action = link.dataset.action;
    const userId = link.dataset.userId;
    const name = link.dataset.name;
    
    let confirmMessage = '';
    switch(action) {
        case 'suspend':
            confirmMessage = `Are you sure you want to suspend ${name}'s account?`;
            break;
        case 'unsuspend':
            confirmMessage = `Are you sure you want to unsuspend ${name}'s account?`;
            break;
        case 'approve':
            confirmMessage = `Are you sure you want to approve ${name}'s account?`;
            break;
    }
    
    if (confirm(confirmMessage)) {
        updateUserStatus(userId, action, link);
    }
}

function updateUserStatus(userId, action, linkElement) {
    // Create form data
    const formData = new FormData();
    formData.append('action', action);
    formData.append('userId', userId);
    
    // Get current URL with all parameters
    const url = window.location.href;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            const row = linkElement.closest('tr');
            
            // Update status cell
            const statusCell = row.querySelector('td:nth-child(5)');
            const statusTitle = data.status;
            const statusLower = statusTitle.toLowerCase();
            statusCell.innerHTML = `<span class="status-badge status-${statusLower}">${statusTitle}</span>`;
            
            // Update actions cell
            const fullName = linkElement.dataset.name;
            updateActionsCell(row, statusTitle, userId, fullName);
            
            // Show success message
            alert(data.message);
        } else {
            alert('Failed to update user status: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the user status.');
    });
}

function updateActionsCell(row, status, userId, name) {
    const actionsCell = row.querySelector('.actions-cell');
    const roleCell = row.querySelector('td:nth-child(4)');
    const role = roleCell.textContent.trim();
    
    let actions = '';
    
    // Suspend/Unsuspend button based on status
    if (status.toLowerCase() === 'suspended') {
        actions += `<a href="#" class="link unsuspend-link" 
                     data-action="unsuspend" 
                     data-user-id="${userId}"
                     data-name="${name}">
                      Unsuspend
                  </a>`;
    } else {
        actions += `<a href="#" class="link suspend-link" 
                     data-action="suspend" 
                     data-user-id="${userId}"
                     data-name="${name}">
                      Suspend
                  </a>`;
    }
    
    // Approve button only for pending homeowners
    if (role.toLowerCase() === 'homeowner' && status.toLowerCase() === 'pending') {
        actions += `<a href="#" class="link approve-link" 
                     data-action="approve" 
                     data-user-id="${userId}"
                     data-name="${name}">
                      Approve
                  </a>`;
    }
    
    // Delete button always present
    actions += `<a href="#" class="link delete-link" 
                 data-user-id="${userId}"
                 data-name="${name}">
                  Delete
              </a>`;
    
    actionsCell.innerHTML = actions;
    
    // Add new event listeners to the updated buttons
    actionsCell.querySelectorAll('.suspend-link, .unsuspend-link, .approve-link').forEach(link => {
        link.addEventListener('click', handleStatusAction);
    });
    
    actionsCell.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', handleDeleteAction);
    });
}

function handleDeleteAction(event) {
    event.preventDefault();
    const link = event.currentTarget;
    const userId = link.dataset.userId;
    const name = link.dataset.name;
    
    if (confirm(`Are you sure you want to delete ${name}'s account?`)) {
        deleteUser(userId);
    }
}

function deleteUser(userId) {
    // Get current URL with all parameters (preserves filters and pagination)
    const url = window.location.href;
    
    fetch(url, {
        method: 'DELETE',
        body: new URLSearchParams({ id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Check if this was the last user on the current page
            const table = document.getElementById("profilesTable");
            const tbody = table.querySelector('tbody');
            const currentRows = tbody.querySelectorAll('tr:not([colspan])');
            
            if (currentRows.length === 1) {
                // If this is the last item on the page and not the first page
                const urlParams = new URLSearchParams(window.location.search);
                const currentPage = parseInt(urlParams.get('page') || '1');
                
                if (currentPage > 1) {
                    // Load the previous page while preserving filters
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('page', (currentPage - 1).toString());
                    
                    // Use AJAX to load the previous page
                    loadProfilesWithAjax(newUrl.search);
                    
                    // Update browser URL without refreshing
                    window.history.pushState({}, '', newUrl.toString());
                    
                    // Show success message
                    alert(data.message);
                    return;
                }
            }
            
            // Remove the row from the table
            const rowToRemove = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (rowToRemove) {
                rowToRemove.remove();
            }
            
            // Check if table is now empty
            const remainingUserRows = tbody.querySelectorAll('tr:not([colspan])');
            
            if (remainingUserRows.length === 0) {
                // Show empty state
                tbody.innerHTML = `<tr>
                    <td colspan="6" class="text-center">
                        <div class="alert alert-info my-3">No users found.</div>
                    </td>
                </tr>`;
                
                // Hide pagination if no users left
                const paginationNav = document.querySelector('nav[aria-label="User profiles pagination"]');
                if (paginationNav) {
                    paginationNav.style.display = 'none';
                }
                
                // Update records info
                const recordsInfo = document.querySelector('.text-muted');
                if (recordsInfo && recordsInfo.textContent.includes('Showing')) {
                    recordsInfo.textContent = 'Showing 0 to 0 of 0 users';
                }
                
                // Show no results message
                const noResultsMsg = document.getElementById('noResultsMessage');
                if (noResultsMsg) {
                    noResultsMsg.style.display = 'block';
                }
            }
            
            alert(data.message);
        } else {
            alert('Failed to delete account: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the account.');
    });
}

function clearFilters() {
    // Reset all filter dropdowns
    document.getElementById('nameFilter').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    
    // Apply the empty filters
    applyFilters();
}