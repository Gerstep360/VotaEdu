document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-login-ci]').forEach(input => {
        input.addEventListener('input', () => {
            document.getElementById(input.dataset.loginCi).value = input.value.trim();
        });
    });
    document.querySelector('[data-fill-course]')?.addEventListener('change', event => {
        const selected = event.target.selectedOptions[0];
        if (selected.dataset.grade) {
            document.querySelector('#course').value = selected.dataset.grade;
            document.querySelector('#parallel').value = selected.dataset.parallel;
        }
    });
    const menu = document.querySelector('.menu-button');
    const navigation = document.querySelector('#navigation');
    menu?.addEventListener('click', () => {
        const open = navigation.classList.toggle('is-open');
        menu.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('click', event => {
        if (menu && !navigation.contains(event.target) && !menu.contains(event.target)) {
            navigation.classList.remove('is-open');
            menu.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && navigation?.classList.contains('is-open')) {
            navigation.classList.remove('is-open');
            menu.setAttribute('aria-expanded', 'false');
            menu.focus();
        }
    });
    document.querySelectorAll('[data-password]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.password);
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Ocultar' : 'Mostrar';
            button.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
    const voteForm = document.querySelector('[data-vote]');
    if (voteForm) {
        const updateSelection = () => {
            const selected = voteForm.querySelector('input[name="option_id"]:checked');
            document.querySelector('#vote-submit').disabled = !selected;
            document.querySelector('#vote-feedback').textContent = selected
                ? `Tu elección: ${selected.dataset.optionName}`
                : 'Selecciona una opción para continuar.';
        };
        voteForm.addEventListener('change', updateSelection);
        updateSelection();
    }
    const dialog = document.querySelector('#confirmation');
    let pendingForm = null;
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', event => {
            if (form.dataset.confirmed === 'true') return;
            event.preventDefault();
            pendingForm = form;
            document.querySelector('#confirm-message').textContent = form.dataset.confirm;
            document.querySelector('#confirm-accept').textContent = form.dataset.confirmLabel || 'Confirmar';
            const selected = form.querySelector('input[name="option_id"]:checked');
            const preview = document.querySelector('#confirm-selection');
            preview.hidden = !selected;
            preview.textContent = selected?.dataset.optionName || '';
            dialog.showModal();
            document.querySelector('#confirm-cancel').focus();
        });
    });
    document.querySelector('#confirm-cancel')?.addEventListener('click', () => dialog.close());
    document.querySelector('#confirm-accept')?.addEventListener('click', () => {
        if (!pendingForm) return;
        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = 'true';
        dialog.close();
        form.requestSubmit();
        const submit = form.querySelector('button[type="submit"], button:not([type])');
        if (submit) { submit.disabled = true; submit.textContent = 'Procesando…'; }
    });
    dialog?.addEventListener('close', () => { pendingForm = null; });
});
