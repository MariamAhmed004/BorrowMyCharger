   document.addEventListener('DOMContentLoaded', function() {
          // Initialize the map with a default center (will be replaced by user location if available)
          var map = L.map('charger-map').setView([53.4808, -2.2426], 12); // Default to Manchester coordinates

          // Add OpenStreetMap tile layer
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
               attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          }).addTo(map);

          // Add sample charger markers
          var chargers = [
               {lat: 53.4808, lng: -2.2426, name: "Sarah M.'s Charger", type: "7kW", price: "£5/hr"},
               {lat: 53.4750, lng: -2.2550, name: "James P.'s Charger", type: "11kW", price: "£7/hr"},
               {lat: 53.4900, lng: -2.2300, name: "Emma T.'s Charger", type: "22kW", price: "£9/hr"},
               {lat: 53.4650, lng: -2.2200, name: "David's Charger", type: "7kW", price: "£5/hr"},
               {lat: 53.4920, lng: -2.2480, name: "Jennifer's Charger", type: "11kW", price: "£6/hr"}
          ];

          // Custom marker icon for chargers
          var chargerIcon = L.icon({
               iconUrl: 'images/marker-icon.png', 
               iconSize: [25, 41],
               iconAnchor: [12, 41],
               popupAnchor: [1, -34]
          });

          // Add charger markers to the map
          chargers.forEach(function(charger) {
               var marker = L.marker([charger.lat, charger.lng], {icon: chargerIcon}).addTo(map);
               marker.bindPopup("<b>" + charger.name + "</b><br>Type: " + charger.type + "<br>Price: " + charger.price + "<br><a href='charger-details.php?id=1'>View Details</a>");
          });

          // Try to get user's location
          if (navigator.geolocation) {
               navigator.geolocation.getCurrentPosition(
                    function(position) {
                         // Success: got user's location
                         var userLat = position.coords.latitude;
                         var userLng = position.coords.longitude;
                         
                         // Center map on user's location
                         map.setView([userLat, userLng], 13);
                         
                         // Add a marker at user's location
                         var userIcon = L.icon({
                              iconUrl: 'images/marker-icon.png', // Custom icon for user location
                              iconSize: [25, 41],
                              iconAnchor: [12, 41],
                              popupAnchor: [1, -34]
                         });
                         
                         var userMarker = L.marker([userLat, userLng], {icon: userIcon}).addTo(map);
                         userMarker.bindPopup("<b>Your Location</b>").openPopup();
                         
                         // Create a circle showing approximate location radius
                         var circle = L.circle([userLat, userLng], {
                              radius: 1000, // 1km radius
                              color: '#3388ff',
                              fillColor: '#3388ff',
                              fillOpacity: 0.1
                         }).addTo(map);
                    },
                    function(error) {
                         // Error or permission denied
                         console.log("Error getting location: " + error.message);
                         // Keep the default map center
                    },
                    {
                         enableHighAccuracy: true,
                         timeout: 5000,
                         maximumAge: 0
                    }
               );
          } else {
               console.log("Geolocation is not supported by this browser.");
               // Keep the default map center
          }
     });