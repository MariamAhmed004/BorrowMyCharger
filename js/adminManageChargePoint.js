function deleteChargePoint(chargePointId, buttonElement) {
    if (confirm('Are you sure you want to delete this charge point? This action cannot be undone.')) {
        // Disable the button to prevent multiple clicks
        buttonElement.disabled = true;
        buttonElement.innerHTML = 'Deleting...';
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'adminDeleteChargePoint.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                // Re-enable the button
                buttonElement.disabled = false;
                buttonElement.innerHTML = 'Delete';
                
                // Debug: Log the raw response
                console.log('Response status:', xhr.status);
                console.log('Response text:', xhr.responseText);
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const row = buttonElement.closest('tr');
                            if (row) {
                                // Add fade out animation
                                row.style.opacity = '0.5';
                                row.style.transition = 'opacity 0.3s';
                                
                                setTimeout(() => {
                                    row.remove();
                                    updatePaginationAfterDelete();
                                }, 300);
                            }
                            
                            // Show success message if available
                            if (response.message) {
                                showMessage(response.message, 'success');
                            }
                        } else {
                            alert('Error: ' + (response.message || 'Failed to delete charge point'));
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        console.error('Raw response:', xhr.responseText);
                        alert('Error processing server response. Check console for details.');
                    }
                } else {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert('Error: ' + (response.message || 'Failed to delete charge point'));
                    } catch (e) {
                        console.error('Error parsing error response:', e);
                        console.error('Raw response:', xhr.responseText);
                        alert('Server error occurred. Status: ' + xhr.status);
                    }
                }
            }
        };
        
        xhr.onerror = function() {
            // Re-enable the button
            buttonElement.disabled = false;
            buttonElement.innerHTML = 'Delete';
            console.error('Network error occurred');
            alert('Network error occurred. Please check your connection and try again.');
        };
        
        // Send the request with charge point ID
        xhr.send('id=' + encodeURIComponent(chargePointId));
    }
}

// Function to show success/error messages (optional enhancement)
function showMessage(message, type) {
    // Create a simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '300px';
    
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

// Function to update pagination after deletion
function updatePaginationAfterDelete() {
    const currentUrl = new URL(window.location);
    const currentPage = parseInt(currentUrl.searchParams.get('page') || '1');
    const remainingRows = document.querySelectorAll('#charge-points-container tr').length;
    
    // If we deleted the last item on the current page and we're not on page 1, go to previous page
    if (remainingRows === 0 && currentPage > 1) {
        currentUrl.searchParams.set('page', currentPage - 1);
        window.location.href = currentUrl.toString();
    } else {
        // Just reload to update the pagination info
        window.location.reload();
    }
}

// Function to navigate to a different page
function navigateToPage(page) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('page', page);
    window.location.href = currentUrl.toString();
}

// Function to change items per page
function changeItemsPerPage(itemsPerPage) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('itemsPerPage', itemsPerPage);
    currentUrl.searchParams.set('page', '1'); // Reset to first page
    window.location.href = currentUrl.toString();
}

// Add Charge Point Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get the button that opens the modal
    const addChargePointBtn = document.getElementById('addChargePointBtn');
    
    // Get the modal
    const homeownerModal = new bootstrap.Modal(document.getElementById('homeownerModal'));
    
    // When the user clicks on the button, open the modal
    if (addChargePointBtn) {
        addChargePointBtn.addEventListener('click', function() {
            homeownerModal.show();
        });
    }
    
    // Add click event listeners to all homeowner items
    const homeownerItems = document.querySelectorAll('.homeowner-item');
    homeownerItems.forEach(function(item) {
        item.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            window.location.href = 'adminAddChargePoint.php?user_id=' + userId;
        });
    });
    
    // Pagination event listeners
    const paginationLinks = document.querySelectorAll('.page-link');
    paginationLinks.forEach(function(link) {
        if (!link.closest('.page-item').classList.contains('disabled') && 
            !link.closest('.page-item').classList.contains('active')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = this.getAttribute('data-page');
                if (page) {
                    navigateToPage(page);
                }
            });
        }
    });
    
    // Items per page change listener
    const itemsPerPageSelect = document.getElementById('itemsPerPage');
    if (itemsPerPageSelect) {
        itemsPerPageSelect.addEventListener('change', function() {
            changeItemsPerPage(this.value);
        });
    }
});