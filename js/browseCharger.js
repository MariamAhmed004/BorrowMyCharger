document.addEventListener('DOMContentLoaded', function() {
  // Input elements
  const locationSearch = document.getElementById('locationSearch');
  const availabilitySearch = document.getElementById('availabilitySearch');
  const locationSelect = document.getElementById('locationSelect');
  const priceRangeSelect = document.getElementById('priceRangeSelect');
  const availabilitySelect = document.getElementById('availabilitySelect');
  const resetBtn = document.getElementById('resetBtn');
  
  // Add event listeners for search inputs with debounce
  let searchTimeout;
  
  locationSearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
      loadChargePoints(1);
    }, 300); // Reduced debounce time for better responsiveness
  });
  
  availabilitySearch.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
      loadChargePoints(1);
    }, 300); // Reduced debounce time for better responsiveness
  });
  
  // Event listeners for dropdown filters
  locationSelect.addEventListener('change', function() {
    loadChargePoints(1);
  });
  
  priceRangeSelect.addEventListener('change', function() {
    loadChargePoints(1);
  });
  
  availabilitySelect.addEventListener('change', function() {
    loadChargePoints(1);
  });
  
  // Reset button
  resetBtn.addEventListener('click', function() {
    // Reset dropdowns
    locationSelect.selectedIndex = 0;
    priceRangeSelect.selectedIndex = 0;
    availabilitySelect.selectedIndex = 0;
    
    // Reset search boxes
    locationSearch.value = '';
    availabilitySearch.value = '';
    
    loadChargePoints(1);
  });
  
  // Function to load charge points with pagination
  function loadChargePoints(page) {
    // Get filter values
    const location = locationSelect.value;
    const priceRange = priceRangeSelect.value;
    const availability = availabilitySelect.value;
    const locationQuery = locationSearch.value.trim();
    const availabilityQuery = availabilitySearch.value.trim();
    
    // Show loading state
    const chargePointsContainer = document.getElementById('chargePointsContainer');
    chargePointsContainer.innerHTML = '<div class="loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p>Loading charge points...</p></div>';
    
    // Build the URL with all parameters
    const url = new URL('browse-charger.php', window.location.origin);
    url.searchParams.append('ajax', 'true');
    url.searchParams.append('page', page);
    
    if (location) url.searchParams.append('location', location);
    if (priceRange) url.searchParams.append('priceRange', priceRange);
    if (availability) url.searchParams.append('availability', availability);
    if (locationQuery) url.searchParams.append('locationQuery', locationQuery);
    if (availabilityQuery) url.searchParams.append('availabilityQuery', availabilityQuery);
    
    // Make the AJAX request
    fetch(url)
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(html => {
        // Update the container with the fetched HTML
        chargePointsContainer.innerHTML = html;
        
        // Update results count
        updateResultsCount();
        
        // Add event listeners to pagination links
        setupPaginationListeners();
        
        // Update URL without refreshing the page
        const pageUrl = new URL(window.location);
        pageUrl.searchParams.delete('ajax');
        
        // Only add parameters that have values
        if (page > 1) pageUrl.searchParams.set('page', page);
        if (location) pageUrl.searchParams.set('location', location);
        if (priceRange) pageUrl.searchParams.set('priceRange', priceRange);
        if (availability) pageUrl.searchParams.set('availability', availability);
        if (locationQuery) pageUrl.searchParams.set('locationQuery', locationQuery);
        if (availabilityQuery) pageUrl.searchParams.set('availabilityQuery', availabilityQuery);
        
        window.history.pushState({}, '', pageUrl);
      })
      .catch(error => {
        console.error('Error fetching charge points:', error);
        chargePointsContainer.innerHTML = '<div class="error"><i class="bi bi-exclamation-triangle"></i><p>Error loading charge points. Please try again.</p></div>';
      });
  }
  
  // Function to update the results count text
  function updateResultsCount() {
    const chargePoints = document.querySelectorAll('.charge-point');
    const resultsCount = document.getElementById('resultsCount');
    
    if (chargePoints.length === 0) {
      resultsCount.textContent = 'No charging stations found';
    } else if (chargePoints.length === 1) {
      resultsCount.textContent = 'Showing 1 charging station';
    } else {
      resultsCount.textContent = `Showing ${chargePoints.length} charging stations`;
    }
  }
  
  // Function to set up pagination listeners
  function setupPaginationListeners() {
    const paginationLinks = document.querySelectorAll('.page-link');
    paginationLinks.forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        const pageNum = this.getAttribute('data-page');
        loadChargePoints(pageNum);
        
        // Scroll to top of results after pagination click
        const resultsSection = document.querySelector('.section-title');
        if (resultsSection) {
          resultsSection.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });
  }
  
  // Event delegation for book buttons
  document.getElementById('chargePointsContainer').addEventListener('click', function(event) {
    const bookBtn = event.target.closest('.btn-primary');
    if (bookBtn) {
      event.preventDefault();
      const parentElement = bookBtn.closest('.book-btn');
      if (parentElement) {
        const role = parentElement.dataset.role;
        const id = parentElement.dataset.id;
        
        if (role === 'Guest') {
          // Create a nicer alert using Bootstrap
          const alertDiv = document.createElement('div');
          alertDiv.className = 'alert alert-warning alert-dismissible fade show';
          alertDiv.setAttribute('role', 'alert');
          alertDiv.innerHTML = `
            <strong>Login Required!</strong> Please log in to book a charge point.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          `;
          
          const container = document.querySelector('.container-custom');
          container.insertBefore(alertDiv, container.firstChild);
          
          // Auto dismiss after 5 seconds
          setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 500);
          }, 5000);
        } else {
          window.location.href = 'book-charger.php?id=' + id;
        }
      }
    }
  });
  
  // Function to get initial page from URL
  function getInitialPage() {
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    return page ? parseInt(page) : 1;
  }
  
  // Set up initial filters from URL parameters
  function setInitialFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set dropdown filters from URL if present
    const location = urlParams.get('location');
    if (location) {
      locationSelect.value = location;
    }
    
    const priceRange = urlParams.get('priceRange');
    if (priceRange) {
      priceRangeSelect.value = priceRange;
    }
    
    const availability = urlParams.get('availability');
    if (availability) {
      availabilitySelect.value = availability;
    }
    
    // Set search inputs from URL if present
    const locationQuery = urlParams.get('locationQuery');
    if (locationQuery) {
      locationSearch.value = locationQuery;
    }
    
    const availabilityQuery = urlParams.get('availabilityQuery');
    if (availabilityQuery) {
      availabilitySearch.value = availabilityQuery;
    }
  }
  
  // Initialize
  setInitialFilters();
  loadChargePoints(getInitialPage());
  
  // Update results count on initial load
  setTimeout(() => {
    updateResultsCount();
  }, 500);
});