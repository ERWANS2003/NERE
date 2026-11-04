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

const bulkUsers = document.querySelector('[data-bulk-users]');

if (bulkUsers) {
    const action = bulkUsers.querySelector('[data-bulk-action]');
    const checkboxes = [...bulkUsers.querySelectorAll('[data-user-checkbox]')];
    const selectAll = bulkUsers.querySelector('[data-select-all]');
    const count = bulkUsers.querySelector('[data-bulk-count]');
    const submit = bulkUsers.querySelector('[data-bulk-submit]');
    const department = bulkUsers.querySelector('[data-bulk-department]');
    const role = bulkUsers.querySelector('[data-bulk-role]');

    const refresh = () => {
        const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
        count.textContent = `${selected} sélectionné${selected > 1 ? 's' : ''}`;
        submit.disabled = selected === 0 || !action.value;
        selectAll.checked = selected > 0 && selected === checkboxes.length;
        selectAll.indeterminate = selected > 0 && selected < checkboxes.length;
        department.hidden = action.value !== 'assign_department';
        role.hidden = !['add_role', 'remove_role'].includes(action.value);
        department.querySelector('select').required = action.value === 'assign_department';
        role.querySelector('select').required = ['add_role', 'remove_role'].includes(action.value);
    };

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
        refresh();
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', refresh));
    action?.addEventListener('change', refresh);
    bulkUsers.addEventListener('submit', (event) => {
        if (['deactivate', 'remove_role'].includes(action.value)
            && !window.confirm('Confirmer cette action sur tous les comptes sélectionnés ?')) {
            event.preventDefault();
        }
    });
    refresh();
}
