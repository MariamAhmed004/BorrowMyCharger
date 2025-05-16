function deleteChargePoint(chargePointId, buttonElement) {
    if (confirm('Are you sure you want to delete this charge point?')) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'adminDeleteChargePoint.php?id=' + encodeURIComponent(chargePointId), true);
        xhr.setRequestHeader('Content-Type', 'application/json');

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    const row = buttonElement.closest('tr');
                    if (row) {
                        row.remove();
                    }
                } else {
                    alert('Error deleting charge point.');
                }
            }
        };

        xhr.send();
    }
}


    // Add Charge Point Modal Functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get the button that opens the modal
        const addChargePointBtn = document.getElementById('addChargePointBtn');
        
        // Get the modal
        const homeownerModal = new bootstrap.Modal(document.getElementById('homeownerModal'));
        
        // When the user clicks on the button, open the modal
        addChargePointBtn.addEventListener('click', function() {
            homeownerModal.show();
        });
        
        // Add click event listeners to all homeowner items
        const homeownerItems = document.querySelectorAll('.homeowner-item');
        homeownerItems.forEach(function(item) {
            item.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                window.location.href = 'adminAddChargePoint.php?user_id=' + userId;
            });
        });
    });