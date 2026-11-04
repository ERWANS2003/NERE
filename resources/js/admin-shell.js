const sidebar = document.querySelector('#admin-sidebar');

if (sidebar) {
    const openButtons = document.querySelectorAll('[data-admin-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-admin-sidebar-close]');

    const setOpen = (open) => {
        document.body.classList.toggle('admin-sidebar-is-open', open);
        sidebar.setAttribute('data-open', open ? 'true' : 'false');
        openButtons.forEach((button) => button.setAttribute('aria-expanded', open ? 'true' : 'false'));

        if (open) {
            sidebar.querySelector('a, button')?.focus();
        }
    };

    openButtons.forEach((button) => button.addEventListener('click', () => setOpen(true)));
    closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('admin-sidebar-is-open')) {
            setOpen(false);
            openButtons[0]?.focus();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            setOpen(false);
        }
    });
}
