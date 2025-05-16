// Status polling functionality for charge points
document.addEventListener('DOMContentLoaded', function() {
    // Configuration for polling
    const POLLING_INTERVAL = 5000;
    let pollingTimer = null;
    let isPolling = false;
    
    // Start polling when page is loaded and visible
    startStatusPolling();
    
    // Handle page visibility changes to conserve resources
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            startStatusPolling();
        } else {
            stopStatusPolling();
        }
    });
    
    // Function to start polling for status updates
    function startStatusPolling() {
        if (!isPolling) {
            isPolling = true;
            // Initial check
            checkChargePointStatus();
            
            // Set up interval for subsequent checks
            pollingTimer = setInterval(checkChargePointStatus, POLLING_INTERVAL);
        }
    }
    
    // Function to stop polling
    function stopStatusPolling() {
        if (isPolling) {
            clearInterval(pollingTimer);
            isPolling = false;
        }
    }
    
    // Function to check charge point statuses
    function checkChargePointStatus() {
        // Get all charge point IDs from the current page
        const chargePoints = document.querySelectorAll('.charge-point');
        const chargePointIds = [];
        
        chargePoints.forEach(chargePoint => {
            const bookBtn = chargePoint.querySelector('.book-btn');
            if (bookBtn && bookBtn.dataset.id) {
                chargePointIds.push(bookBtn.dataset.id);
            }
        });
        
        // If no charge points on page, don't proceed
        if (chargePointIds.length === 0) return;
        
        // Build request URL with charge point IDs
        const url = new URL('check-status.php', window.location.origin);
        url.searchParams.append('ids', chargePointIds.join(','));
        
        // Make the AJAX request
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                updateChargePointStatuses(data);
            })
            .catch(error => {
                console.error('Error fetching charge point statuses:', error);
            });
    }
    
    // Function to update UI with new status information
    function updateChargePointStatuses(statusData) {
        if (!statusData || Object.keys(statusData).length === 0) return;
        
        const chargePoints = document.querySelectorAll('.charge-point');
        
        chargePoints.forEach(chargePoint => {
            const bookBtn = chargePoint.querySelector('.book-btn');
            if (!bookBtn || !bookBtn.dataset.id) return;
            
            const chargePointId = bookBtn.dataset.id;
            const statusInfo = statusData[chargePointId];
            
            if (statusInfo) {
                const statusBadge = chargePoint.querySelector('.status-badge');
       // In the updateChargePointStatuses function
if (statusBadge) {
    // Remove all status-related classes
    statusBadge.className = 'status-badge';
    // Add the new status class
    statusBadge.classList.add(statusInfo.statusClass);
    // Update the status text
    statusBadge.textContent = statusInfo.status;
    
    // If the status has changed, briefly highlight the status badge
    if (!statusBadge.dataset.lastStatus || 
        statusBadge.dataset.lastStatus !== statusInfo.statusId) {
        // Store the current status ID
        statusBadge.dataset.lastStatus = statusInfo.statusId;
        
        // Add highlight effect
        statusBadge.classList.add('status-updated');
        setTimeout(() => {
            statusBadge.classList.remove('status-updated');
        }, 2000);
    }
}
            }
        });
    }
});