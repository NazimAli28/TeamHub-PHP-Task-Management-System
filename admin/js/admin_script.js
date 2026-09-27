// Toast
function showToast(message) {
    const toast = document.getElementById("toast");
    toast.textContent = message;
    toast.classList.add("show");
    setTimeout(() => {
        toast.classList.remove("show");
    }, 3000);
}
const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get("status");
if (status === "added") {
    showToast("✅ Record added successfully!");
} else if (status === "updated") {
    showToast("📝 Record updated successfully!");
} else if (status === "deleted") {
    showToast("❌ Record deleted successfully!");
}
// Dark Mode
function toggleDarkMode() {
    document.body.classList.toggle("dark");
    localStorage.setItem("darkMode", document.body.classList.contains("dark"));
}
if (localStorage.getItem("darkMode") === "true") {
    document.body.classList.add("dark");
}
// Time Input
const timeInput = document.getElementById("timeInput");
const timeHint = document.getElementById("timeHint");
timeInput.addEventListener("input", () => {
    const mins = parseInt(timeInput.value) || 0;
    const hrs = Math.floor(mins / 60);
    const rem = mins % 60;
    timeHint.textContent = `= ${hrs} Hour${hrs !== 1 ? "s" : ""} ${rem} Minute${rem !== 1 ? "s" : ""
        }`;
});
// Work Selection
const workSelect = document.getElementById("workSelect");
const workInput = document.getElementById("workInput");
const selectedWorks = document.getElementById("selectedWorks");
let selected = [];
workSelect.addEventListener("change", () => {
    const value = workSelect.value.trim();
    if (value && !selected.includes(value)) {
        selected.push(value);
        updateWorkList();
    }
    workSelect.value = ""; // reset dropdown
});
function updateWorkList() {
    // update UI
    selectedWorks.innerHTML = "";
    selected.forEach((item, index) => {
        const tag = document.createElement("span");
        tag.classList.add("tag");
        tag.innerHTML = `${item} <span onclick="removeWork(${index})" style="cursor:pointer;color:red;">&times;</span>`;
        selectedWorks.appendChild(tag);
    });
    // update hidden field
    workInput.value = selected.join(", ");
}
function removeWork(index) {
    selected.splice(index, 1);
    updateWorkList();
}
