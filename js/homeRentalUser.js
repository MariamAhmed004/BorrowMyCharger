document.addEventListener('DOMContentLoaded', function() {
    const monthYear = document.getElementById('monthYear');
    const calendar = document.getElementById('calendar');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');
    let currentDate = new Date();

    function renderCalendar(bookings) {
        calendar.innerHTML = '';
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        monthYear.textContent = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
        const firstDay = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayIndex = firstDay.getDay();
        
        // Add empty cells for days before the 1st of the month
        for (let i = 0; i < firstDayIndex; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.classList.add('day', 'empty');
            calendar.appendChild(emptyCell);
        }
        
        // Create cells for each day of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.classList.add('day');
            dayCell.textContent = day;
            
            // Format date as YYYY-MM-DD for comparison
            const currentYearMonth = `${year}-${String(month + 1).padStart(2, '0')}`;
            const formattedDay = String(day).padStart(2, '0');
            const dateToCheck = `${currentYearMonth}-${formattedDay}`;
            
            // Find bookings for this day
            const dayBookings = bookings.filter(b => {
                try {
                    const bookingDate = b.booking_date.split(' ')[0]; // Extract date part
                    return bookingDate === dateToCheck;
                } catch (error) {
                    console.error(`Error comparing booking_date: ${b.booking_date}`, error);
                    return false;
                }
            });
            
            // Apply appropriate styling for bookings
            if (dayBookings.length > 0) {
                // Check for different status types
                const hasApproved = dayBookings.some(b => b.status === 'Approved');
                const hasPending = dayBookings.some(b => b.status === 'Pending Approval');
                const hasRejected = dayBookings.some(b => b.status === 'Rejected');
                
                // Create status indicators container
                const statusIndicators = document.createElement('div');
                statusIndicators.className = 'status-indicators';
                
                // Add multi-status visualization
                if (hasApproved) {
                    const indicator = document.createElement('div');
                    indicator.className = 'status-indicator approved';
                    statusIndicators.appendChild(indicator);
                }
                
                if (hasPending) {
                    const indicator = document.createElement('div');
                    indicator.className = 'status-indicator pending';
                    statusIndicators.appendChild(indicator);
                }
                
                if (hasRejected) {
                    const indicator = document.createElement('div');
                    indicator.className = 'status-indicator rejected';
                    statusIndicators.appendChild(indicator);
                }
                
                dayCell.appendChild(statusIndicators);
                
                // Add booking count badge if multiple bookings exist
                if (dayBookings.length > 0) {
                    const countBadge = document.createElement('span');
                    countBadge.classList.add('booking-count');
                    countBadge.textContent = dayBookings.length;
                    dayCell.appendChild(countBadge);
                }
                
                // Add click event for showing booking details
                dayCell.addEventListener('click', function() {
                    showBookingDetails(dayBookings, dateToCheck);
                });
                
                // Make clickable cells look interactive
                dayCell.style.cursor = 'pointer';
            }
            
            // Mark today's date
            const today = new Date();
            if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
                dayCell.classList.add('today');
            }
            
            calendar.appendChild(dayCell);
        }
    }

    // Function to show booking details using tooltip or redirection
    function showBookingDetails(bookings, date) {
        // If only one booking, go directly to that booking's details
        if (bookings.length === 1) {
            window.location.href = `reservation-details.php?id=${bookings[0].booking_id}`;
            return;
        }
        
        
        // Format date for display
        const displayDate = new Date(date).toLocaleDateString();
        
        // Create an alert message with booking info
        let alertMessage = `Bookings for ${displayDate}:\n\n`;
        
        bookings.forEach((booking, index) => {
            alertMessage += `${index + 1}. Time: ${booking.booking_time} - Status: ${booking.status}\n`;
        });
        
        alertMessage += `\nClick OK to view all your bookings.`;
        
        alert(alertMessage);
        
        // Redirect to booking history page
        window.location.href = 'booking-history.php';
    }

    prevMonth.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(window.bookings);
    });

    nextMonth.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(window.bookings);
    });

    // Initialize tooltips if using Bootstrap
    function initTooltips() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl, {
                    html: true
                });
            });
        }
    }

    // Initial render
    renderCalendar(window.bookings);
    
    // Initialize tooltips after calendar is rendered
    initTooltips();
});