let markers = []; // Array to hold all markers
let map; // Declare map variable

function initMap() {
    const bahrainCenter = [26.0667, 50.5577];

    // Initialize Leaflet Map
    map = L.map('charger-map').setView(bahrainCenter, 10);

    // Add OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    loadMarkers("all");
    addUserLocationMarker();

    document.getElementById("availability-filter").addEventListener("change", function () {
        loadMarkers(this.value.toLowerCase());
    });

    document.getElementById("min-price").addEventListener("input", function () {
        loadMarkers(document.getElementById("availability-filter").value.toLowerCase());
    });

    document.getElementById("max-price").addEventListener("input", function () {
        loadMarkers(document.getElementById("availability-filter").value.toLowerCase());
    });
}

function loadMarkers(filter) {
    const minPrice = parseFloat(document.getElementById("min-price").value) || 0;
    const maxPrice = parseFloat(document.getElementById("max-price").value) || Infinity;

    // Clear existing markers from map
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];

    const xhr = new XMLHttpRequest();
    xhr.open("GET", `index.php?filter=${filter}&min_price=${minPrice}&max_price=${maxPrice}`, true);
    xhr.onload = function () {
        if (this.status === 200) {
            const chargePoints = JSON.parse(this.responseText);
            chargePoints.forEach(point => {
                const price = parseFloat(point.price_per_kwh);

                if (price >= minPrice && price <= maxPrice) {
                    const position = [parseFloat(point.latitude), parseFloat(point.longitude)];

                    const marker = L.marker(position).addTo(map);

                    const popupContent = `
                        <strong>Charge Point ID:</strong> ${point.charge_point_id}<br>
                        <strong>Price per kWh:</strong> BD${point.price_per_kwh}<br>
                        <strong>Status:</strong> ${point.availability_status_title}<br>
                        <strong>Street:</strong> ${point.streetName}<br>
                        <strong>House Number:</strong> ${point.house_number}<br>
                        <strong>Block:</strong> ${point.block}<br>
                        <strong>Road:</strong> ${point.road}<br>
                        <img src="${point.charge_point_picture_url}" alt="Charge Point Image" style="width:100px;height:auto;">
                    `;

                    marker.bindPopup(popupContent);

                    markers.push(marker);
                }
            });
        }
    };
    xhr.send();
}

function addUserLocationMarker() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const userLocation = [position.coords.latitude, position.coords.longitude];
            const userMarker = L.marker(userLocation, {
                icon: L.divIcon({
                    className: 'user-marker',
                    html: '<div style="background-color: blue; border-radius: 50%; width: 20px; height: 20px; border: 2px solid white;"></div>'
                })
            }).addTo(map);

            userMarker.bindPopup("Your current location").openPopup();
            markers.push(userMarker);
            map.setView(userLocation); // Center the map on user location
        }, () => {
            console.error("Geolocation service failed.");
        });
    } else {
        console.error("Geolocation is not supported by this browser.");
    }
}

// Initialize map after DOM is ready
document.addEventListener("DOMContentLoaded", initMap);