// Activate primary and secondary modal buttons to have them do interesting things.
document.addEventListener("DOMContentLoaded", function () {
    showModalOnClick( "whiteGloveModal", "whiteGloveBtn");
    showModalOnClick( "whiteGloveModal", "whiteGloveLink");
    showModalOnClick( "contactSupportModal", "contactSupportBtn");
    showModalOnClick( "contactSupportModal", "contactSupportForConnecting");

    // Close all modals when clicking outside
    window.addEventListener('click', function(event) {
        closeAllModals(event);
    });

    addModalButtonCallback('whiteGloveModal', 'primary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            buildMailtoLink(event)
        }, 0)
    });

    addModalButtonCallback('whiteGloveModal', 'secondary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            buildMailtoLink(event)
        }, 0)
    });


    addModalButtonCallback('contactSupportModal', 'primary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            showModal("standardSupportModal");
        }, 0)
    });

    addModalButtonCallback('standardSupportModal', 'primary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            buildMailtoLink(event)
        }, 0)
    });

    addModalButtonCallback('standardSupportModal', 'secondary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            buildMailtoLink(event)
        }, 0)
    });

    addModalButtonCallback('contactSupportModal', 'secondary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            showModal("premiumSupportModal");
        }, 0)
    });

    addModalButtonCallback('premiumSupportModal', 'primary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            buildMailtoLink(event)
        }, 0)
    });

    addModalButtonCallback('premiumSupportModal', 'secondary', function(event) {
        closeAllModals(event);
        setTimeout(function(){
            buildMailtoLink(event)
        }, 0)
    });

    // Analytics
    const trackableElements = document.querySelectorAll('.trackable');
    trackableElements.forEach(element => {
        element.addEventListener('click', e => {
            const action = e.currentTarget.dataset.action;
            const hrefValue = e.currentTarget.href;

            if (e.target.getAttribute('target') !== '_blank') {
                e.preventDefault();
                if (hrefValue) {
                    setTimeout(() => {
                        window.location.href = hrefValue; // Redirect to the href value after 100 milliseconds
                    }, 100);
                }
            }

            window.sendTOTAnalytics(action)
        })
    });
});


function buildMailtoLink(event) {
    // Get the closest modal content div to the clicked button
    const modalContent = event.target.closest('.tot-modalcontent');

    // Extract relevant info from modal
    const modalHeader = modalContent.querySelector('.tot-modalheader h2').textContent;
    const modalBody = modalContent.querySelector('.tot-modalbody p').textContent;
    const clickedButton = event.target.textContent;
    const priceInfo = event.target.closest('.tot-btn-price-wrapper').querySelector('.tot-price-under-button').textContent;

    // Get optional replacement body from data attribute if present
    const dataBody = event.target.getAttribute('data-email-body') || '';
    const supportRequest = dataBody || '[[PLEASE TELL US WHAT YOU NEED HELP WITH.]]';

    // Extract URL details, assuming this information is available in a variable called urlDetails
    const urlDetails = window.location.href;

    // Build email subject and body
    const emailSubject = `Support Request: ${modalHeader}`;
    const emailBody = `Hello Support Team,\n\n${supportRequest}\n\nBest regards,\n[Your Name]\n\n###############################\nMetadata for Support:\n- Button clicked: ${clickedButton}\n- Price info: ${priceInfo}\n- URL Details: ${urlDetails}\n###############################`;

    // Encode subject and body
    const encodedSubject = encodeURIComponent(emailSubject);
    const encodedBody = encodeURIComponent(emailBody);

    // Create mailto link
    const mailtoLink = `mailto:onboarding@tokenoftrust.com?subject=${encodedSubject}&body=${encodedBody}`;

    // Open mail client
    window.location.href = mailtoLink;
}

