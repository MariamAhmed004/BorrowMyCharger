document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for action buttons
    document.querySelectorAll('.suspend-link, .unsuspend-link, .approve-link').forEach(link => {
        link.addEventListener('click', handleStatusAction);
    });
    
    document.querySelectorAll('.delete-link').forEach(link => {
        link.addEventListener('click', handleDeleteAction);
    });

    // Initialize filters
    filterTable();
});

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
    
    // Send AJAX request
    fetch(window.location.href, {
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
    fetch(window.location.href, {
        method: 'DELETE',
        body: new URLSearchParams({ id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from the table
            const rowToRemove = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (rowToRemove) {
                rowToRemove.remove();
            }
            
            // Check if table is now empty
            const table = document.getElementById("profilesTable");
            const tbody = table.querySelector('tbody');
            const remainingUserRows = tbody.querySelectorAll('tr:not([colspan])');
            
            if (remainingUserRows.length === 0) {
                // Show empty state
                tbody.innerHTML = `<tr>
                    <td colspan="6" class="text-center">
                        <div class="alert alert-info my-3">No users found.</div>
                    </td>
                </tr>`;
            }
            
            // Update filter display
            filterTable();
            
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

function filterTable() {
    const nameFilter = document.getElementById("nameFilter").value.toLowerCase();
    const roleFilter = document.getElementById("roleFilter").value.toLowerCase();
    const statusFilter = document.getElementById("statusFilter").value.toLowerCase();

    const table = document.getElementById("profilesTable");
    const rows = table.getElementsByTagName("tr");
    const noResultsMessage = document.getElementById("noResultsMessage");
    
    let visibleRowCount = 0;

    // Skip header row (i=0)
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName("td");
        
        // Skip if this is the "No users found" message row when table is initially empty
        if (cells.length === 1 && cells[0].getAttribute('colspan')) {
            continue;
        }
        
        const name = cells[0].textContent.toLowerCase();
        const role = cells[3].textContent.toLowerCase();
        const status = cells[4].textContent.toLowerCase().trim();

        const nameMatch = nameFilter === "" || name.includes(nameFilter);
        const roleMatch = roleFilter === "" || role.includes(roleFilter);
        const statusMatch = statusFilter === "" || status.includes(statusFilter);

        if (nameMatch && roleMatch && statusMatch) {
            rows[i].style.display = "";
            visibleRowCount++;
        } else {
            rows[i].style.display = "none";
        }
    }
    
    // Show/hide no results message
    if (visibleRowCount === 0 && rows.length > 1) {
        table.style.display = "none";
        noResultsMessage.style.display = "block";
    } else {
        table.style.display = "table";
        noResultsMessage.style.display = "none";
    }
}

function clearFilters() {
    document.getElementById("nameFilter").value = "";
    document.getElementById("roleFilter").value = "";
    document.getElementById("statusFilter").value = "";
    
    // Reset the table display
    filterTable();
    
    // Ensure table is visible and no results message is hidden
    const table = document.getElementById("profilesTable");
    const noResultsMessage = document.getElementById("noResultsMessage");
    
    // Only show table if we have actual user data
    const tbody = table.querySelector('tbody');
    const remainingUserRows = tbody.querySelectorAll('tr:not([colspan])');
    
    if (remainingUserRows.length > 0) {
        table.style.display = "table";
        noResultsMessage.style.display = "none";
    }
}