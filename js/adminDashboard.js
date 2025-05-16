// Utility function to generate random but consistent colors
function generateColors(count) {
    const colors = [
        '#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6',
        '#1abc9c', '#34495e', '#e67e22', '#c0392b', '#16a085'
    ];

    let result = [];
    for (let i = 0; i < count; i++) {
        result.push(colors[i % colors.length]);
    }
    return result;
}

// Helper to safely parse JSON data from PHP
function parseData(dataString, defaultValue = {}) {
    try {
        return JSON.parse(dataString);
    } catch (e) {
        console.error('Error parsing data:', e);
        return defaultValue;
    }
}

// Chart for User Roles
function createUserRolesChart(data) {
    const labels = Object.keys(data);
    const values = Object.values(data).map(Number);
    
    const ctx = document.getElementById('userRolesChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: generateColors(labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'User Distribution'
                }
            }
        }
    });
}

// Chart for Charge Point Status
function createChargePointStatusChart(data) {
    const labels = Object.keys(data);
    const values = Object.values(data).map(Number);
    
    const ctx = document.getElementById('chargePointStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: ['#2ecc71', '#e74c3c', '#f39c12'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Chart for User Account Status
function createUserStatusChart(data) {
    const labels = Object.keys(data);
    const values = Object.values(data).map(Number);
    
    const ctx = document.getElementById('userStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: ['#2ecc71', '#e74c3c', '#f39c12'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Chart for Booking Status
function createBookingStatusChart(data) {
    const labels = Object.keys(data);
    const values = Object.values(data).map(Number);
    
    const ctx = document.getElementById('bookingStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: ['#f39c12', '#2ecc71', '#e74c3c'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
}

// Chart for Booking Trends
function createBookingTrendsChart(data) {
    const labels = Object.keys(data);
    const values = Object.values(data).map(Number);
    
    const formattedLabels = labels.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const ctx = document.getElementById('bookingTrendsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: formattedLabels,
            datasets: [{
                label: 'Bookings',
                data: values,
                fill: false,
                borderColor: '#3498db',
                tension: 0.1,
                pointBackgroundColor: '#3498db'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
}

// Chart for Popular Days
function createPopularDaysChart(data) {
    const labels = Object.keys(data);
    const values = Object.values(data).map(Number);
    
    const ctx = document.getElementById('popularDaysChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Bookings',
                data: values,
                backgroundColor: generateColors(labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Chart for Popular Hours
function createPopularHoursChart(data) {
    const labels = Object.keys(data).map(hour => {
        const h = parseInt(hour);
        if (h === 0) return '12 AM';
        if (h === 12) return '12 PM';
        return h < 12 ? `${h} AM` : `${h - 12} PM`;
    });
    const values = Object.values(data).map(Number);
    
    const ctx = document.getElementById('popularHoursChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Bookings',
                data: values,
                backgroundColor: generateColors(labels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Initialize all charts when the page is loaded
document.addEventListener('DOMContentLoaded', function() {
    createUserRolesChart(window.userRoleCounts);
    createChargePointStatusChart(window.chargePointStatusCounts);
    createUserStatusChart(window.userStatusCounts);
    createBookingStatusChart(window.bookingStatusCounts);
    createBookingTrendsChart(window.bookingsLastSevenDays);
    createPopularDaysChart(window.popularBookingDays);
    createPopularHoursChart(window.popularBookingTimes);
});