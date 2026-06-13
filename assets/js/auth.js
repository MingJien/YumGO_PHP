document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-password-toggle]');
    if (!toggle) {
        return;
    }

    const inputId = toggle.getAttribute('data-password-toggle');
    const input = document.getElementById(inputId);
    const icon = toggle.querySelector('i');

    if (!input) {
        return;
    }

    const shouldShow = input.type === 'password';
    input.type = shouldShow ? 'text' : 'password';
    toggle.setAttribute('aria-label', shouldShow ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');

    if (icon) {
        icon.classList.toggle('bi-eye', !shouldShow);
        icon.classList.toggle('bi-eye-slash', shouldShow);
    }
});
