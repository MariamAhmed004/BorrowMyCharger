// calendar.js
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

    for (let i = 0; i < firstDayIndex; i++) {
        const emptyCell = document.createElement('div');
        emptyCell.classList.add('day', 'empty');
        calendar.appendChild(emptyCell);
    }

    for (let day = 1; day <= daysInMonth; day++) {
        const dayCell = document.createElement('div');
        dayCell.classList.add('day');
        dayCell.textContent = day;

        const booking = bookings.find(b => {
            try {
                const [yearPart, monthPart, dayPart] = b.booking_date.split(' ')[0].split('-');
                const bookingDate = new Date(Date.UTC(yearPart, monthPart - 1, dayPart));
                return (
                    bookingDate.getUTCFullYear() === year &&
                    bookingDate.getUTCMonth() === month &&
                    bookingDate.getUTCDate() === day
                );
            } catch (error) {
                console.error(`Error parsing booking_date: ${b.booking_date}`, error);
                return false;
            }
        });

        if (booking) {
            if (booking.status === 'approved') {
                dayCell.classList.add('has-confirmed');
            } else if (booking.status === 'rejected') {
                dayCell.classList.add('has-rejected');
            } else {
                dayCell.classList.add('has-reservation');
            }

            // Disable click functionality
            dayCell.onclick = null;
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