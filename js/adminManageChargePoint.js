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
