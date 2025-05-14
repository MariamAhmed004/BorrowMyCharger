
        document.addEventListener('DOMContentLoaded', function() {
            const bookingId = document.getElementById('booking-id').value;

            function initializeMap(lat, lng, address) {
                const map = L.map('map').setView([lat, lng], 15);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(map);

                const marker = L.marker([lat, lng]).addTo(map)
                    .bindPopup(`<b>Charge Point</b><br>${address}`)
                    .openPopup();
            }

            function showMapError(message) {
                document.getElementById('map').innerHTML =
                    '<div class="error-message">' + message + '</div>';
            }

            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get-coordinates.php?booking_id=' + encodeURIComponent(bookingId), true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            initializeMap(parseFloat(response.data.latitude), parseFloat(response.data.longitude), response.data.address);
                        } else {
                            showMapError('Error: ' + response.message);
                        }
                    } catch (e) {
                        showMapError('Error parsing response: ' + e.message);
                    }
                } else {
                    showMapError('Error fetching coordinates. Status: ' + xhr.status);
                }
            };
            xhr.onerror = function() {
                showMapError('Network error occurred while fetching coordinates');
            };
            xhr.send();
        });
