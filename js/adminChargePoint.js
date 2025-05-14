// Admin functionality for managing charge points
document.addEventListener('DOMContentLoaded', function() {
    // Add the Admin Add Charge Point button to the page
    addAdminButton();
    
    // Set up event listeners for the admin functionality
    document.addEventListener('click', function(event) {
        // Admin button click handler
        if (event.target.matches('#adminAddChargePointBtn') || event.target.closest('#adminAddChargePointBtn')) {
            fetchHomeOwnersWithoutChargePoints();
        }
        
        // Select homeowner click handler
        if (event.target.matches('.select-homeowner') || event.target.closest('.select-homeowner')) {
            const userId = event.target.closest('.select-homeowner').dataset.userId;
            const userName = event.target.closest('.select-homeowner').dataset.userName;
            selectHomeOwner(userId, userName);
        }
    });
});

/**
 * Add the admin button to the page
 */
function addAdminButton() {
    const container = document.querySelector('.container.mt-5');
    
    // Create button container
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'mb-4 text-end';
    
    // Create button
    const button = document.createElement('button');
    button.id = 'adminAddChargePointBtn';
    button.className = 'btn btn-primary';
    button.innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Charge Point (Admin)';
    
    // Append to DOM
    buttonContainer.appendChild(button);
    container.insertBefore(buttonContainer, container.firstChild);
}

/**
 * Fetch homeowners without charge points
 */
function fetchHomeOwnersWithoutChargePoints() {
    // Show loading indicator
    showAlert('Loading', 'Fetching homeowners data...', 'info');
    
    // Create AJAX request using Fetch API
    fetch('admin-actions.php?action=getHomeOwnersWithoutChargePoints')
        .then(response => {
            // Remove loading alert
            const loadingAlert = document.querySelector('.alert.alert-info');
            if (loadingAlert) loadingAlert.remove();
            
            if (!response.ok) {
                throw new Error(`Server returned status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    // Try to parse as JSON
                    return JSON.parse(text);
                } catch (e) {
                    console.error("Server returned non-JSON response:", text);
                    throw new Error("Server returned invalid JSON. Check PHP errors.");
                }
            });
        })
        .then(data => {
            if (data.success) {
                showHomeOwnersModal(data.homeowners);
            } else {
                showAlert('Error', data.message || 'Failed to fetch homeowners', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error', 'Failed to connect to server: ' + error.message, 'error');
        });
}

/**
 * Show modal with homeowners list
 */
function showHomeOwnersModal(homeowners) {
    // Remove existing modal if any
    let existingModal = document.getElementById('homeownersModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal structure
    const modal = document.createElement('div');
    modal.id = 'homeownersModal';
    modal.className = 'modal fade';
    modal.tabIndex = '-1';
    modal.setAttribute('aria-labelledby', 'homeownersModalLabel');
    modal.setAttribute('aria-hidden', 'true');
    
    let modalContent = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="homeownersModalLabel">Select a Homeowner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
    `;
    
    if (homeowners.length === 0) {
        modalContent += `<p class="text-center">No homeowners without charge points found.</p>`;
    } else {
        modalContent += `<div class="list-group">`;
        homeowners.forEach(homeowner => {
            modalContent += `
                <button type="button" class="list-group-item list-group-item-action select-homeowner" 
                        data-user-id="${homeowner.user_id}" 
                        data-user-name="${homeowner.first_name} ${homeowner.last_name}">
                    <strong>${homeowner.first_name} ${homeowner.last_name}</strong>
                    <br>
                    <small>Email: ${homeowner.email} | Phone: ${homeowner.phone_number}</small>
                </button>
            `;
        });
        modalContent += `</div>`;
    }
    
    modalContent += `
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    `;
    
    modal.innerHTML = modalContent;
    document.body.appendChild(modal);
    
    // Initialize Bootstrap modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

/**
 * Select a homeowner and open the charge point modal
 */
function selectHomeOwner(userId, userName) {
    // Close the homeowners modal
    const homeownersModal = document.getElementById('homeownersModal');
    const bsHomeownersModal = bootstrap.Modal.getInstance(homeownersModal);
    bsHomeownersModal.hide();
    
    // Wait a bit for the modal to close properly
    setTimeout(() => {
        // Make sure the charge point form exists
        let chargePointForm = document.getElementById('chargePointForm');
        if (!chargePointForm) {
            showAlert('Error', 'Charge point form not found', 'error');
            return;
        }
        
        // Reset the form
        chargePointForm.reset();
        
        // Set the selected user ID in a hidden field
        let selectedUserIdField = document.getElementById('selected_user_id');
        if (!selectedUserIdField) {
            selectedUserIdField = document.createElement('input');
            selectedUserIdField.type = 'hidden';
            selectedUserIdField.id = 'selected_user_id';
            selectedUserIdField.name = 'selected_user_id';
            chargePointForm.appendChild(selectedUserIdField);
        }
        selectedUserIdField.value = userId;
        
        // Update the modal title to include the homeowner's name
        let modalTitle = document.getElementById('modalTitle');
        if (modalTitle) {
            modalTitle.textContent = `Add Charge Point for ${userName}`;
        }
        
        // Show the charge point modal
        const chargePointModal = document.getElementById('chargePointModal');
        if (chargePointModal) {
            const bsChargePointModal = new bootstrap.Modal(chargePointModal);
            bsChargePointModal.show();
        } else {
            showAlert('Error', 'Charge point modal not found', 'error');
        }

        // Add event listener for form submission
        chargePointForm.addEventListener('submit', addChargePoint);
    }, 300); // Small delay to allow the first modal to close properly
}

/**
 * Add a charge point for the selected homeowner
 */
function addChargePoint(event) {
    event.preventDefault(); // Prevent the default form submission

    const chargePointForm = document.getElementById('chargePointForm');
    const formData = new FormData(chargePointForm);

    // Append the selected user ID to the form data
    const selectedUserIdField = document.getElementById('selected_user_id');
    if (selectedUserIdField) {
        formData.append('user_id', selectedUserIdField.value);
    }

    fetch('admin-actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Handle response
        if (data.success) {
            showAlert('Success', data.message, 'success');
            // Optionally, refresh the charge points list or close the modal
        } else {
            showAlert('Error', data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('Error', 'Failed to add charge point: ' + error.message, 'error');
    });
}

/**
 * Show an alert message to the user
 */
function showAlert(title, message, type) {
    // Remove existing alerts of the same type
    const existingAlerts = document.querySelectorAll(`.alert.alert-${type === 'error' ? 'danger' : type}`);
    existingAlerts.forEach(alert => alert.remove());
    
    // Create a Bootstrap alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    
    alertDiv.innerHTML = `
        <strong>${title}</strong>: ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Insert alert at the top of the container
    const container = document.querySelector('.container.mt-5');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto remove after 5 seconds (except for info alerts)
    if (type !== 'info') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }
    
    
}