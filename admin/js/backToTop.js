// Back to top functionality
window.onscroll = function () {
  scrollFunction();
};

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    document.getElementById("backToTop").style.display = "block";
  } else {
    document.getElementById("backToTop").style.display = "none";
  }
}

function topFunction() {
  document.body.scrollTop = 0;
  document.documentElement.scrollTop = 0;
}

// Create back to top button if it doesn't exist
if (!document.getElementById("backToTop")) {
  const backToTopButton = document.createElement("button");
  backToTopButton.id = "backToTop";
  backToTopButton.innerHTML = "↑";
  backToTopButton.title = "Back to top";
  backToTopButton.style.cssText = `
        display: none;
        position: fixed;
        bottom: 20px;
        right: 30px;
        z-index: 99;
        border: none;
        outline: none;
        background-color: #555;
        color: white;
        cursor: pointer;
        padding: 15px;
        border-radius: 10px;
        font-size: 18px;
    `;
  backToTopButton.onclick = topFunction;
  document.body.appendChild(backToTopButton);
}
