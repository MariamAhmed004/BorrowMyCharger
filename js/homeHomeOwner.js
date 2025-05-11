// Calendar functionality
document.addEventListener('DOMContentLoaded', function() {
    const monthYear = document.getElementById('monthYear');
    const calendar = document.getElementById('calendar');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');
    let currentDate = new Date();
    
    function renderCalendar(bookings) {
        calendar.innerHTML = ''; // Clear the calendar
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        
        // Set month and year title
        monthYear.textContent = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
        
        // Get the first day of the month
        const firstDay = new Date(year, month, 1);
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Get the index of the first day of the month (0 = Sunday, 1 = Monday, etc.)
        const firstDayIndex = firstDay.getDay();
        
        // Fill the calendar with empty cells before the first day
        for (let i = 0; i < firstDayIndex; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.classList.add('day', 'empty');
            calendar.appendChild(emptyCell);
        }
        
        // Fill the calendar with the days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.classList.add('day');
            dayCell.textContent = day;
            
            // Format the date string for comparison (YYYY-MM-DD)
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            // Find all bookings for this day
            const dayBookings = bookings.filter(b => {
                // Extract the date portion from the booking_date
                const bookingDate = b.booking_date.split(' ')[0];
                return bookingDate === dateStr;
            });
            
            // Check for status and add appropriate styling
            if (dayBookings.length > 0) {
                // Count bookings by status
                const pendingCount = dayBookings.filter(b => b.booking_status_id == 1).length;
                const approvedCount = dayBookings.filter(b => b.booking_status_id == 2).length;
                const rejectedCount = dayBookings.filter(b => b.booking_status_id == 3).length;
                
                // Prioritize pending, then approved, then rejected for styling
                if (pendingCount > 0) {
                    dayCell.classList.add('has-pending');
                } else if (approvedCount > 0) {
                    dayCell.classList.add('has-approved');
                } else if (rejectedCount > 0) {
                    dayCell.classList.add('has-rejected');
                }
                
                // Add tooltip showing booking counts
                let tooltipText = [];
                if (pendingCount > 0) tooltipText.push(`${pendingCount} pending`);
                if (approvedCount > 0) tooltipText.push(`${approvedCount} approved`);
                if (rejectedCount > 0) tooltipText.push(`${rejectedCount} rejected`);
                
                dayCell.title = tooltipText.join(', ');
                
                // Add click event to navigate to the bookings for this date
                dayCell.onclick = () => {
                    window.location.href = `borrowrequest.php?date=${dateStr}`;
                };
            }
            
            calendar.appendChild(dayCell);
        }
    }
    
    prevMonth.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar(window.bookings);
    });
    
    nextMonth.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar(window.bookings);
    });
    
    // Initial render
    renderCalendar(window.bookings);
});