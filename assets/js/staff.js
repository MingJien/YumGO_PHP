document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        if (!input) {
            return;
        }

        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        const icon = button.querySelector('i');
        if (icon) {
            icon.className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
        }
    });
});
