

var totIsAgeGateConfirmed = localStorage.getItem("totIsAgeGateConfirmed");
var totAgeGateConfirmationExpires = localStorage.getItem("totAgeGateConfirmationExpires");
var totAgeGateInterval;
var currentTime = Date.now();
var expiration = 8 * 60 * 60 * 1000; // 8 hours.
var totIsAgeGateModalOpen = false;

if (!totIsAgeGateConfirmed) {
    // You have not confirmed the age gate yet.
    totAgeGateInterval = setInterval(totOpenAgeGateWhenYouCan, 20);
} else if (totAgeGateConfirmationExpires && currentTime > totAgeGateConfirmationExpires) {
    // You HAVE confirmed the age gate, but that has expired.
    localStorage.removeItem("totIsAgeGateConfirmed");
    localStorage.removeItem("totAgeGateConfirmationExpires");
} else {
    // Age gate is confirmed! Do not show this person this modal.
}

function totOpenAgeGateWhenYouCan() {
    if (typeof tot !== "undefined" && tot.openGate) {
        totOpenAgeGateNow();
    } else {
        // tot is undefined. Try again in 20 milliseconds.
    }
}

function totOpenAgeGateNow() {
    clearInterval(totAgeGateInterval);
    tot.openGate();
    totIsAgeGateModalOpen = true;

    tot("bind", "modalClose", function (event) {
        // The modal has been closed. Don't show it again for 8 hours.
        totIsAgeGateConfirmed = true;
        localStorage.setItem("totIsAgeGateConfirmed", true);
        totAgeGateConfirmationExpires = currentTime + expiration;
        localStorage.setItem("totAgeGateConfirmationExpires", currentTime + expiration);
    });
}