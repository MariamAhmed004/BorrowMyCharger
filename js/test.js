document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.testimonial-carousel');
    const items = document.querySelectorAll('.testimonial-carousel .item');
    const prevButton = document.querySelector('.prev');
    const nextButton = document.querySelector('.next');
    let currentIndex = 0;

    function updateCarousel() {
        const itemWidth = items[0].offsetWidth + 20; // Including margin
        carousel.scrollTo({
            left: currentIndex * itemWidth,
            behavior: 'smooth'
        });
        updateButtons();
    }

    function updateButtons() {
        prevButton.style.display = currentIndex > 0 ? 'block' : 'none';
        nextButton.style.display = currentIndex < items.length - 3 ? 'block' : 'none';
    }

    prevButton.addEventListener('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            updateCarousel();
        }
    });

    nextButton.addEventListener('click', function() {
        if (currentIndex < items.length - 3) {
            currentIndex++;
            updateCarousel();
        }
    });

    updateButtons(); // Initial check
});
