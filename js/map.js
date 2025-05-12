let markers = []; // Array to hold all markers
let map; // Declare map variable

function initMap() {
    const bahrainCenter = { lat: 26.0667, lng: 50.5577 };

    map = new google.maps.Map(document.getElementById("charger-map"), {
        zoom: 12,
        center: bahrainCenter,
    });

    loadMarkers("all"); // Load all markers by default

    document.getElementById("availability-filter").addEventListener("change", function() {
        const filterValue = this.value.toLowerCase(); // Convert to lowercase
        loadMarkers(filterValue);
    });

    // Add event listeners for price inputs
    document.getElementById("min-price").addEventListener("input", function() {
        loadMarkers(document.getElementById("availability-filter").value.toLowerCase());
    });

    document.getElementById("max-price").addEventListener("input", function() {
        loadMarkers(document.getElementById("availability-filter").value.toLowerCase());
    });
}

function loadMarkers(filter) {
    const minPrice = parseFloat(document.getElementById("min-price").value) || 0; // Default to 0 if empty
    const maxPrice = parseFloat(document.getElementById("max-price").value) || Infinity; // Default to Infinity if empty

    // Clear existing markers
    markers.forEach(marker => marker.setMap(null));
    markers = [];

    const xhr = new XMLHttpRequest();
    xhr.open("GET", `index.php?filter=${filter}&min_price=${minPrice}&max_price=${maxPrice}`, true);
    xhr.onload = function() {
        if (this.status === 200) {
            const chargePoints = JSON.parse(this.responseText);
            chargePoints.forEach(point => {
                const price = parseFloat(point.price_per_kwh);
                
                // Check if the charge point's price is within the specified range
                if (price >= minPrice && price <= maxPrice) {
                    const position = { lat: parseFloat(point.latitude), lng: parseFloat(point.longitude) };
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: `Charge Point ID: ${point.charge_point_id}`
                    });

                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <strong>Charge Point ID:</strong> ${point.charge_point_id}<br>
                            <strong>Price per kWh:</strong> BD${point.price_per_kwh}<br>
                            <strong>Status:</strong> ${point.availability_status_title}<br>
                            <strong>Street:</strong> ${point.streetName}<br>
                            <strong>House Number:</strong> ${point.house_number}<br>
                            <strong>Block:</strong> ${point.block}<br>
                            <strong>Road:</strong> ${point.road}<br>
                            <img src="${point.charge_point_picture_url}" alt="Charge Point Image" style="width:100px;height:auto;">
                        `
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