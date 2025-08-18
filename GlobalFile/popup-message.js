//Popup message box that appears on page load and disappears after 5 seconds
window.addEventListener("load", function () {
    const popupMessage = document.getElementById("popup-message");

    setTimeout(() => {
        popupMessage.classList.add("hide");

        popupMessage.addEventListener("transitionend", () => {
            popupMessage.style.display = "none";
        }, { once: true });

    }, 1500);
});