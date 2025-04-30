
    const monthYear = document.getElementById('monthYear');
    const calendar = document.getElementById('calendar');
    const prevMonth = document.getElementById('prevMonth');
    const nextMonth = document.getElementById('nextMonth');

    let currentDate = new Date();

    function renderCalendar() {
        calendar.innerHTML = '';
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
            emptyCell.classList.add('day');
            calendar.appendChild(emptyCell);
        }

        // Fill the calendar with the days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.classList.add('day');
            dayCell.textContent = day;
            dayCell.onclick = () => alert(`Selected date: ${day} ${currentDate.toLocaleString('default', { month: 'long' })} ${year}`);
            calendar.appendChild(dayCell);
        }
    }

    prevMonth.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonth.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // Initial render
    renderCalendar();
