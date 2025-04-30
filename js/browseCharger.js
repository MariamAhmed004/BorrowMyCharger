document.getElementById('resetBtn').onclick = function () {
  document.getElementById('locationSelect').selectedIndex = 0;
  document.getElementById('priceRangeSelect').selectedIndex = 0;
  document.getElementById('availabilitySelect').selectedIndex = 0;
  loadChargePoints(1);
};

document.getElementById('locationSelect').addEventListener('change', function() {
  loadChargePoints(1);
});

document.getElementById('priceRangeSelect').addEventListener('change', function() {
  loadChargePoints(1);
});

document.getElementById('availabilitySelect').addEventListener('change', function() {
  loadChargePoints(1);
});

// Function to load charge points with pagination
function loadChargePoints(page) {
  var location = document.getElementById('locationSelect').value;
  var priceRange = document.getElementById('priceRangeSelect').value;
  var availability = document.getElementById('availabilitySelect').value;
  
  var xhr = new XMLHttpRequest();
  xhr.open('GET', 'browse-charger.php?ajax=true' + 
    '&location=' + encodeURIComponent(location) +
    '&priceRange=' + encodeURIComponent(priceRange) +
    '&availability=' + encodeURIComponent(availability) +
    '&page=' + encodeURIComponent(page), true);
    
  xhr.onload = function () {
    if (xhr.status === 200) {
      document.getElementById('chargePointsContainer').innerHTML = xhr.responseText;
      
      // Add event listeners to pagination links
      const paginationLinks = document.querySelectorAll('.page-link');
      paginationLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const pageNum = this.getAttribute('data-page');
          loadChargePoints(pageNum);
          
          // Update URL without refreshing the page
          const url = new URL(window.location);
          url.searchParams.set('page', pageNum);
          window.history.pushState({}, '', url);
        });
      });
    } else {
      console.error('Error fetching charge points:', xhr.statusText);
    }
  };
  
  xhr.onerror = function () {
    console.error('Request error...');
  };
  
  xhr.send();
}

// Event delegation for book buttons
document.getElementById('chargePointsContainer').addEventListener('click', function (event) {
  var bookBtn = event.target.closest('.btn-success');
  if (bookBtn) {
    event.preventDefault();
    var parentElement = bookBtn.closest('.book-btn');
    if (parentElement) {
      var role = parentElement.dataset.role;
      var id = parentElement.dataset.id;
      if (role === 'Guest') {
        alert('Please log in to book a charge point.');
      } else {
        window.location.href = 'book-charger.php?id=' + id;
      }
    }
  }
});

// Load initial charge points
// Check if there's a page parameter in the URL
function getInitialPage() {
  const urlParams = new URLSearchParams(window.location.search);
  const page = urlParams.get('page');
  return page ? parseInt(page) : 1;
}

// Initialize the page with charge points
document.addEventListener('DOMContentLoaded', function() {
  // Check URL for any initial filters
  const urlParams = new URLSearchParams(window.location.search);
  
  // Set filter values from URL if present
  const location = urlParams.get('location');
  if (location) {
    document.getElementById('locationSelect').value = location;
  }
  
  const priceRange = urlParams.get('priceRange');
  if (priceRange) {
    document.getElementById('priceRangeSelect').value = priceRange;
  }
  
  const availability = urlParams.get('availability');
  if (availability) {
    document.getElementById('availabilitySelect').value = availability;
  }
  
  // Load charge points with the initial page
  loadChargePoints(getInitialPage());
});