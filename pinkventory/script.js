// script.js

function showSignIn() {
    document.querySelector(".page-container").classList.remove("active");
}

function showSignUp() {
    document.querySelector(".page-container").classList.add("active");
}

// Attach event listeners for buttons
document.querySelector(".toggle-btn").addEventListener("click", showSignUp);

function toggleLoginForm(type) {
    const userForm = document.getElementById('user-login-form');
    const adminForm = document.getElementById('admin-login-form');
    
    if (type === 'user') {
        userForm.style.display = 'block';
        adminForm.style.display = 'none';
    } else {
        userForm.style.display = 'none';
        adminForm.style.display = 'block';
    }
}

function showLoginForm(type) {
    // Your logic to show the appropriate login form based on the selected type
    console.log(`Showing login form for: ${type}`);
}