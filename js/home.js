
// Carousel JavaScript
var links = document.querySelectorAll(".itemLinks");
var wrapper = document.querySelector("#wrapper");
var activeLink = 0;

// Add click event listeners to each navigation dot
for (var i = 0; i < links.length; i++) {
    links[i].addEventListener('click', setClickedItem, false);
    links[i].itemID = i;
}

// Set the first dot as active by default
links[activeLink].classList.add("active");

function setClickedItem(e) {
    removeActiveLinks();
    resetTimer();
    activeLink = e.target.itemID;
    changePosition(activeLink);
}

function removeActiveLinks() {
    for (var i = 0; i < links.length; i++) {
        links[i].classList.remove("active");
    }
}

function changePosition(position) {
    // Calculate the correct translation percentage
    var translateValue = -(position * 25) + "%";
    wrapper.style.transform = "translateX(" + translateValue + ")";
    links[position].classList.add("active");
}

var timeoutID;

function startTimer() {
    timeoutID = window.setInterval(goToNextItem, 4000);
}
startTimer();

function resetTimer() {
    window.clearInterval(timeoutID);
    startTimer();
}

function goToNextItem() {
    removeActiveLinks();
    activeLink = (activeLink < links.length - 1) ? activeLink + 1 : 0;
    changePosition(activeLink);
}

