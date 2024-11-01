const debugMode = true;
const noop = function(){}
const debug = {
    log : debugMode ? console.log : noop,
    warn: debugMode ? console.warn: noop,
}

// For the display of testimonials
let activeTestimonial = 0;
function showTestimonial(index) {
    const testimonials = document.querySelectorAll('.tot-testimonial');
    testimonials[activeTestimonial].classList.remove('active'); // remove active class
    testimonials[index].classList.add('active'); // add active class
    activeTestimonial = index;
}
document.addEventListener("DOMContentLoaded", function () {
    let testimonials = document.querySelectorAll(".tot-testimonial");
    if (testimonials[0]) {
        let currentIndex = 0;

        testimonials.forEach((testimonial, index) => {
            if (index === 0) {
                testimonial.style.opacity = "1";
            } else {
                testimonial.style.opacity = "0";
            }
        });

        setInterval(() => {
            testimonials[currentIndex].style.opacity = "0";
            currentIndex++;
            if (currentIndex >= testimonials.length) {
                currentIndex = 0;
            }
            testimonials[currentIndex].style.opacity = "1";
        }, 7000);
    }
});


// Function to perform form submission
function submitForm() {
    // Uncomment this function when ready to submit the form
    // document.querySelector('form').submit();
    debug.log('Implement form submission!');
}

function getModalButton(modalId, buttonType) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        debug.warn(`No modal found with ID: ${modalId} `);
        return;
    }

    const button = modal.querySelector(`.tot-cta-button.${buttonType}`);
    if (!button) {
        debug.warn(`No ${buttonType} button found in modal: ${modalId} `);
        return;
    }
    return button;
}
// To add actions to primary and secondary modals
function addModalButtonCallback(modalId, buttonType, callback) {
    const button = getModalButton(modalId, buttonType);
    if (button) {
        button.addEventListener('click', function(event){
            debug.log(`Clicked ${buttonType} button in modal: ${modalId} `);
            callback(event);
        });
    }
}


// Event listener for closing all modals when Esc is pressed
document.addEventListener("DOMContentLoaded", function () {
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });
    closeAllModals(event);
});

// To show a modal.
function showModalOnClick(modalId, buttonClicked) {
    var modal = document.getElementById(modalId);
    var btn = document.getElementById(buttonClicked);
    var span = modal.getElementsByClassName("tot-close-btn")[0];

    btn && btn.addEventListener('click', function (event) {
        event.preventDefault(); // Prevents any default action
        event.stopPropagation();
        showModal(modal);
    });

    span && span.addEventListener('click', function (event = null) {
        event.preventDefault();
        closeAllModals(event);
    });
}

// Closes all modals
function closeAllModals(event = null) {
    var allModals = document.querySelectorAll('.tot-modaloverlay');
    allModals.forEach(function(modal) {
        closeModal(modal);
    });
}

// Shows a specific modal
function showModal(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }

    if (modal && modal.style.display !== "flex") {
        console.log("Showing modal: ", modal.id);  // Changed debug.log to console.log
        modal.style.display = "flex";
    }
}

// Closes a specific modal
function closeModal(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }

    if (modal && modal.style.display !== "none") {
        console.log("Closing open modal: ", modal.id);  // Changed debug.log to console.log
        modal.style.display = "none";
    }
}
