
        document.addEventListener('DOMContentLoaded', function() {
            const bookingId = document.getElementById('booking-id').value;

            function initializeMap(lat, lng, address) {
                const map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 15,
                    center: { lat: lat, lng: lng }
                });

                const marker = new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    title: "Charge Point"
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `<b>Charge Point</b><br>${address}`
                });

                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });

                infoWindow.open(map, marker);
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
