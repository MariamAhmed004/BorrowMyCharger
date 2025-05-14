let markers = []; // Array to hold all markers
let map; // Declare map variable

function initMap() {
    const bahrainCenter = { lat: 26.0667, lng: 50.5577 };

    // Initialize Google Map
    map = new google.maps.Map(document.getElementById("charger-map"), {
        center: bahrainCenter,
        zoom: 10,
    });

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
    markers.forEach(marker => marker.setMap(null));
    markers = [];

    const xhr = new XMLHttpRequest();
    xhr.open("GET", `index.php?filter=${filter}&min_price=${minPrice}&max_price=${maxPrice}`, true);
    xhr.onload = function () {
        if (this.status === 200) {
            const chargePoints = JSON.parse(this.responseText);
            chargePoints.forEach(point => {
                const price = parseFloat(point.price_per_kwh);

                if (price >= minPrice && price <= maxPrice) {
                    const position = { lat: parseFloat(point.latitude), lng: parseFloat(point.longitude) };

                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                    });

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

                    const infoWindow = new google.maps.InfoWindow({
                        content: popupContent,
                    });

                    marker.addListener("click", () => {
                        infoWindow.open(map, marker);
                    });

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
            const userLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
            const userMarker = new google.maps.Marker({
                position: userLocation,
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: "blue", // Change color here
                    fillOpacity: 1,
                    strokeWeight: 2,
                    strokeColor: "white"
                },
            });

            const userInfoWindow = new google.maps.InfoWindow({
                content: "Your current location",
            });

            userMarker.addListener("click", () => {
                userInfoWindow.open(map, userMarker);
            });

            markers.push(userMarker);
            map.setCenter(userLocation); // Center the map on user location
        }, () => {
            console.error("Geolocation service failed.");
        });
    } else {
        console.error("Geolocation is not supported by this browser.");
    }
}

// Initialize map after DOM is ready
document.addEventListener("DOMContentLoaded", initMap);