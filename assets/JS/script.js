function togglePassword(fieldId, button) {

    const password = document.getElementById(fieldId);

    if (password.type === "password") {
        password.type = "text";
        button.innerHTML = "🙈";
        button.setAttribute("aria-label", "Hide password");
    } else {
        password.type = "password";
        button.innerHTML = "👁";
        button.setAttribute("aria-label", "Show password");
    }
}