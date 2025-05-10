

    
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

    // Get the index of the first day of the month
    const firstDayIndex = firstDay.getDay();

    // Fill the calendar with empty cells before the first day
    for (let i = 0; i < firstDayIndex; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.classList.add('day', 'empty'); // Add a class for styling empty cells
        calendar.appendChild(emptyCell);
    }

    // Fill the calendar with the days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.classList.add('day');
        dayCell.textContent = day;

        // Check if the day matches any booking date
        const booking = bookings.find(b => {
            try {
                // Extract the date portion (YYYY-MM-DD) from the booking_date
                const [yearPart, monthPart, dayPart] = b.booking_date.split(' ')[0].split('-');
                const bookingDate = new Date(Date.UTC(yearPart, monthPart - 1, dayPart)); // Normalize to UTC

                console.log('Booking Date:', b.booking_date, 'Parsed Date:', bookingDate, 'Calendar Date:', `${year}-${month + 1}-${day}`);

                // Compare the year, month, and day
                return (
                    bookingDate.getUTCFullYear() === year &&
                    bookingDate.getUTCMonth() === month &&
                    bookingDate.getUTCDate() === day
                );
            } catch (error) {
                console.error(`Error parsing booking_date: ${b.booking_date}`, error);
                return false; // Skip this booking if the date is invalid
            }
        });

        // If a booking exists for this day, add a special class
        if (booking) {
            console.log(`Booking found for ${day}:`, booking); // Debugging
            dayCell.style.color = '#ffffff';
            dayCell.style.backgroundColor = '#299c6e'; 

            //add an event listener to the booking cell to navigate to the booking details page
            dayCell.onclick = () => {
                window.location.href = `./booking-details.php?booking_id=${booking.booking_id}`;
            };

        }

        // Add click event to display the selected date
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
